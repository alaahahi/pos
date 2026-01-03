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
use Illuminate\Support\Facades\Log;
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
        
        // Log للتحقق من الحركات
        Log::debug('Box transactions for customer', [
            'customer_id' => $customerId,
            'total_transactions' => $boxTransactions->count(),
            'transactions' => $boxTransactions->map(function($t) {
                return [
                    'id' => $t->id,
                    'type' => $t->type,
                    'amount' => $t->amount,
                    'currency' => $t->currency,
                    'details' => $t->details,
                ];
            })->toArray(),
        ]);
        
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
            } elseif ($transaction->type === 'payment') {
                // التحقق من طريقة الدفع من details
                $details = $transaction->details ?? [];
                
                // إذا كان details string، قم بتحويله إلى array
                if (is_string($details)) {
                    $details = json_decode($details, true) ?? [];
                }
                
                $paymentMethod = is_array($details) ? ($details['payment_method'] ?? null) : null;
                
                // Log للتحقق
                Log::debug('Payment transaction processing', [
                    'transaction_id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $amount,
                    'currency' => $currency,
                    'details_raw' => $transaction->details,
                    'details_parsed' => $details,
                    'payment_method' => $paymentMethod,
                ]);
                
                // دفع من الرصيد - يعتبر سحب من الرصيد
                if ($paymentMethod === 'balance') {
                    if ($isDollar) {
                        $totalWithdrawalsDollar += $amount;
                    } elseif ($isDinar) {
                        $totalWithdrawalsDinar += $amount;
                    }
                    
                    $transactionDetails[] = [
                        'id' => $transaction->id,
                        'type' => 'payment',
                        'amount' => $amount,
                        'currency' => $currency,
                        'date' => $transaction->created ?? $transaction->created_at,
                        'payment_method' => 'balance',
                    ];
                }
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
        $autoFix = $request->get('auto_fix', true); // تصحيح تلقائي افتراضي
        
        // Log للتحقق من الحسابات
        Log::info('Balance verification', [
            'customer_id' => $customer->id,
            'stored' => $calculatedBalance['stored'],
            'calculated' => $calculatedBalance['calculated'],
            'difference' => $calculatedBalance['difference'],
            'is_balanced' => $calculatedBalance['is_balanced'],
            'transactions' => [
                'deposits_dinar' => $calculatedBalance['transactions']['total_deposits_dinar'],
                'withdrawals_dinar' => $calculatedBalance['transactions']['total_withdrawals_dinar'],
                'details_count' => count($calculatedBalance['transactions']['details'] ?? []),
            ],
        ]);
        
        // إذا كان هناك اختلاف، قم بالتصحيح تلقائياً (ما لم يتم تعطيله)
        if (($shouldFix || ($autoFix && !$calculatedBalance['is_balanced']))) {
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
            'total_paid' => 'nullable|numeric|min:0', // مجموع الدفعات بعد إضافة الدفعة الجديدة
            'total_payments' => 'nullable|numeric|min:0', // مجموع الدفعات الإجمالي للعميل
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
            
            // استخدام total_paid المرسل من الفرونت إذا كان موجوداً، وإلا حسابها
            $newPaidAmount = $validated['total_paid'] ?? ($currentPaid + $validated['amount']);
            
            // Log مجموع الدفعات المرسل من الفرونت
            if (isset($validated['total_paid'])) {
                Log::info('Total paid received from frontend', [
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                    'current_paid' => $currentPaid,
                    'payment_amount' => $validated['amount'],
                    'total_paid_from_frontend' => $validated['total_paid'],
                    'calculated_total_paid' => $currentPaid + $validated['amount'],
                    'total_payments_customer' => $validated['total_payments'] ?? null,
                ]);
            }
            
            $newStatus = $newPaidAmount >= $finalAmount ? 'paid' : 'due';
            
            // Update order
            $order->update([
                'total_paid' => $newPaidAmount,
                'status' => $newStatus,
            ]);
            
            // Handle payment based on method
            if ($validated['payment_method'] === 'cash') {
                // Add to main box (لأن المال يدخل فعلياً)
                $accountingController = app(AccountingController::class);
                $userAccount = \App\Models\UserType::where('name', 'account')->first()?->id;
                $mainBox = \App\Models\User::with('wallet')
                    ->where('type_id', $userAccount)
                    ->where('email', 'mainBox@account.com')
                    ->first();
                
                if ($mainBox && $mainBox->wallet) {
                    $currencyCode = $order->currency ?? env('DEFAULT_CURRENCY', 'IQD');
                    $currency = in_array(strtoupper($currencyCode), ['USD', '$', 'DOLLAR']) ? '$' : 'IQD';
                    
                    $transaction = $accountingController->increaseWallet(
                        $validated['amount'],
                        'دفع نقدي فاتورة رقم ' . $order->id . ' - ' . $customer->name,
                        $mainBox->id,
                        $order->id,
                        Order::class,
                        0,
                        0,
                        $currency,
                        $order->date ?? now()->format('Y-m-d')
                    );
                    
                    Log::info('Cash payment added to main box', [
                        'order_id' => $order->id,
                        'customer_id' => $customer->id,
                        'amount' => $validated['amount'],
                        'currency' => $currency,
                        'transaction_id' => $transaction?->id,
                    ]);
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
                
                // تحديد العملة من الفاتورة أو استخدام العملة الافتراضية
                $orderCurrency = $order->currency ?? $this->defaultCurrency;
                $isDollar = in_array(strtoupper($orderCurrency), ['USD', '$', 'DOLLAR']);
                $isDinar = in_array(strtoupper($orderCurrency), ['IQD', 'IQD', 'DINAR']);
                
                // إذا لم يتم تحديد العملة، استخدم العملة الافتراضية
                if (!$isDollar && !$isDinar) {
                    $isDinar = ($this->defaultCurrency === 'IQD');
                }
                
                // التحقق من الرصيد وخصمه
                if ($isDollar) {
                    if ($customerBalance->balance_dollar < $validated['amount']) {
                        throw new \Exception('الرصيد بالدولار غير كافي');
                    }
                    $customerBalance->balance_dollar -= $validated['amount'];
                } else {
                    if ($customerBalance->balance_dinar < $validated['amount']) {
                        throw new \Exception('الرصيد بالدينار غير كافي');
                    }
                    $customerBalance->balance_dinar -= $validated['amount'];
                }
                
                $customerBalance->last_transaction_date = now();
                
                // حفظ الرصيد مع التأكد من الحفظ
                if (!$customerBalance->save()) {
                    throw new \Exception('فشل في تحديث رصيد العميل');
                }
                
                // إعادة تحميل الرصيد للتأكد من التحديث
                $customerBalance->refresh();
                
                // تسجيل الحركة في Box للعميل فقط (بدون إضافة في الصندوق الرئيسي)
                // لأن المبلغ لم يدخل الصندوق فعلياً - فقط تحويل داخلي
                $currencyCode = $isDollar ? 'USD' : 'IQD';
                $paymentBox = \App\Models\Box::create([
                    'name' => "دفع فاتورة رقم {$order->id} من الرصيد - {$customer->name}",
                    'amount' => $validated['amount'],
                    'type' => 'payment',
                    'description' => "دفع فاتورة رقم {$order->id} من الرصيد - {$customer->name}",
                    'currency' => $currencyCode,
                    'created' => $order->date ?? now()->format('Y-m-d'),
                    'details' => [
                        'notes' => $validated['notes'] ?? '',
                        'customer_id' => $customer->id,
                        'order_id' => $order->id,
                        'payment_method' => 'balance',
                    ],
                    'morphed_id' => $customer->id,
                    'morphed_type' => Customer::class,
                    'is_active' => true,
                    'balance' => 0,
                    'balance_usd' => 0,
                ]);
                
                // التأكد من عدم إضافة المعاملة في الصندوق الرئيسي
                // (لا نستدعي increaseWallet هنا لأن المال لم يدخل فعلياً)
                
                // Log للتحقق من إنشاء الحركة
                Log::info('Payment from balance Box created (NO main box transaction)', [
                    'box_id' => $paymentBox->id,
                    'customer_id' => $customer->id,
                    'order_id' => $order->id,
                    'amount' => $validated['amount'],
                    'currency' => $currencyCode,
                    'payment_method' => 'balance',
                    'details' => $paymentBox->details,
                    'note' => 'This transaction should NOT appear in main box',
                ]);
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
