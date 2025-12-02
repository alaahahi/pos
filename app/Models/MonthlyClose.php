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

class MonthlyClose extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'total_sales_usd',
        'total_sales_iqd',
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

        // Get main box user
        $userAccount = UserType::where('name', 'account')->first();
        $mainBoxUser = User::with('wallet')
            ->where('type_id', $userAccount->id)
            ->where('email', 'mainBox@account.com')
            ->first();

        if (!$mainBoxUser || !$mainBoxUser->wallet) {
            return $this;
        }

        $walletId = $mainBoxUser->wallet->id;

        // Calculate sales (orders) for the month
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])->get();
        $this->total_orders = $orders->count();
        
        // Calculate sales amounts from transactions (orders payments)
        $salesTransactions = Transactions::where('wallet_id', $walletId)
            ->where('type', 'in')
            ->whereBetween('created', [$startDate, $endDate])
            ->where(function($query) {
                $query->where('morphed_type', Order::class)
                      ->orWhere('description', 'LIKE', '%طلب%')
                      ->orWhere('description', 'LIKE', '%order%');
            })
            ->get();

        $this->total_sales_usd = $salesTransactions->whereIn('currency', ['USD', '$'])->sum('amount');
        $this->total_sales_iqd = $salesTransactions->where('currency', 'IQD')->sum('amount');

        // Calculate expenses for the month
        $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->get();
        $this->total_expenses_usd = $expenses->where('currency', 'USD')->sum('amount');
        $this->total_expenses_iqd = $expenses->where('currency', 'IQD')->sum('amount');

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
            // If no previous month, calculate from all transactions before this month
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

        // Calculate closing balance
        $this->closing_balance_usd = $this->opening_balance_usd + $this->total_sales_usd - $this->total_expenses_usd;
        $this->closing_balance_iqd = $this->opening_balance_iqd + $this->total_sales_iqd - $this->total_expenses_iqd;

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
                'total_expenses_usd' => 0,
                'total_expenses_iqd' => 0,
            ]
        );
    }
}

