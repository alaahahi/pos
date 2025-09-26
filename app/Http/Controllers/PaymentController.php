<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerBalance;
use App\Models\DecorationOrder;
use App\Models\Box;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read payment', ['only' => ['index']]);
        $this->middleware('permission:create payment', ['only' => ['create', 'store']]);
        $this->middleware('permission:update payment', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete payment', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of payments.
     */
    public function index(Request $request)
    {
        $filters = [
            'customer' => $request->customer,
            'type' => $request->type,
            'currency' => $request->currency,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        // Start the Box query for payments
        $paymentQuery = Box::with('morphed')
            ->where('type', 'payment')
            ->latest();

        // Apply filters
        $paymentQuery->when($filters['customer'], function ($query, $customer) {
            return $query->whereHas('morphed', function ($q) use ($customer) {
                $q->where('customer_id', $customer);
            });
        });

        $paymentQuery->when($filters['currency'], function ($query, $currency) {
            return $query->where('currency', $currency);
        });

        $paymentQuery->when($filters['date_from'], function ($query, $date) {
            return $query->whereDate('created', '>=', $date);
        });

        $paymentQuery->when($filters['date_to'], function ($query, $date) {
            return $query->whereDate('created', '<=', $date);
        });

        $payments = $paymentQuery->paginate(15);

        // Get customers for filter dropdown
        $customers = Customer::select('id', 'name')->get();

        // Get customer balances
        $customerBalances = CustomerBalance::with('customer')->get();

        return Inertia::render('Payments/Index', [
            'translations' => __('messages'),
            'filters' => $filters,
            'payments' => $payments,
            'customers' => $customers,
            'customerBalances' => $customerBalances,
        ]);
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create()
    {
        $customers = Customer::with('balance')->get();
        $decorationOrders = DecorationOrder::with('customer')
            ->whereIn('status', ['created', 'received', 'executing', 'partial_payment'])
            ->get();

        return Inertia::render('Payments/Create', [
            'translations' => __('messages'),
            'customers' => $customers,
            'decorationOrders' => $decorationOrders,
        ]);
    }

    /**
     * Store a newly created payment in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:dollar,dinar',
            'type' => 'required|in:deposit,withdrawal,payment',
            'description' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'decoration_order_id' => 'nullable|exists:decoration_orders,id',
        ]);

        DB::beginTransaction();

        try {
            // Create payment record
            $payment = Box::create([
                'amount' => $request->amount,
                'type' => $request->type,
                'description' => $request->description,
                'currency' => $request->currency,
                'created' => now(),
                'details' => [
                    'notes' => $request->notes,
                    'decoration_order_id' => $request->decoration_order_id,
                    'customer_id' => $request->customer_id,
                ],
                'morphed_id' => $request->customer_id,
                'morphed_type' => Customer::class,
            ]);

            // Update customer balance
            $customerBalance = CustomerBalance::firstOrCreate(
                ['customer_id' => $request->customer_id],
                [
                    'balance_dollar' => 0,
                    'balance_dinar' => 0,
                    'last_transaction_date' => now(),
                ]
            );

            if ($request->type === 'deposit') {
                // Add to balance
                if ($request->currency === 'dollar') {
                    $customerBalance->balance_dollar += $request->amount;
                } else {
                    $customerBalance->balance_dinar += $request->amount;
                }
            } elseif ($request->type === 'withdrawal') {
                // Subtract from balance
                if ($request->currency === 'dollar') {
                    if ($customerBalance->balance_dollar < $request->amount) {
                        throw new \Exception('الرصيد غير كافي للسحب');
                    }
                    $customerBalance->balance_dollar -= $request->amount;
                } else {
                    if ($customerBalance->balance_dinar < $request->amount) {
                        throw new \Exception('الرصيد غير كافي للسحب');
                    }
                    $customerBalance->balance_dinar -= $request->amount;
                }
            } elseif ($request->type === 'payment' && $request->decoration_order_id) {
                // Payment for decoration order
                $decorationOrder = DecorationOrder::find($request->decoration_order_id);
                $newPaidAmount = $decorationOrder->paid_amount + $request->amount;
                
                // Update decoration order
                $decorationOrder->update([
                    'paid_amount' => $newPaidAmount,
                    'status' => $newPaidAmount >= $decorationOrder->total_price ? 'full_payment' : 'partial_payment',
                    'paid_at' => now(),
                ]);

                // Deduct from customer balance if available
                if ($request->currency === 'dollar') {
                    $availableBalance = $customerBalance->balance_dollar;
                    if ($availableBalance > 0) {
                        $deductAmount = min($availableBalance, $request->amount);
                        $customerBalance->balance_dollar -= $deductAmount;
                    }
                } else {
                    $availableBalance = $customerBalance->balance_dinar;
                    if ($availableBalance > 0) {
                        $deductAmount = min($availableBalance, $request->amount);
                        $customerBalance->balance_dinar -= $deductAmount;
                    }
                }
            }

            $customerBalance->last_transaction_date = now();
            $customerBalance->save();

            DB::commit();

            Log::info('Payment created', [
                'payment_id' => $payment->id,
                'customer_id' => $request->customer_id,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'type' => $request->type,
            ]);

            return redirect()->route('payments.index')
                ->with('success', 'تم إنشاء الدفعة بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Payment creation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified payment.
     */
    public function show(Box $payment)
    {
        $payment->load('morphed');
        
        return Inertia::render('Payments/Show', [
            'translations' => __('messages'),
            'payment' => $payment,
        ]);
    }

    /**
     * Show the form for editing the specified payment.
     */
    public function edit(Box $payment)
    {
        $customers = Customer::with('balance')->get();
        $decorationOrders = DecorationOrder::with('customer')
            ->whereIn('status', ['created', 'received', 'executing', 'partial_payment'])
            ->get();

        return Inertia::render('Payments/Edit', [
            'translations' => __('messages'),
            'payment' => $payment,
            'customers' => $customers,
            'decorationOrders' => $decorationOrders,
        ]);
    }

    /**
     * Update the specified payment in storage.
     */
    public function update(Request $request, Box $payment)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:dollar,dinar',
            'type' => 'required|in:deposit,withdrawal,payment',
            'description' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        $payment->update([
            'amount' => $request->amount,
            'type' => $request->type,
            'description' => $request->description,
            'currency' => $request->currency,
            'details' => array_merge($payment->details ?? [], [
                'notes' => $request->notes,
            ]),
        ]);

        Log::info('Payment updated', [
            'payment_id' => $payment->id,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'type' => $request->type,
        ]);

        return redirect()->route('payments.index')
            ->with('success', 'تم تحديث الدفعة بنجاح');
    }

    /**
     * Remove the specified payment from storage.
     */
    public function destroy(Box $payment)
    {
        $payment->delete();

        Log::info('Payment deleted', [
            'payment_id' => $payment->id,
        ]);

        return redirect()->route('payments.index')
            ->with('success', 'تم حذف الدفعة بنجاح');
    }

    /**
     * Get customer balance
     */
    public function getCustomerBalance(Request $request)
    {
        $customerId = $request->customer_id;
        
        $balance = CustomerBalance::where('customer_id', $customerId)->first();
        
        if (!$balance) {
            return response()->json([
                'balance_dollar' => 0,
                'balance_dinar' => 0,
            ]);
        }

        return response()->json([
            'balance_dollar' => $balance->balance_dollar,
            'balance_dinar' => $balance->balance_dinar,
        ]);
    }
}
