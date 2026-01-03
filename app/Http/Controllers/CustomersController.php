<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\CustomerBalance;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Services\LogService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AccountingController;

class CustomersController extends Controller
{
    protected $accountingController;
    protected $mainBox;
    protected $defaultCurrency;

    public function __construct(AccountingController $accountingController)
    {
        $this->accountingController = $accountingController;
        
        $this->middleware('permission:read customers', ['only' => ['index']]);
        $this->middleware('permission:create customer', ['only' => ['create']]);
        $this->middleware('permission:update customer', ['only' => ['update', 'edit']]);
        $this->middleware('permission:delete customer', ['only' => ['destroy']]);
        
        // Get main box
        try {
            $userAccount = UserType::where('name', 'account')->first()?->id;
            $this->mainBox = User::with('wallet')
                ->where('type_id', $userAccount)
                ->where('email', 'mainBox@account.com')
                ->first();
        } catch (\Exception $e) {
            $this->mainBox = null;
        }
        
        $this->defaultCurrency = env('DEFAULT_CURRENCY', 'IQD');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Define the filters
        $filters = [
            'name' => $request->name,
            'phone' => $request->phone,
            'is_active' => $request->is_active,
        ];

        // Start the Customer query
        $customersQuery = Customer::with('balance')->latest();

        // Apply the filters if they exist
        $customersQuery->when($filters['name'], function ($query, $name) {
            return $query->where('name', 'LIKE', "%{$name}%");
        });

        $customersQuery->when($filters['phone'], function ($query, $phone) {
            return $query->where('phone', 'LIKE', "%{$phone}%");
        });

        if (isset($filters['is_active'])) {
            $customersQuery->where('is_active', $filters['is_active']);
        }

        // Paginate the filtered customers
        $customers = $customersQuery->paginate(10);
        
        // Calculate debt for each customer
        $customers->getCollection()->transform(function ($customer) {
            $orders = Order::where('customer_id', $customer->id)
                ->where('status', 'due')
                ->get();
            
            $customer->total_debt = $orders->sum(function($order) {
                return ($order->final_amount ?? $order->total_amount) - ($order->total_paid ?? 0);
            });
            
            return $customer;
        });

        return Inertia('Client/index', [
            'translations' => __('messages'),
            'filters' => $filters,
            'customers' => $customers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia('Client/Create', [
            'translations' => __('messages'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        // Create customer instance and assign validated data
        $customer = new Customer($request->validated());
    
        // Handle avatar upload if a file is provided
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $customer->avatar = $path;
        }
    
        // Save the customer
        $customer->save();
    
        // Create a balance record for the customer
        $customer->balance()->create([
            'balance_dollar' => 0, // Initial balance in dollars
            'balance_dinar' => 0,  // Initial balance in dinars
            'currency_preference' => 'dinar', // Default preference
            'last_transaction_date' => now(), // Current date
        ]);

        // Log customer creation
        LogService::createLog(
            'Customer',
            'Created',
            $customer->id,
            [],
            $customer->toArray(),
            'success'
        );
    
        return redirect()->route('customers.index')
            ->with('success', __('messages.data_saved_successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return Inertia('Client/Edit', [
            'translations' => __('messages'),
            'customer' => $customer,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $originalData = $customer->getOriginal();
        // Check if an avatar file is uploaded and store it
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $customer->avatar = $path;
        }

        // Update customer information
        $customer->update($request->validated());

        // Log customer update
        LogService::createLog(
            'Customer',
            'Updated',
            $customer->id,
            $originalData,
            $customer->toArray(),
            'warning'
        );

        return redirect()->route('customers.index')
            ->with('success', __('messages.data_updated_successfully'));
    }

    /**
     * Activate or deactivate the specified resource.
     */
    public function activate(Customer $customer)
    {
        $originalData = $customer->getOriginal();
        $customer->update([
            'is_active' => !$customer->is_active,
        ]);

        // Log status toggle
        LogService::createLog(
            'Customer',
            'Status Toggled',
            $customer->id,
            $originalData,
            $customer->toArray(),
            'info'
        );

        return redirect()->route('customers.index')
            ->with('success', __('messages.status_updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $originalData = $customer->toArray();
        $id = $customer->id;
        $customer->delete();

        // Log deletion
        LogService::createLog(
            'Customer',
            'Deleted',
            $id,
            $originalData,
            [],
            'danger'
        );

        return redirect()->route('customers.index')
            ->with('success', __('messages.data_deleted_successfully'));
    }

    /**
     * Show customer details with invoices
     */
    public function show(Customer $customer)
    {
        // Load customer with balance
        $customer->load('balance');
        
        // Get all orders for this customer
        $orders = Order::where('customer_id', $customer->id)
            ->with('products')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Calculate totals
        $totalDebt = $orders->where('status', 'due')->sum(function($order) {
            return ($order->final_amount ?? $order->total_amount) - ($order->total_paid ?? 0);
        });
        
        $totalPaid = $orders->sum('total_paid');
        $totalInvoices = $orders->sum(function($order) {
            return $order->final_amount ?? $order->total_amount;
        });
        
        // Format orders data
        $invoices = $orders->map(function($order) {
            $finalAmount = $order->final_amount ?? $order->total_amount;
            $paidAmount = $order->total_paid ?? 0;
            $remaining = $finalAmount - $paidAmount;
            
            return [
                'id' => $order->id,
                'order_number' => $order->order_number ?? '#' . $order->id,
                'date' => $order->date ?? $order->created_at->format('Y-m-d'),
                'total_amount' => $order->total_amount,
                'discount_amount' => $order->discount_amount ?? 0,
                'final_amount' => $finalAmount,
                'total_paid' => $paidAmount,
                'remaining' => $remaining,
                'status' => $order->status,
                'payment_method' => $order->payment_method ?? 'cash',
                'notes' => $order->notes,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            ];
        });
        
        // Calculate balance from invoices and payments
        $calculatedBalance = $this->calculateCustomerBalance($customer->id);
        
        // Get all transactions for this customer
        $transactions = \App\Models\Box::where('morphed_id', $customer->id)
            ->where('morphed_type', Customer::class)
            ->orderBy('created', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'name' => $transaction->name,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'description' => $transaction->description,
                    'date' => $transaction->created ?? $transaction->created_at->format('Y-m-d'),
                    'details' => $transaction->details,
                ];
            });
        
        return Inertia::render('Client/Show', [
            'translations' => __('messages'),
            'customer' => $customer,
            'invoices' => $invoices,
            'transactions' => $transactions,
            'statistics' => [
                'total_invoices' => $invoices->count(),
                'total_amount' => $totalInvoices,
                'total_paid' => $totalPaid,
                'total_debt' => $totalDebt,
            ],
            'calculated_balance' => $calculatedBalance,
        ]);
    }

    /**
     * Calculate customer balance from invoices and payments
     */
    protected function calculateCustomerBalance($customerId)
    {
        // Get all orders for customer
        $orders = Order::where('customer_id', $customerId)->get();
        
        // Calculate from orders
        $totalInvoices = $orders->sum(function($order) {
            return $order->final_amount ?? $order->total_amount;
        });
        
        $totalPaidFromOrders = $orders->sum('total_paid');
        
        // Get all Box transactions for this customer (deposits, withdrawals, payments)
        // Only get non-deleted transactions
        $boxTransactions = \App\Models\Box::where('morphed_id', $customerId)
            ->where('morphed_type', Customer::class)
            ->whereNull('deleted_at')
            ->get();
        
        $totalDepositsDollar = 0;
        $totalDepositsDinar = 0;
        $totalWithdrawalsDollar = 0;
        $totalWithdrawalsDinar = 0;
        
        $transactionDetails = [];
        
        foreach ($boxTransactions as $transaction) {
            $amount = (float) $transaction->amount;
            $currency = $transaction->currency;
            
            // Normalize currency
            $isDollar = in_array($currency, ['USD', '$', 'dollar']);
            $isDinar = in_array($currency, ['IQD', 'IQD', 'dinar']);
            
            if ($transaction->type === 'deposit') {
                if ($isDollar) {
                    $totalDepositsDollar += $amount;
                } elseif ($isDinar) {
                    $totalDepositsDinar += $amount;
                }
                
                $transactionDetails[] = [
                    'id' => $transaction->id,
                    'type' => 'deposit',
                    'amount' => $amount,
                    'currency' => $currency,
                    'date' => $transaction->created ?? $transaction->created_at,
                ];
            } elseif ($transaction->type === 'withdrawal') {
                if ($isDollar) {
                    $totalWithdrawalsDollar += $amount;
                } elseif ($isDinar) {
                    $totalWithdrawalsDinar += $amount;
                }
                
                $transactionDetails[] = [
                    'id' => $transaction->id,
                    'type' => 'withdrawal',
                    'amount' => $amount,
                    'currency' => $currency,
                    'date' => $transaction->created ?? $transaction->created_at,
                ];
            }
        }
        
        // Get customer balance record
        $customerBalance = CustomerBalance::where('customer_id', $customerId)->first();
        
        $storedBalanceDollar = (float) ($customerBalance->balance_dollar ?? 0);
        $storedBalanceDinar = (float) ($customerBalance->balance_dinar ?? 0);
        
        // Calculate expected balance: الإيداعات - السحوبات
        $calculatedBalanceDollar = $totalDepositsDollar - $totalWithdrawalsDollar;
        $calculatedBalanceDinar = $totalDepositsDinar - $totalWithdrawalsDinar;
        
        // Calculate difference
        $differenceDollar = $storedBalanceDollar - $calculatedBalanceDollar;
        $differenceDinar = $storedBalanceDinar - $calculatedBalanceDinar;
        
        return [
            'stored' => [
                'dollar' => round($storedBalanceDollar, 2),
                'dinar' => round($storedBalanceDinar, 2),
            ],
            'calculated' => [
                'dollar' => round($calculatedBalanceDollar, 2),
                'dinar' => round($calculatedBalanceDinar, 2),
            ],
            'difference' => [
                'dollar' => round($differenceDollar, 2),
                'dinar' => round($differenceDinar, 2),
            ],
            'is_balanced' => abs($differenceDollar) < 0.01 && abs($differenceDinar) < 0.01,
            'transactions' => [
                'total_deposits_dollar' => round($totalDepositsDollar, 2),
                'total_deposits_dinar' => round($totalDepositsDinar, 2),
                'total_withdrawals_dollar' => round($totalWithdrawalsDollar, 2),
                'total_withdrawals_dinar' => round($totalWithdrawalsDinar, 2),
                'details' => $transactionDetails,
            ],
            'orders' => [
                'total_invoices' => round($totalInvoices, 2),
                'total_paid' => round($totalPaidFromOrders, 2),
            ],
        ];
    }

    /**
     * API endpoint to verify and recalculate customer balance
     */
    public function verifyBalance(Request $request, Customer $customer)
    {
        $calculatedBalance = $this->calculateCustomerBalance($customer->id);
        
        $shouldFix = $request->get('fix', false);
        
        // إذا كان هناك اختلاف، قم بالتصحيح تلقائياً
        if ($shouldFix || !$calculatedBalance['is_balanced']) {
            DB::beginTransaction();
            
            try {
                $customerBalance = CustomerBalance::firstOrCreate(
                    ['customer_id' => $customer->id],
                    [
                        'balance_dollar' => 0,
                        'balance_dinar' => 0,
                        'last_transaction_date' => now(),
                    ]
                );
                
                $oldBalanceDollar = $customerBalance->balance_dollar;
                $oldBalanceDinar = $customerBalance->balance_dinar;
                
                // Update balance to match calculated
                $customerBalance->balance_dollar = $calculatedBalance['calculated']['dollar'];
                $customerBalance->balance_dinar = $calculatedBalance['calculated']['dinar'];
                $customerBalance->last_transaction_date = now();
                $customerBalance->save();
                
                DB::commit();
                
                // Log the correction
                Log::info('Customer balance corrected', [
                    'customer_id' => $customer->id,
                    'old_balance_dollar' => $oldBalanceDollar,
                    'old_balance_dinar' => $oldBalanceDinar,
                    'new_balance_dollar' => $customerBalance->balance_dollar,
                    'new_balance_dinar' => $customerBalance->balance_dinar,
                    'difference_dollar' => $calculatedBalance['difference']['dollar'],
                    'difference_dinar' => $calculatedBalance['difference']['dinar'],
                ]);
                
                // Recalculate after fix
                $calculatedBalance = $this->calculateCustomerBalance($customer->id);
                
                return response()->json([
                    'success' => true,
                    'message' => 'تم تصحيح الرصيد بنجاح',
                    'balance' => $calculatedBalance,
                    'corrected' => true,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                
                Log::error('Error correcting customer balance', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تصحيح الرصيد: ' . $e->getMessage(),
                    'balance' => $calculatedBalance,
                ], 500);
            }
        }
        
        return response()->json([
            'success' => true,
            'balance' => $calculatedBalance,
            'corrected' => false,
        ]);
    }

    /**
     * Pay invoice from customer page
     */
    public function payInvoice(Request $request, Customer $customer, Order $order)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,balance,transfer',
            'notes' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            $finalAmount = $order->final_amount ?? $order->total_amount;
            $currentPaid = $order->total_paid ?? 0;
            $remaining = $finalAmount - $currentPaid;
            
            // Validate payment amount
            if ($validated['amount'] > $remaining) {
                throw new \Exception('المبلغ المدفوع أكبر من المبلغ المتبقي');
            }
            
            $newPaidAmount = $currentPaid + $validated['amount'];
            $newStatus = $newPaidAmount >= $finalAmount ? 'paid' : 'due';
            
            // Update order
            $order->update([
                'total_paid' => $newPaidAmount,
                'status' => $newStatus,
            ]);
            
            // Handle payment based on method
            if ($validated['payment_method'] === 'cash') {
                // Add to main box (similar to OrderController)
                $accountingController = app(AccountingController::class);
                $userAccount = \App\Models\UserType::where('name', 'account')->first()?->id;
                $mainBox = \App\Models\User::with('wallet')
                    ->where('type_id', $userAccount)
                    ->where('email', 'mainBox@account.com')
                    ->first();
                
                if ($mainBox) {
                    $accountingController->increaseWallet(
                        $validated['amount'],
                        'دفع نقدي فاتورة رقم ' . $order->id . ' - ' . $customer->name,
                        $mainBox->id,
                        $order->id,
                        Order::class,
                        0,
                        0,
                        env('DEFAULT_CURRENCY', 'IQD'),
                        $order->date ?? now()->format('Y-m-d')
                    );
                }
            } elseif ($validated['payment_method'] === 'balance') {
                // Deduct from customer balance
                $customerBalance = CustomerBalance::firstOrCreate(
                    ['customer_id' => $customer->id],
                    [
                        'balance_dollar' => 0,
                        'balance_dinar' => 0,
                        'last_transaction_date' => now(),
                    ]
                );
                
                // Assuming default currency is IQD (dinar)
                // You may need to adjust this based on your system
                if ($customerBalance->balance_dinar < $validated['amount']) {
                    throw new \Exception('الرصيد غير كافي');
                }
                
                $customerBalance->balance_dinar -= $validated['amount'];
                $customerBalance->last_transaction_date = now();
                $customerBalance->save();
                
                // إضافة المعاملة في الصندوق الرئيسي (دفع من الرصيد)
                if ($this->mainBox && $this->mainBox->wallet) {
                    $this->accountingController->increaseWallet(
                        (int) round($validated['amount']),
                        "دفع فاتورة رقم {$order->id} من الرصيد - {$customer->name}",
                        $this->mainBox->id,
                        $order->id,
                        Order::class,
                        0,
                        0,
                        $this->defaultCurrency,
                        $order->date ?? now()->format('Y-m-d'),
                        0,
                        'in',
                        ['notes' => $validated['notes'] ?? '', 'customer_id' => $customer->id, 'payment_method' => 'balance']
                    );
                }
            }
            
            // Log the payment
            LogService::createLog(
                'Order Payment',
                'Payment Made',
                $order->id,
                ['old_paid' => $currentPaid, 'old_status' => $order->getOriginal('status')],
                ['new_paid' => $newPaidAmount, 'new_status' => $newStatus, 'payment_method' => $validated['payment_method']],
                'success'
            );
            
            DB::commit();
            
            return redirect()->route('customers.show', $customer)
                ->with('success', 'تم دفع الفاتورة بنجاح');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
