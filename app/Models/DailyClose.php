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
        'close_date' => 'date',
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

    public function scopeToday($query)
    {
        return $query->whereDate('close_date', today());
    }

    // Methods
    public function calculateDailyData()
    {
        $date = $this->close_date ?? today();
        $dateString = Carbon::parse($date)->format('Y-m-d');
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
        // Method 1: Find transactions by morphed_type (most accurate)
        // Use whereDate since 'created' is a date field, not datetime
        $salesTransactionsByOrder = Transactions::where('wallet_id', $walletId)
            ->where('type', 'in')
            ->whereDate('created', $dateString)
            ->where(function($query) {
                $query->where('morphed_type', Order::class)
                      ->orWhere('morphed_type', 'App\\Models\\Order');
            })
            ->get();
        
        // Method 2: Find transactions by description (fallback for old transactions)
        $salesTransactionsByDescription = Transactions::where('wallet_id', $walletId)
            ->where('type', 'in')
            ->whereDate('created', $dateString)
            ->where(function($query) {
                $query->where('description', 'LIKE', '%طلب%')
                      ->orWhere('description', 'LIKE', '%order%')
                      ->orWhere('description', 'LIKE', '%فاتورة%')
                      ->orWhere('description', 'LIKE', '%invoice%');
            })
            ->get();
        
        // Combine both methods and remove duplicates
        $allSalesTransactions = $salesTransactionsByOrder->merge($salesTransactionsByDescription)->unique('id');

        $this->total_sales_usd = $allSalesTransactions->whereIn('currency', ['USD', '$'])->sum('amount');
        $this->total_sales_iqd = $allSalesTransactions->where('currency', 'IQD')->sum('amount');
        
        // If no transactions found at all but orders exist, calculate from orders total_paid as fallback
        // Only use fallback if we found NO sales transactions (not just zero amounts)
        if ($allSalesTransactions->isEmpty() && $orders->count() > 0) {
            $ordersTotalPaid = $orders->sum('total_paid');
            if ($ordersTotalPaid > 0) {
                // Get default currency from environment
                $defaultCurrency = env('DEFAULT_CURRENCY', 'IQD');
                if ($defaultCurrency === 'USD' || $defaultCurrency === '$') {
                    $this->total_sales_usd = $ordersTotalPaid;
                } else {
                    $this->total_sales_iqd = $ordersTotalPaid;
                }
            }
        }

        // Calculate expenses for the day
        // Only from transactions (Expense model transactions)
        // Note: Each expense creates both an Expense record AND a transaction.
        // We only count transactions to avoid double counting.
        // Direct withdrawals are calculated separately, so we only count Expense model transactions here
        // Use whereDate since 'created' is a date field, not datetime
        $expenseModelTransactions = Transactions::where('wallet_id', $walletId)
            ->where('type', 'out')
            ->whereDate('created', $dateString)
            ->where('morphed_type', Expense::class)
            ->get();
        
        // Sum expenses from Expense model transactions (note: out transactions have negative amounts, so we use abs)
        $this->total_expenses_usd = abs($expenseModelTransactions->whereIn('currency', ['USD', '$'])->sum('amount'));
        $this->total_expenses_iqd = abs($expenseModelTransactions->where('currency', 'IQD')->sum('amount'));
        
        // Calculate additional income (deposits that are not sales)
        // Get ALL incoming transactions first, then exclude sales
        // Use whereDate since 'created' is a date field, not datetime
        $allIncomingTransactions = Transactions::where('wallet_id', $walletId)
            ->where('type', 'in')
            ->whereDate('created', $dateString)
            ->get();
        
        // Get sales transaction IDs to exclude
        $salesTransactionIds = $allSalesTransactions->pluck('id')->toArray();
        
        // Filter out sales transactions - keep only additional income
        // reject() removes items that return true, so we return true to EXCLUDE sales
        $additionalIncomeTransactions = $allIncomingTransactions->reject(function($transaction) use ($salesTransactionIds) {
            // Exclude if it's a sales transaction (already identified)
            if (in_array($transaction->id, $salesTransactionIds)) {
                return true; // EXCLUDE this transaction
            }
            
            // Exclude if it's an order-related transaction
            if ($transaction->morphed_type === Order::class || $transaction->morphed_type === 'App\\Models\\Order') {
                return true; // EXCLUDE this transaction
            }
            
            // Exclude if description contains sales-related keywords
            $description = $transaction->description ?? '';
            if (stripos($description, 'طلب') !== false || 
                stripos($description, 'order') !== false ||
                stripos($description, 'فاتورة') !== false ||
                stripos($description, 'invoice') !== false ||
                stripos($description, 'دفع نقدي') !== false ||
                stripos($description, 'cash payment') !== false) {
                return true; // EXCLUDE this transaction
            }
            
            // Include everything else (direct additions, receipts, empty descriptions, etc.)
            return false; // KEEP this transaction
        });
        
        // Calculate direct deposits (additions to box) - separate from sales
        $directDepositsUsd = $additionalIncomeTransactions->whereIn('currency', ['USD', '$'])->sum('amount');
        $directDepositsIqd = $additionalIncomeTransactions->where('currency', 'IQD')->sum('amount');
        
        $this->direct_deposits_usd = $directDepositsUsd;
        $this->direct_deposits_iqd = $directDepositsIqd;
        
        // Calculate direct withdrawals (from box) - separate from expenses
        // Use whereDate since 'created' is a date field, not datetime
        $directWithdrawalTransactions = Transactions::where('wallet_id', $walletId)
            ->where('type', 'out')
            ->whereDate('created', $dateString)
            ->where(function($query) {
                // Exclude expenses and sales-related transactions
                $query->where('morphed_type', '!=', Order::class)
                      ->where('morphed_type', '!=', 'App\\Models\\Order')
                      ->where('morphed_type', '!=', Expense::class)
                      ->where(function($q) {
                          $q->where('description', 'NOT LIKE', '%طلب%')
                            ->where('description', 'NOT LIKE', '%order%')
                            ->where('description', 'NOT LIKE', '%فاتورة%')
                            ->where('description', 'NOT LIKE', '%invoice%')
                            ->where('description', 'NOT LIKE', '%مصروف%')
                            ->where('description', 'NOT LIKE', '%expense%');
                      });
            })
            ->get();
        
        // Direct withdrawals (note: out transactions have negative amounts, so we use abs)
        $directWithdrawalsUsd = abs($directWithdrawalTransactions->whereIn('currency', ['USD', '$'])->sum('amount'));
        $directWithdrawalsIqd = abs($directWithdrawalTransactions->where('currency', 'IQD')->sum('amount'));
        
        $this->direct_withdrawals_usd = $directWithdrawalsUsd;
        $this->direct_withdrawals_iqd = $directWithdrawalsIqd;

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
        // Calculate closing balance
        // Closing = Opening + Sales + Direct Deposits - Expenses - Direct Withdrawals
        $this->closing_balance_usd = $this->opening_balance_usd + $this->total_sales_usd + $this->direct_deposits_usd - $this->total_expenses_usd - $this->direct_withdrawals_usd;
        $this->closing_balance_iqd = $this->opening_balance_iqd + $this->total_sales_iqd + $this->direct_deposits_iqd - $this->total_expenses_iqd - $this->direct_withdrawals_iqd;

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

