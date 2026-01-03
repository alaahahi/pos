<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\DailyClose;
use App\Models\MonthlyClose;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use App\Http\Requests\StoreBoxRequest;
use App\Http\Requests\UpdateBoxRequest;
use App\Http\Controllers\AccountingController;
use App\Models\UserType;
use App\Models\User;
use App\Models\Transactions;
use Carbon\Carbon;
use App\Services\LogService;
use Illuminate\Support\Facades\DB;
class BoxesController extends Controller
{
    public function __construct(AccountingController $accountingController)
    {
        $this->middleware('permission:read boxes', ['only' => ['index']]);
        $this->middleware('permission:create box', ['only' => ['create']]);
        $this->middleware('permission:update box', ['only' => ['update', 'edit']]);
        $this->middleware('permission:delete box', ['only' => ['destroy']]);
        $this->accountingController = $accountingController;
        $this->userAccount =  UserType::where('name', 'account')->first()?->id;
        $this->mainBox= User::with('wallet')->where('type_id', $this->userAccount)->where('email','mainBox@account.com');
        $this->defaultCurrency = env('DEFAULT_CURRENCY', 'IQD'); // مثلاً 'KWD' كخيار افتراضي
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Define the filters
        $filters = [
            'name' => $request->name,
            'note' => $request->note,
            'start_date' => $request->start_date ?? Carbon::today()->toDateString(),
            'end_date' => $request->end_date ?? Carbon::today()->toDateString(),
        ];

        // Start the Box query
        $transactionsQuery = Transactions::with('morphed', 'TransactionsImages')->orderBy('created_at', 'desc');

        // Apply the filters if they exist
        // Search by name in the morphed relationship
        $transactionsQuery->when($filters['name'], function ($query, $name) {
            return $query->whereHasMorph('morphed', ['App\Models\User', 'App\Models\Customer'], function ($query) use ($name) {
                $query->where('name', 'LIKE', "%{$name}%");
            });
        });

        // Search by description/note
        $transactionsQuery->when($filters['note'], function ($query, $note) {
            return $query->where('description', 'LIKE', "%{$note}%");
        });

        // Filter by date range
        $transactionsQuery->when($filters['start_date'], function ($query, $start_date) {
            return $query->whereDate('created_at', '>=', $start_date);
        });

        $transactionsQuery->when($filters['end_date'], function ($query, $end_date) {
            return $query->whereDate('created_at', '<=', $end_date);
        });

    
        // Paginate the filtered transactions
        $transactions = $transactionsQuery->paginate(10)->appends($filters);

        // Get the main box user with wallet (this is what's actually updated)
        $mainBoxUser = User::with('wallet')
            ->where('type_id', $this->userAccount)
            ->where('email', 'mainBox@account.com')
            ->first();
        
        // Prepare mainBox data from the user's wallet
        $mainBoxData = null;
        if ($mainBoxUser && $mainBoxUser->wallet) {
            $walletId = $mainBoxUser->wallet->id;
            
            // حساب الرصيد الفعلي من المعاملات (جمع وطرح)
            $calculatedBalanceUSD = Transactions::where('wallet_id', $walletId)
                ->where(function($query) {
                    $query->where('currency', 'USD')
                          ->orWhere('currency', '$');
                })
                ->sum('amount');
            
            $calculatedBalanceIQD = Transactions::where('wallet_id', $walletId)
                ->where('currency', 'IQD')
                ->sum('amount');
            
            // الرصيد المخزن في قاعدة البيانات
            $storedBalanceUSD = $mainBoxUser->wallet->balance ?? 0;
            $storedBalanceIQD = $mainBoxUser->wallet->balance_dinar ?? 0;
            
            // تصحيح الرصيد المخزن إذا كان هناك فرق
            if (abs($calculatedBalanceUSD - $storedBalanceUSD) > 0.01) {
                $mainBoxUser->wallet->update(['balance' => $calculatedBalanceUSD]);
            }
            
            if (abs($calculatedBalanceIQD - $storedBalanceIQD) > 0.01) {
                $mainBoxUser->wallet->update(['balance_dinar' => $calculatedBalanceIQD]);
            }
            
            $mainBoxData = (object)[
                'id' => $mainBoxUser->id,
                'balance' => $calculatedBalanceIQD,  // IQD - المحسوب من المعاملات
                'balance_usd' => $calculatedBalanceUSD, // USD - المحسوب من المعاملات
            ];
        } else {
            // Fallback to Box model if mainBox user doesn't exist
            $box = Box::where('is_active', true)->first();
            if ($box) {
                $mainBoxData = $box;
            }
        }
        
        // Get system config for exchange rate
        $systemConfig = \App\Models\SystemConfig::first();
        
        // Get today's daily close
        $dailyClose = DailyClose::getToday();
        $dailyClose->calculateDailyData();
        $dailyClose->save(); // Save calculated data
        
        // Get current month's monthly close
        $monthlyClose = MonthlyClose::getCurrentMonth();
        $monthlyClose->calculateMonthlyData();
        $monthlyClose->save(); // Save calculated data
        
        return Inertia('Boxes/index', [
            'translations' => __('messages'),
            'filters' => $filters,
            'transactions' => $transactions,
            'mainBox' => $mainBoxData,
            'exchangeRate' => $systemConfig ? $systemConfig->exchange_rate : 1500,
            'dailyClose' => $dailyClose,
            'monthlyClose' => $monthlyClose,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia('Boxes/Create', [
            'translations' => __('messages'),
        ]);
    }

    /**
     * Update the specified resource
     */
    public function update(Request $request, Box $box)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();

        try {
            $oldAmount = $box->amount;
            $box->update([
                'amount' => $request->amount,
            ]);

            // إذا كانت الحركة مرتبطة بعميل، يجب تحديث رصيد العميل
            if ($box->morphed_type === \App\Models\Customer::class && $box->morphed_id) {
                $customerBalance = \App\Models\CustomerBalance::where('customer_id', $box->morphed_id)->first();
                
                if ($customerBalance) {
                    $difference = $request->amount - $oldAmount;
                    $currency = $box->currency;
                    
                    if ($box->type === 'deposit') {
                        // إيداع: الفرق يضاف للرصيد
                        if ($currency === 'USD' || $currency === '$') {
                            $customerBalance->balance_dollar += $difference;
                        } else {
                            $customerBalance->balance_dinar += $difference;
                        }
                    } elseif ($box->type === 'withdrawal') {
                        // سحب: الفرق يطرح من الرصيد
                        if ($currency === 'USD' || $currency === '$') {
                            $customerBalance->balance_dollar -= $difference;
                        } else {
                            $customerBalance->balance_dinar -= $difference;
                        }
                    }
                    
                    $customerBalance->last_transaction_date = now();
                    $customerBalance->save();
                }
            }

            DB::commit();

            LogService::createLog(
                'Box',
                'Update Amount',
                $box->id,
                ['old_amount' => $oldAmount],
                ['new_amount' => $request->amount],
                'info'
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المبلغ بنجاح',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المبلغ: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function addToBox(Request $request)
    {
        $request->validate([
            'amountDollar' => 'nullable|numeric',
            'amountDinar' => 'nullable|numeric',
            'amountNote' => 'nullable|string',
        ]);
        if($request->amountDollar > 0){
            $transaction = $this->accountingController->increaseWallet(
                $request->amountDollar??0, // المبلغ المدفوع
                $request->amountNote ?? '', // الوصف
                $this->mainBox->first()->id, // الصندوق الرئيسي للعميل
                $this->mainBox->first()->id, // صندوق النظام أو المستخدم الأساسي
                'App\Models\User',
                0,
                0,
                '$',
                $request->date
            );

            if ($transaction) {
                LogService::createLog(
                    'Box',
                    'Deposit',
                    $transaction->id,
                    [],
                    [
                        'amount' => $request->amountDollar,
                        'currency' => '$',
                        'note' => $request->amountNote ?? ''
                    ],
                    'success'
                );
            }
        }
        if($request->amountDinar > 0){
            $transaction = $this->accountingController->increaseWallet(
                $request->amountDinar??0, // المبلغ المدفوع
                $request->amountNote ?? '', // الوصف
                $this->mainBox->first()->id, // الصندوق الرئيسي للعميل
                $this->mainBox->first()->id, // صندوق النظام أو المستخدم الأساسي
                'App\Models\User',
                0,
                0,
                'IQD',
                $request->date
            );

            if ($transaction) {
                LogService::createLog(
                    'Box',
                    'Deposit',
                    $transaction->id,
                    [],
                    [
                        'amount' => $request->amountDinar,
                        'currency' => 'IQD',
                        'note' => $request->amountNote ?? ''
                    ],
                    'success'
                );
            }
        }
        
        // Update daily close after adding to box
        $dailyClose = DailyClose::getToday();
        $dailyClose->calculateDailyData();
        $dailyClose->save();
        
        // Update monthly close
        $monthlyClose = MonthlyClose::getCurrentMonth();
        $monthlyClose->calculateMonthlyData();
        $monthlyClose->save();
        
        return response()->json(['message' => 'Transaction added successfully']);
    }
    public function dropFromBox(Request $request)
    {
        $request->validate([
            'amountDollar' => 'nullable|numeric',
            'amountDinar' => 'nullable|numeric',
            'amountNote' => 'nullable|string',
        ]);
        if($request->amountDollar > 0){
            $transaction = $this->accountingController->decreaseWallet(
                $request->amountDollar??0, // المبلغ المدفوع
                $request->amountNote ?? '', // الوصف
                $this->mainBox->first()->id, // الصندوق الرئيسي للعميل
                $this->mainBox->first()->id, // صندوق النظام أو المستخدم الأساسي
                'App\Models\User',
                0,
                0,
                '$',
                $request->date
            );

            if ($transaction) {
                LogService::createLog(
                    'Box',
                    'Withdraw',
                    $transaction->id,
                    [],
                    [
                        'amount' => $request->amountDollar,
                        'currency' => '$',
                        'note' => $request->amountNote ?? ''
                    ],
                    'warning'
                );
            }
        }
        if($request->amountDinar > 0){
            $transaction = $this->accountingController->decreaseWallet(
                $request->amountDinar??0, // المبلغ المدفوع
                $request->amountNote ?? '', // الوصف
                $this->mainBox->first()->id, // الصندوق الرئيسي للعميل
                $this->mainBox->first()->id, // صندوق النظام أو المستخدم الأساسي
                'App\Models\User',
                0,
                0,
                'IQD',
                $request->date
            );

            if ($transaction) {
                LogService::createLog(
                    'Box',
                    'Withdraw',
                    $transaction->id,
                    [],
                    [
                        'amount' => $request->amountDinar,
                        'currency' => 'IQD',
                        'note' => $request->amountNote ?? ''
                    ],
                    'warning'
                );
            }
        }
 
        
        return response()->json(['message' => 'Transaction dropped successfully']);
    }
    public function transactions(Request $request)
    {
        $filters = [
            'name' => $request->name,
            'phone' => $request->phone,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $request->is_active,
        ];

        // Start the Box query
        $transactionsQuery = Transactions::with('TransactionsImages', 'morphed')->orderBy('created_at', 'desc');

        // Apply the filters if they exist
        $transactionsQuery->when($filters['name'], function ($query, $name) {
            return $query->where('name', 'LIKE', "%{$name}%");
        });

        $transactionsQuery->when($filters['phone'], function ($query, $phone) {
            return $query->where('phone', 'LIKE', "%{$phone}%");
        });

        $transactionsQuery->when($filters['start_date']??Carbon::now()->subDays(1), function ($query, $start_date) {
            return $query->where('created', '>=', $start_date);
        });

        $transactionsQuery->when($filters['end_date']??Carbon::now(), function ($query, $end_date) {
            return $query->where('created', '<=', $end_date);
        });

    
        // Paginate the filtered boxes
        $transactions = $transactionsQuery->paginate(10);
        $transactions->appends(request()->query());
        return response()->json($transactions);
    }

    /**
     * Close daily sales
     */
    public function closeDaily(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            $date = $request->date ? Carbon::parse($request->date) : today();
            
            $dailyClose = DailyClose::firstOrCreate(
                ['close_date' => $date->format('Y-m-d')],
                ['status' => 'open']
            );

            if ($dailyClose->status === 'closed') {
                return response()->json([
                    'success' => false,
                    'message' => 'اليوم مغلق بالفعل'
                ], 400);
            }

            // Calculate and close
            $dailyClose->calculateDailyData();
            if ($request->notes) {
                $dailyClose->notes = $request->notes;
            }
            $dailyClose->close();

            return response()->json([
                'success' => true,
                'message' => 'تم إغلاق اليوم بنجاح',
                'data' => $dailyClose
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Close monthly sales
     */
    public function closeMonthly(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'notes' => 'nullable|string',
        ]);

        try {
            $year = $request->year ?? now()->year;
            $month = $request->month ?? now()->month;
            
            $monthlyClose = MonthlyClose::firstOrCreate(
                [
                    'year' => $year,
                    'month' => $month,
                ],
                ['status' => 'open']
            );

            if ($monthlyClose->status === 'closed') {
                return response()->json([
                    'success' => false,
                    'message' => 'الشهر مغلق بالفعل'
                ], 400);
            }

            // Calculate and close
            $monthlyClose->calculateMonthlyData();
            if ($request->notes) {
                $monthlyClose->notes = $request->notes;
            }
            $monthlyClose->close();

            return response()->json([
                'success' => true,
                'message' => 'تم إغلاق الشهر بنجاح',
                'data' => $monthlyClose
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get daily close data
     */
    public function getDailyClose(Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : today();
        
        $dailyClose = DailyClose::firstOrCreate(
            ['close_date' => $date->format('Y-m-d')],
            ['status' => 'open']
        );
        
        $dailyClose->calculateDailyData();
        $dailyClose->save(); // Save calculated data
        
        return response()->json($dailyClose);
    }

    /**
     * Get monthly close data
     */
    public function getMonthlyClose(Request $request)
    {
        $year = $request->year ?? now()->year;
        $month = $request->month ?? now()->month;
        
        $monthlyClose = MonthlyClose::firstOrCreate(
            [
                'year' => $year,
                'month' => $month,
            ],
            ['status' => 'open']
        );
        
        $monthlyClose->calculateMonthlyData();
        $monthlyClose->save(); // Save calculated data
        
        return response()->json($monthlyClose);
    }

    /**
     * Get list of daily and monthly closes with filters
     */
    public function closesList(Request $request)
    {
        $filters = [
            'type' => $request->type ?? 'all', // 'daily', 'monthly', 'all'
            'status' => $request->status ?? 'all', // 'open', 'closed', 'all'
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'year' => $request->year,
            'month' => $request->month,
        ];

        $dailyCloses = collect();
        $monthlyCloses = collect();

        // Get daily closes
        if ($filters['type'] === 'all' || $filters['type'] === 'daily') {
            $dailyQuery = DailyClose::query();

            if ($filters['status'] !== 'all') {
                $dailyQuery->where('status', $filters['status']);
            }

            if ($filters['start_date']) {
                $dailyQuery->whereDate('close_date', '>=', $filters['start_date']);
            }

            if ($filters['end_date']) {
                $dailyQuery->whereDate('close_date', '<=', $filters['end_date']);
            }

            $dailyCloses = $dailyQuery->orderBy('close_date', 'desc')->paginate(15);
        }

        // Get monthly closes
        if ($filters['type'] === 'all' || $filters['type'] === 'monthly') {
            $monthlyQuery = MonthlyClose::query();

            if ($filters['status'] !== 'all') {
                $monthlyQuery->where('status', $filters['status']);
            }

            if ($filters['year']) {
                $monthlyQuery->where('year', $filters['year']);
            }

            if ($filters['month']) {
                $monthlyQuery->where('month', $filters['month']);
            }

            $monthlyCloses = $monthlyQuery->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->paginate(15);
        }

        return response()->json([
            'daily_closes' => $dailyCloses,
            'monthly_closes' => $monthlyCloses,
            'filters' => $filters,
        ]);
    }
}
