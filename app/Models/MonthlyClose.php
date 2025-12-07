<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserType;
use App\Models\Order;
use App\Models\Expense;
use App\Models\Transactions;
use App\Models\DailyClose;

class MonthlyClose extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'total_sales_usd',
        'total_sales_iqd',
        'direct_deposits_usd',
        'direct_deposits_iqd',
        'direct_withdrawals_usd',
        'direct_withdrawals_iqd',
        'total_expenses_usd',
        'total_expenses_iqd',
        'opening_balance_usd',
        'opening_balance_iqd',
        'closing_balance_usd',
        'closing_balance_iqd',
        'total_orders',
        'notes',
        'status',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'total_sales_usd' => 'decimal:2',
        'total_sales_iqd' => 'decimal:2',
        'direct_deposits_usd' => 'decimal:2',
        'direct_deposits_iqd' => 'decimal:2',
        'direct_withdrawals_usd' => 'decimal:2',
        'direct_withdrawals_iqd' => 'decimal:2',
        'total_expenses_usd' => 'decimal:2',
        'total_expenses_iqd' => 'decimal:2',
        'opening_balance_usd' => 'decimal:2',
        'opening_balance_iqd' => 'decimal:2',
        'closing_balance_usd' => 'decimal:2',
        'closing_balance_iqd' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    // Relationships
    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeCurrentMonth($query)
    {
        return $query->where('year', now()->year)
                    ->where('month', now()->month);
    }

    // Accessors
    public function getMonthNameAttribute()
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
        ];

        return $months[$this->month] ?? $this->month;
    }

    // Methods
    public function calculateMonthlyData()
    {
        $startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endDate = Carbon::create($this->year, $this->month, 1)->endOfMonth();
        $today = Carbon::today();

        // Get today's daily close if it's within the month (may be open or closed)
        $todayDailyClose = null;
        if ($today->year == $this->year && $today->month == $this->month) {
            $todayDailyClose = DailyClose::whereDate('close_date', $today)->first();
            if ($todayDailyClose) {
                // Recalculate today's data to ensure it's up to date
                $todayDailyClose->calculateDailyData();
            }
        }

        // Get all daily closes for the month (closed days only)
        // Exclude today if it exists to avoid double counting
        $closedDailyClosesQuery = DailyClose::whereBetween('close_date', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->where('status', 'closed');
        
        // Exclude today's close if it exists (whether open or closed) to avoid double counting
        // We only count closed days, and if today is closed, it will be counted separately
        if ($todayDailyClose) {
            $closedDailyClosesQuery->whereDate('close_date', '!=', $today);
        }
        
        $closedDailyCloses = $closedDailyClosesQuery->get();

        // Sum sales from closed daily closes (excluding today)
        $totalSalesUsd = $closedDailyCloses->sum('total_sales_usd');
        $totalSalesIqd = $closedDailyCloses->sum('total_sales_iqd');
        $totalDirectDepositsUsd = $closedDailyCloses->sum('direct_deposits_usd');
        $totalDirectDepositsIqd = $closedDailyCloses->sum('direct_deposits_iqd');
        $totalDirectWithdrawalsUsd = $closedDailyCloses->sum('direct_withdrawals_usd');
        $totalDirectWithdrawalsIqd = $closedDailyCloses->sum('direct_withdrawals_iqd');
        $totalExpensesUsd = $closedDailyCloses->sum('total_expenses_usd');
        $totalExpensesIqd = $closedDailyCloses->sum('total_expenses_iqd');
        $totalOrders = $closedDailyCloses->sum('total_orders');

        // Add today's data ONLY if today is closed (to include it in monthly calculation)
        // If today is open, it should not be included in monthly close
        if ($todayDailyClose && $todayDailyClose->status === 'closed') {
            $totalSalesUsd += $todayDailyClose->total_sales_usd;
            $totalSalesIqd += $todayDailyClose->total_sales_iqd;
            $totalDirectDepositsUsd += $todayDailyClose->direct_deposits_usd;
            $totalDirectDepositsIqd += $todayDailyClose->direct_deposits_iqd;
            $totalDirectWithdrawalsUsd += $todayDailyClose->direct_withdrawals_usd;
            $totalDirectWithdrawalsIqd += $todayDailyClose->direct_withdrawals_iqd;
            $totalExpensesUsd += $todayDailyClose->total_expenses_usd;
            $totalExpensesIqd += $todayDailyClose->total_expenses_iqd;
            $totalOrders += $todayDailyClose->total_orders;
        }

        $this->total_sales_usd = $totalSalesUsd;
        $this->total_sales_iqd = $totalSalesIqd;
        $this->direct_deposits_usd = $totalDirectDepositsUsd;
        $this->direct_deposits_iqd = $totalDirectDepositsIqd;
        $this->direct_withdrawals_usd = $totalDirectWithdrawalsUsd;
        $this->direct_withdrawals_iqd = $totalDirectWithdrawalsIqd;
        $this->total_expenses_usd = $totalExpensesUsd;
        $this->total_expenses_iqd = $totalExpensesIqd;
        $this->total_orders = $totalOrders;

        // Get opening balance (from previous month's closing balance)
        $previousMonth = MonthlyClose::where(function($query) {
            if ($this->month == 1) {
                $query->where('year', $this->year - 1)->where('month', 12);
            } else {
                $query->where('year', $this->year)->where('month', $this->month - 1);
            }
        })
        ->where('status', 'closed')
        ->first();

        if ($previousMonth) {
            $this->opening_balance_usd = $previousMonth->closing_balance_usd;
            $this->opening_balance_iqd = $previousMonth->closing_balance_iqd;
        } else {
            // If no previous month, get opening balance from first day of month's daily close
            $firstDayClose = DailyClose::whereDate('close_date', $startDate)->first();
            if ($firstDayClose) {
                $this->opening_balance_usd = $firstDayClose->opening_balance_usd;
                $this->opening_balance_iqd = $firstDayClose->opening_balance_iqd;
            } else {
                // Fallback: calculate from all transactions before this month
                $userAccount = UserType::where('name', 'account')->first();
                $mainBoxUser = User::with('wallet')
                    ->where('type_id', $userAccount->id)
                    ->where('email', 'mainBox@account.com')
                    ->first();

                if ($mainBoxUser && $mainBoxUser->wallet) {
                    $walletId = $mainBoxUser->wallet->id;
                    $openingTransactionsUSD = Transactions::where('wallet_id', $walletId)
                        ->where(function($query) {
                            $query->where('currency', 'USD')->orWhere('currency', '$');
                        })
                        ->where('created', '<', $startDate)
                        ->sum('amount');

                    $openingTransactionsIQD = Transactions::where('wallet_id', $walletId)
                        ->where('currency', 'IQD')
                        ->where('created', '<', $startDate)
                        ->sum('amount');

                    $this->opening_balance_usd = $openingTransactionsUSD;
                    $this->opening_balance_iqd = $openingTransactionsIQD;
                }
            }
        }

        // Calculate closing balance
        // Calculate closing balance
        // Closing = Opening + Sales + Direct Deposits - Expenses - Direct Withdrawals
        $this->closing_balance_usd = $this->opening_balance_usd + $this->total_sales_usd + $this->direct_deposits_usd - $this->total_expenses_usd - $this->direct_withdrawals_usd;
        $this->closing_balance_iqd = $this->opening_balance_iqd + $this->total_sales_iqd + $this->direct_deposits_iqd - $this->total_expenses_iqd - $this->direct_withdrawals_iqd;

        return $this;
    }

    public function close($userId = null)
    {
        if ($this->status === 'closed') {
            throw new \Exception('الشهر مغلق بالفعل');
        }

        // Calculate final data
        $this->calculateMonthlyData();

        // Close the month
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $userId ?? auth()->id(),
        ]);

        // Create next month if it doesn't exist
        $nextMonth = $this->month === 12 ? 1 : $this->month + 1;
        $nextYear = $this->month === 12 ? $this->year + 1 : $this->year;

        MonthlyClose::firstOrCreate(
            [
                'year' => $nextYear,
                'month' => $nextMonth,
            ],
            [
                'status' => 'open',
                'opening_balance_usd' => $this->closing_balance_usd,
                'opening_balance_iqd' => $this->closing_balance_iqd,
            ]
        );

        return $this;
    }

    public static function getCurrentMonth()
    {
        return static::firstOrCreate(
            [
                'year' => now()->year,
                'month' => now()->month,
            ],
            [
                'status' => 'open',
                'total_sales_usd' => 0,
                'total_sales_iqd' => 0,
                'direct_deposits_usd' => 0,
                'direct_deposits_iqd' => 0,
                'direct_withdrawals_usd' => 0,
                'direct_withdrawals_iqd' => 0,
                'total_expenses_usd' => 0,
                'total_expenses_iqd' => 0,
            ]
        );
    }
}

