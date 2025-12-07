<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailyClose;
use App\Models\Order;
use App\Models\Transactions;
use App\Models\Expense;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;
use Carbon\Carbon;

class TestDailyCloseCalculation extends Command
{
    protected $signature = 'test:daily-close-calculation {date?}';
    protected $description = 'Test daily close calculation and verify all numbers are correct';

    public function handle()
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : Carbon::today();
        $dateString = $date->format('Y-m-d');
        
        $this->info("Testing Daily Close Calculation for: {$dateString}");
        $this->info(str_repeat('=', 60));
        
        // Get main box user
        $userAccount = UserType::where('name', 'account')->first();
        if (!$userAccount) {
            $this->error('UserType "account" not found!');
            return 1;
        }
        
        $mainBoxUser = User::with('wallet')
            ->where('type_id', $userAccount->id)
            ->where('email', 'mainBox@account.com')
            ->first();
            
        if (!$mainBoxUser || !$mainBoxUser->wallet) {
            $this->error('Main box user or wallet not found!');
            return 1;
        }
        
        $walletId = $mainBoxUser->wallet->id;
        $this->info("Wallet ID: {$walletId}");
        $this->info("");
        
        // Get or create daily close
        $dailyClose = DailyClose::firstOrCreate(
            ['close_date' => $dateString],
            ['status' => 'open']
        );
        
        // Calculate data
        $dailyClose->calculateDailyData();
        $dailyClose->save();
        
        $this->info("=== CALCULATED VALUES ===");
        $this->info("Total Orders: {$dailyClose->total_orders}");
        $this->info("Total Sales USD: {$dailyClose->total_sales_usd}");
        $this->info("Total Sales IQD: {$dailyClose->total_sales_iqd}");
        $this->info("Direct Deposits USD: {$dailyClose->direct_deposits_usd}");
        $this->info("Direct Deposits IQD: {$dailyClose->direct_deposits_iqd}");
        $this->info("Total Expenses USD: {$dailyClose->total_expenses_usd}");
        $this->info("Total Expenses IQD: {$dailyClose->total_expenses_iqd}");
        $this->info("Direct Withdrawals USD: {$dailyClose->direct_withdrawals_usd}");
        $this->info("Direct Withdrawals IQD: {$dailyClose->direct_withdrawals_iqd}");
        $this->info("Opening Balance USD: {$dailyClose->opening_balance_usd}");
        $this->info("Opening Balance IQD: {$dailyClose->opening_balance_iqd}");
        $this->info("Closing Balance USD: {$dailyClose->closing_balance_usd}");
        $this->info("Closing Balance IQD: {$dailyClose->closing_balance_iqd}");
        $this->info("");
        
        // Manual verification
        $this->info("=== MANUAL VERIFICATION ===");
        
        // Verify sales
        $orders = Order::whereBetween('created_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])->get();
        $this->info("Orders found: {$orders->count()}");
        
        // Method 1: By morphed_type
        $salesTransactionsByOrder = Transactions::where('wallet_id', $walletId)
            ->where('type', 'in')
            ->whereDate('created', $dateString)
            ->where(function($query) {
                $query->where('morphed_type', Order::class)
                      ->orWhere('morphed_type', 'App\\Models\\Order');
            })
            ->get();
        
        // Method 2: By description
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
        
        $manualSalesUsd = $allSalesTransactions->whereIn('currency', ['USD', '$'])->sum('amount');
        $manualSalesIqd = $allSalesTransactions->where('currency', 'IQD')->sum('amount');
        
        $this->info("Sales Transactions (by morphed_type): {$salesTransactionsByOrder->count()}");
        $this->info("Sales Transactions (by description): {$salesTransactionsByDescription->count()}");
        $this->info("Total Sales Transactions (unique): {$allSalesTransactions->count()}");
        $this->info("Sales Transactions USD: {$manualSalesUsd}");
        $this->info("Sales Transactions IQD: {$manualSalesIqd}");
        
        // Show fallback calculation if no transactions found
        if ($allSalesTransactions->isEmpty() && $orders->count() > 0) {
            $ordersTotalPaid = $orders->sum('total_paid');
            $this->info("No sales transactions found, using orders.total_paid as fallback: {$ordersTotalPaid}");
        }
        
        // Verify direct deposits
        $allIncoming = Transactions::where('wallet_id', $walletId)
            ->where('type', 'in')
            ->whereDate('created', $dateString)
            ->get();
        
        $salesIds = $allSalesTransactions->pluck('id')->toArray();
        $directDeposits = $allIncoming->reject(function($t) use ($salesIds) {
            if (in_array($t->id, $salesIds)) return true;
            if ($t->morphed_type === Order::class || $t->morphed_type === 'App\\Models\\Order') return true;
            $desc = $t->description ?? '';
            if (stripos($desc, 'طلب') !== false || stripos($desc, 'order') !== false ||
                stripos($desc, 'فاتورة') !== false || stripos($desc, 'invoice') !== false ||
                stripos($desc, 'دفع نقدي') !== false || stripos($desc, 'cash payment') !== false) {
                return true;
            }
            return false;
        });
        
        $manualDepositsUsd = $directDeposits->whereIn('currency', ['USD', '$'])->sum('amount');
        $manualDepositsIqd = $directDeposits->where('currency', 'IQD')->sum('amount');
        
        $this->info("Direct Deposits USD: {$manualDepositsUsd}");
        $this->info("Direct Deposits IQD: {$manualDepositsIqd}");
        
        // Verify expenses
        $expenses = Expense::whereDate('expense_date', $dateString)->get();
        $expenseTransactions = Transactions::where('wallet_id', $walletId)
            ->where('type', 'out')
            ->whereDate('created', $dateString)
            ->where('morphed_type', Expense::class)
            ->get();
        
        $manualExpensesUsd = $expenses->where('currency', 'USD')->sum('amount') + 
                            abs($expenseTransactions->whereIn('currency', ['USD', '$'])->sum('amount'));
        $manualExpensesIqd = $expenses->where('currency', 'IQD')->sum('amount') + 
                            abs($expenseTransactions->where('currency', 'IQD')->sum('amount'));
        
        $this->info("Expenses USD: {$manualExpensesUsd}");
        $this->info("Expenses IQD: {$manualExpensesIqd}");
        
        // Verify closing balance calculation
        $manualClosingUsd = $dailyClose->opening_balance_usd + 
                           $dailyClose->total_sales_usd + 
                           $dailyClose->direct_deposits_usd - 
                           $dailyClose->total_expenses_usd - 
                           $dailyClose->direct_withdrawals_usd;
        
        $manualClosingIqd = $dailyClose->opening_balance_iqd + 
                           $dailyClose->total_sales_iqd + 
                           $dailyClose->direct_deposits_iqd - 
                           $dailyClose->total_expenses_iqd - 
                           $dailyClose->direct_withdrawals_iqd;
        
        $this->info("");
        $this->info("=== VERIFICATION RESULTS ===");
        
        $errors = [];
        
        if (abs($dailyClose->total_sales_usd - $manualSalesUsd) > 0.01) {
            $errors[] = "Sales USD mismatch: Calculated={$dailyClose->total_sales_usd}, Manual={$manualSalesUsd}";
        }
        
        if (abs($dailyClose->total_sales_iqd - $manualSalesIqd) > 0.01) {
            $errors[] = "Sales IQD mismatch: Calculated={$dailyClose->total_sales_iqd}, Manual={$manualSalesIqd}";
        }
        
        if (abs($dailyClose->direct_deposits_usd - $manualDepositsUsd) > 0.01) {
            $errors[] = "Direct Deposits USD mismatch: Calculated={$dailyClose->direct_deposits_usd}, Manual={$manualDepositsUsd}";
        }
        
        if (abs($dailyClose->direct_deposits_iqd - $manualDepositsIqd) > 0.01) {
            $errors[] = "Direct Deposits IQD mismatch: Calculated={$dailyClose->direct_deposits_iqd}, Manual={$manualDepositsIqd}";
        }
        
        if (abs($dailyClose->total_expenses_usd - $manualExpensesUsd) > 0.01) {
            $errors[] = "Expenses USD mismatch: Calculated={$dailyClose->total_expenses_usd}, Manual={$manualExpensesUsd}";
        }
        
        if (abs($dailyClose->total_expenses_iqd - $manualExpensesIqd) > 0.01) {
            $errors[] = "Expenses IQD mismatch: Calculated={$dailyClose->total_expenses_iqd}, Manual={$manualExpensesIqd}";
        }
        
        if (abs($dailyClose->closing_balance_usd - $manualClosingUsd) > 0.01) {
            $errors[] = "Closing Balance USD mismatch: Calculated={$dailyClose->closing_balance_usd}, Manual={$manualClosingUsd}";
        }
        
        if (abs($dailyClose->closing_balance_iqd - $manualClosingIqd) > 0.01) {
            $errors[] = "Closing Balance IQD mismatch: Calculated={$dailyClose->closing_balance_iqd}, Manual={$manualClosingIqd}";
        }
        
        if (empty($errors)) {
            $this->info("✓ All calculations are correct!");
            return 0;
        } else {
            $this->error("✗ Found " . count($errors) . " error(s):");
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
            return 1;
        }
    }
}

