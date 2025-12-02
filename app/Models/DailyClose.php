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

class DailyClose extends Model
{
    use HasFactory;

    protected $fillable = [
        'close_date',
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
        'close_date' => 'date',
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

    public function scopeToday($query)
    {
        return $query->whereDate('close_date', today());
    }

    // Methods
    public function calculateDailyData()
    {
        $date = $this->close_date ?? today();
        $startDate = Carbon::parse($date)->startOfDay();
        $endDate = Carbon::parse($date)->endOfDay();

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

        // Calculate sales (orders) for the day
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

        // Calculate expenses for the day
        $expenses = Expense::whereDate('expense_date', $date)->get();
        $this->total_expenses_usd = $expenses->where('currency', 'USD')->sum('amount');
        $this->total_expenses_iqd = $expenses->where('currency', 'IQD')->sum('amount');

        // Get opening balance (from previous day's closing balance)
        $previousDay = DailyClose::where('close_date', '<', $date)
            ->where('status', 'closed')
            ->orderBy('close_date', 'desc')
            ->first();

        if ($previousDay) {
            $this->opening_balance_usd = $previousDay->closing_balance_usd;
            $this->opening_balance_iqd = $previousDay->closing_balance_iqd;
        } else {
            // If no previous day, calculate from all transactions before this date
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
            throw new \Exception('اليوم مغلق بالفعل');
        }

        // Calculate final data
        $this->calculateDailyData();

        // Close the day
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $userId ?? auth()->id(),
        ]);

        return $this;
    }

    public static function getToday()
    {
        return static::firstOrCreate(
            ['close_date' => today()],
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

