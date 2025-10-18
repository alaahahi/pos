<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MonthlyAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'status', // open, closed
        'total_orders',
        'total_orders_amount',
        'total_payments_received',
        'total_payments_received_usd',
        'total_payments_received_iqd',
        'total_payments_paid',
        'total_balance_dollar',
        'total_balance_dinar',
        'net_profit_dollar',
        'net_profit_dinar',
        'opening_balance_dollar',
        'opening_balance_dinar',
        'closing_balance_dollar',
        'closing_balance_dinar',
        'notes',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'total_orders_amount' => 'decimal:2',
        'total_payments_received' => 'decimal:2',
        'total_payments_received_usd' => 'decimal:2',
        'total_payments_received_iqd' => 'decimal:2',
        'total_payments_paid' => 'decimal:2',
        'total_balance_dollar' => 'decimal:2',
        'total_balance_dinar' => 'decimal:2',
        'net_profit_dollar' => 'decimal:2',
        'net_profit_dinar' => 'decimal:2',
        'opening_balance_dollar' => 'decimal:2',
        'opening_balance_dinar' => 'decimal:2',
        'closing_balance_dollar' => 'decimal:2',
        'closing_balance_dinar' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    // Scopes
    public function scopeCurrentMonth($query)
    {
        return $query->where('year', now()->year)
                    ->where('month', now()->month);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
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

    public function getStatusNameAttribute()
    {
        return $this->status === 'open' ? 'مفتوح' : 'مغلق';
    }

    public function getStatusColorAttribute()
    {
        return $this->status === 'open' ? 'success' : 'secondary';
    }

    // Methods
    public function calculateMonthlyData()
    {
        $startDate = now()->setYear($this->year)->setMonth($this->month)->startOfMonth();
        $endDate = now()->setYear($this->year)->setMonth($this->month)->endOfMonth();

        // Calculate decoration orders
        $orders = DecorationOrder::whereBetween('created_at', [$startDate, $endDate])->get();
        
        $this->total_orders = $orders->count();
        $this->total_orders_amount = $orders->sum('total_price');

        // Calculate payments received (from customers)
        $paymentsReceived = Box::where('type', 'payment')
            ->whereBetween('created', [$startDate, $endDate])
            ->where('morphed_type', DecorationOrder::class)
            ->get();

        $this->total_payments_received_usd = $paymentsReceived->where('currency', 'USD')->sum('amount');
        $this->total_payments_received_iqd = $paymentsReceived->where('currency', 'IQD')->sum('amount');
        $this->total_payments_received = $this->total_payments_received_usd + $this->total_payments_received_iqd;

        // Calculate balance transactions
        $balanceTransactions = Box::whereIn('type', ['deposit', 'withdrawal'])
            ->whereBetween('created', [$startDate, $endDate])
            ->where('morphed_type', Customer::class)
            ->get();

        $deposits = $balanceTransactions->where('type', 'deposit');
        $withdrawals = $balanceTransactions->where('type', 'withdrawal');

        $this->total_balance_dollar = $deposits->where('currency', 'USD')->sum('amount') - 
                                     $withdrawals->where('currency', 'USD')->sum('amount');
        
        $this->total_balance_dinar = $deposits->where('currency', 'IQD')->sum('amount') - 
                                    $withdrawals->where('currency', 'IQD')->sum('amount');

        // Calculate net profit
        $this->net_profit_dollar = $this->total_payments_received_usd - $this->total_balance_dollar;
        $this->net_profit_dinar = $this->total_payments_received_iqd - $this->total_balance_dinar;

        return $this;
    }

    public function closeMonth($userId = null)
    {
        if ($this->status === 'closed') {
            throw new \Exception('الشهر مغلق بالفعل');
        }

        // Calculate final data
        $this->calculateMonthlyData();

        // Set closing balances (same as opening for next month)
        $this->closing_balance_dollar = $this->opening_balance_dollar + $this->total_balance_dollar;
        $this->closing_balance_dinar = $this->opening_balance_dinar + $this->total_balance_dinar;

        // Close the month
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $userId ?? auth()->id(),
        ]);

        // Create next month if it doesn't exist
        $nextMonth = $this->month === 12 ? 1 : $this->month + 1;
        $nextYear = $this->month === 12 ? $this->year + 1 : $this->year;

        MonthlyAccount::firstOrCreate(
            [
                'year' => $nextYear,
                'month' => $nextMonth,
            ],
            [
                'status' => 'open',
                'opening_balance_dollar' => $this->closing_balance_dollar,
                'opening_balance_dinar' => $this->closing_balance_dinar,
            ]
        );

        return $this;
    }

    // Static methods
    public static function getCurrentMonth()
    {
        return static::firstOrCreate(
            [
                'year' => now()->year,
                'month' => now()->month,
            ],
            [
                'status' => 'open',
                'opening_balance_dollar' => 0,
                'opening_balance_dinar' => 0,
            ]
        );
    }

    public static function getMonthlyReport($year, $month)
    {
        $monthlyAccount = static::where('year', $year)
            ->where('month', $month)
            ->first();

        if (!$monthlyAccount) {
            return null;
        }

        $startDate = now()->setYear($year)->setMonth($month)->startOfMonth();
        $endDate = now()->setYear($year)->setMonth($month)->endOfMonth();

        // Get detailed data
        $orders = DecorationOrder::with(['decoration', 'customer'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $payments = Box::with('morphed')
            ->where('type', 'payment')
            ->whereBetween('created', [$startDate, $endDate])
            ->where('morphed_type', DecorationOrder::class)
            ->get();

        $balanceTransactions = Box::with('morphed')
            ->whereIn('type', ['deposit', 'withdrawal'])
            ->whereBetween('created', [$startDate, $endDate])
            ->where('morphed_type', Customer::class)
            ->get();

        return [
            'monthly_account' => $monthlyAccount,
            'orders' => $orders,
            'payments' => $payments,
            'balance_transactions' => $balanceTransactions,
        ];
    }
}
