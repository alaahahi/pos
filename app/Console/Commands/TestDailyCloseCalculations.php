<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailyClose;
use App\Models\MonthlyClose;
use App\Models\Order;
use App\Models\Expense;
use App\Models\Transactions;
use App\Models\User;
use App\Models\UserType;
use Carbon\Carbon;

class TestDailyCloseCalculations extends Command
{
    protected $signature = 'test:daily-close';
    protected $description = 'Test daily and monthly close calculations';

    public function handle()
    {
        $this->info("========================================");
        $this->info("Daily & Monthly Close Calculation Test");
        $this->info("========================================\n");

        try {
            // Get main box user
            $userAccount = UserType::where('name', 'account')->first();
            if (!$userAccount) {
                throw new \Exception("UserType 'account' not found");
            }
            
            $mainBoxUser = User::with('wallet')
                ->where('type_id', $userAccount->id)
                ->where('email', 'mainBox@account.com')
                ->first();
            
            if (!$mainBoxUser || !$mainBoxUser->wallet) {
                throw new \Exception("Main box user or wallet not found");
            }
            
            $walletId = $mainBoxUser->wallet->id;
            $this->info("✓ Main box wallet found (ID: {$walletId})\n");
            
            // Test 1: Check today's transactions
            $today = Carbon::today();
            $startDate = $today->copy()->startOfDay();
            $endDate = $today->copy()->endOfDay();
            
            // Also check recent transactions (last 7 days) to see if there are any additions
            $recentStartDate = Carbon::today()->subDays(7)->startOfDay();
            $recentTransactions = Transactions::where('wallet_id', $walletId)
                ->where('type', 'in')
                ->whereBetween('created', [$recentStartDate, $endDate])
                ->get();
            
            $this->info("Recent Incoming Transactions (Last 7 Days): " . $recentTransactions->count());
            foreach ($recentTransactions->take(10) as $trans) {
                $transDate = Carbon::parse($trans->created)->format('Y-m-d');
                $this->info("  Date: {$transDate}, ID: {$trans->id}, Amount: {$trans->amount} {$trans->currency}, Type: {$trans->morphed_type}, Desc: " . ($trans->description ?? 'NULL'));
            }
            $this->info("");
            
            $this->info("Test 1: Today's Transactions Analysis");
            $this->info("--------------------------------------");
            $this->info("Date: {$today->format('Y-m-d')}\n");
            
            // Sales transactions
            $salesTransactions = Transactions::where('wallet_id', $walletId)
                ->where('type', 'in')
                ->whereBetween('created', [$startDate, $endDate])
                ->where(function($query) {
                    $query->where('morphed_type', Order::class)
                          ->orWhere('description', 'LIKE', '%فاتورة%')
                          ->orWhere('description', 'LIKE', '%invoice%');
                })
                ->get();
            
            $this->info("Sales Transactions (Orders): " . $salesTransactions->count());
            $this->info("  USD: " . $salesTransactions->whereIn('currency', ['USD', '$'])->sum('amount'));
            $this->info("  IQD: " . $salesTransactions->where('currency', 'IQD')->sum('amount') . "\n");
            
            // Additional income (deposits/additions) - using same logic as DailyClose
            // Get ALL incoming transactions first, then exclude sales
            $allIncomingTransactions = Transactions::where('wallet_id', $walletId)
                ->where('type', 'in')
                ->whereBetween('created', [$startDate, $endDate])
                ->get();
            
            // Get sales transaction IDs to exclude
            $salesTransactionIds = $salesTransactions->pluck('id')->toArray();
            
            // Filter out sales transactions - keep only additional income (same logic as DailyClose)
            $additionalIncome = $allIncomingTransactions->reject(function($transaction) use ($salesTransactionIds) {
                // Exclude if it's a sales transaction
                if (in_array($transaction->id, $salesTransactionIds)) {
                    return true;
                }
                
                // Exclude if it's an order-related transaction
                if ($transaction->morphed_type === Order::class || $transaction->morphed_type === 'App\\Models\\Order') {
                    return true;
                }
                
                // Exclude if description contains sales-related keywords
                $description = $transaction->description ?? '';
                if (stripos($description, 'طلب') !== false || 
                    stripos($description, 'order') !== false ||
                    stripos($description, 'فاتورة') !== false ||
                    stripos($description, 'invoice') !== false ||
                    stripos($description, 'دفع نقدي') !== false ||
                    stripos($description, 'cash payment') !== false) {
                    return true;
                }
                
                // Include everything else (direct additions, receipts, etc.)
                return false;
            });
            
            $this->info("Additional Income (Deposits/Additions): " . $additionalIncome->count());
            $this->info("  USD: " . $additionalIncome->whereIn('currency', ['USD', '$'])->sum('amount'));
            $this->info("  IQD: " . $additionalIncome->where('currency', 'IQD')->sum('amount') . "\n");
            
            // Show all incoming transactions for debugging
            $this->info("All Incoming Transactions Today: " . $allIncomingTransactions->count());
            foreach ($allIncomingTransactions as $trans) {
                $this->info("  ID: {$trans->id}, Amount: {$trans->amount} {$trans->currency}, Type: {$trans->morphed_type}, Desc: " . ($trans->description ?? 'NULL'));
            }
            $this->info("");
            
            // Expenses from Expense table
            $expenses = Expense::whereDate('expense_date', $today)->get();
            $this->info("Expenses (from Expense table): " . $expenses->count());
            $this->info("  USD: " . $expenses->where('currency', 'USD')->sum('amount'));
            $this->info("  IQD: " . $expenses->where('currency', 'IQD')->sum('amount') . "\n");
            
            // Expenses from transactions
            $expenseTransactions = Transactions::where('wallet_id', $walletId)
                ->where('type', 'out')
                ->whereBetween('created', [$startDate, $endDate])
                ->where(function($query) {
                    $query->where('morphed_type', '!=', Order::class)
                          ->where('morphed_type', '!=', 'App\\Models\\Order')
                          ->where(function($q) {
                              $q->where('description', 'NOT LIKE', '%طلب%')
                                ->where('description', 'NOT LIKE', '%order%')
                                ->where('description', 'NOT LIKE', '%فاتورة%')
                                ->where('description', 'NOT LIKE', '%invoice%');
                          });
                })
                ->get();
            
            $this->info("Expenses (from transactions): " . $expenseTransactions->count());
            $this->info("  USD: " . abs($expenseTransactions->whereIn('currency', ['USD', '$'])->sum('amount')));
            $this->info("  IQD: " . abs($expenseTransactions->where('currency', 'IQD')->sum('amount')) . "\n");
            
            // Test 2: Calculate Daily Close
            $this->info("Test 2: Daily Close Calculation");
            $this->info("---------------------------------");
            
            $dailyClose = DailyClose::getToday();
            $dailyClose->calculateDailyData();
            
            $this->info("Total Sales:");
            $this->info("  USD: " . $dailyClose->total_sales_usd);
            $this->info("  IQD: " . $dailyClose->total_sales_iqd . "\n");
            
            $this->info("Direct Deposits:");
            $this->info("  USD: " . ($dailyClose->direct_deposits_usd ?? 0));
            $this->info("  IQD: " . ($dailyClose->direct_deposits_iqd ?? 0) . "\n");
            
            $this->info("Direct Withdrawals:");
            $this->info("  USD: " . ($dailyClose->direct_withdrawals_usd ?? 0));
            $this->info("  IQD: " . ($dailyClose->direct_withdrawals_iqd ?? 0) . "\n");
            
            $this->info("Total Expenses:");
            $this->info("  USD: " . $dailyClose->total_expenses_usd);
            $this->info("  IQD: " . $dailyClose->total_expenses_iqd . "\n");
            
            $this->info("Opening Balance:");
            $this->info("  USD: " . $dailyClose->opening_balance_usd);
            $this->info("  IQD: " . $dailyClose->opening_balance_iqd . "\n");
            
            $this->info("Closing Balance:");
            $this->info("  USD: " . $dailyClose->closing_balance_usd);
            $this->info("  IQD: " . $dailyClose->closing_balance_iqd . "\n");
            
            $this->info("Total Orders: " . $dailyClose->total_orders . "\n");
            
            // Check orders for today
            $todayOrders = Order::whereBetween('created_at', [$startDate, $endDate])->get();
            $this->info("Today's Orders Details:");
            foreach ($todayOrders as $order) {
                $this->info("  Order #{$order->id}: Total Amount: {$order->total_amount}, Total Paid: {$order->total_paid}, Currency: " . ($order->total_paid > 0 ? 'IQD (from fallback)' : 'N/A'));
            }
            $this->info("  Total Paid Sum: " . $todayOrders->sum('total_paid') . "\n");
            
            // Test 3: Monthly Close
            $this->info("Test 3: Monthly Close Calculation");
            $this->info("----------------------------------");
            
            $monthlyClose = MonthlyClose::getCurrentMonth();
            $monthlyClose->calculateMonthlyData();
            
            $this->info("Total Sales (Month):");
            $this->info("  USD: " . $monthlyClose->total_sales_usd);
            $this->info("  IQD: " . $monthlyClose->total_sales_iqd . "\n");
            
            $this->info("Direct Deposits (Month):");
            $this->info("  USD: " . ($monthlyClose->direct_deposits_usd ?? 0));
            $this->info("  IQD: " . ($monthlyClose->direct_deposits_iqd ?? 0) . "\n");
            
            $this->info("Direct Withdrawals (Month):");
            $this->info("  USD: " . ($monthlyClose->direct_withdrawals_usd ?? 0));
            $this->info("  IQD: " . ($monthlyClose->direct_withdrawals_iqd ?? 0) . "\n");
            
            $this->info("Total Expenses (Month):");
            $this->info("  USD: " . $monthlyClose->total_expenses_usd);
            $this->info("  IQD: " . $monthlyClose->total_expenses_iqd . "\n");
            
            $this->info("Total Orders (Month): " . $monthlyClose->total_orders . "\n");
            
            // Test 4: Verification
            $this->info("Test 4: Verification");
            $this->info("--------------------");
            
            $expectedSalesUsd = $salesTransactions->whereIn('currency', ['USD', '$'])->sum('amount') + 
                               $additionalIncome->whereIn('currency', ['USD', '$'])->sum('amount');
            $expectedSalesIqd = $salesTransactions->where('currency', 'IQD')->sum('amount') + 
                               $additionalIncome->where('currency', 'IQD')->sum('amount');
            
            $expectedExpensesUsd = $expenses->where('currency', 'USD')->sum('amount') + 
                                  abs($expenseTransactions->whereIn('currency', ['USD', '$'])->sum('amount'));
            $expectedExpensesIqd = $expenses->where('currency', 'IQD')->sum('amount') + 
                                  abs($expenseTransactions->where('currency', 'IQD')->sum('amount'));
            
            $this->info("Expected Sales:");
            $this->info("  USD: " . $expectedSalesUsd . " (Actual: " . $dailyClose->total_sales_usd . ")");
            $this->info("  IQD: " . $expectedSalesIqd . " (Actual: " . $dailyClose->total_sales_iqd . ")");
            
            if (abs($expectedSalesUsd - $dailyClose->total_sales_usd) < 0.01 && 
                abs($expectedSalesIqd - $dailyClose->total_sales_iqd) < 0.01) {
                $this->info("  ✓ Sales calculation is correct!\n");
            } else {
                $this->error("  ✗ Sales calculation mismatch!\n");
            }
            
            $this->info("Expected Expenses:");
            $this->info("  USD: " . $expectedExpensesUsd . " (Actual: " . $dailyClose->total_expenses_usd . ")");
            $this->info("  IQD: " . $expectedExpensesIqd . " (Actual: " . $dailyClose->total_expenses_iqd . ")");
            
            if (abs($expectedExpensesUsd - $dailyClose->total_expenses_usd) < 0.01 && 
                abs($expectedExpensesIqd - $dailyClose->total_expenses_iqd) < 0.01) {
                $this->info("  ✓ Expenses calculation is correct!\n");
            } else {
                $this->error("  ✗ Expenses calculation mismatch!\n");
            }
            
            $this->info("========================================");
            $this->info("Test completed successfully!");
            $this->info("========================================");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("ERROR: " . $e->getMessage());
            $this->error("File: " . $e->getFile());
            $this->error("Line: " . $e->getLine());
            return 1;
        }
    }
}

