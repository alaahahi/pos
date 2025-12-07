<?php

/**
 * Test Script for Daily Close Calculations
 * 
 * This script tests:
 * 1. Sales calculations (from orders)
 * 2. Additional income (deposits/additions to box)
 * 3. Expenses calculations (from Expense table + transactions)
 * 4. Monthly close aggregation from daily closes
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\DailyClose;
use App\Models\MonthlyClose;
use App\Models\Order;
use App\Models\Expense;
use App\Models\Transactions;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "Daily & Monthly Close Calculation Test\n";
echo "========================================\n\n";

try {
    // Get main box user
    $userAccount = UserType::where('name', 'account')->first();
    if (!$userAccount) {
        throw new Exception("UserType 'account' not found");
    }
    
    $mainBoxUser = User::with('wallet')
        ->where('type_id', $userAccount->id)
        ->where('email', 'mainBox@account.com')
        ->first();
    
    if (!$mainBoxUser || !$mainBoxUser->wallet) {
        throw new Exception("Main box user or wallet not found");
    }
    
    $walletId = $mainBoxUser->wallet->id;
    echo "✓ Main box wallet found (ID: {$walletId})\n\n";
    
    // Test 1: Check today's transactions
    $today = Carbon::today();
    $startDate = $today->copy()->startOfDay();
    $endDate = $today->copy()->endOfDay();
    
    echo "Test 1: Today's Transactions Analysis\n";
    echo "--------------------------------------\n";
    echo "Date: {$today->format('Y-m-d')}\n\n";
    
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
    
    echo "Sales Transactions (Orders): " . $salesTransactions->count() . "\n";
    echo "  USD: " . $salesTransactions->whereIn('currency', ['USD', '$'])->sum('amount') . "\n";
    echo "  IQD: " . $salesTransactions->where('currency', 'IQD')->sum('amount') . "\n\n";
    
    // Additional income (deposits/additions)
    $additionalIncome = Transactions::where('wallet_id', $walletId)
        ->where('type', 'in')
        ->whereBetween('created', [$startDate, $endDate])
        ->where(function($query) {
            $query->where(function($q) {
                $q->where('morphed_type', '!=', Order::class)
                  ->where('morphed_type', '!=', 'App\\Models\\Order');
            })
            ->where(function($q) {
                $q->where('description', 'NOT LIKE', '%طلب%')
                  ->where('description', 'NOT LIKE', '%order%')
                  ->where('description', 'NOT LIKE', '%فاتورة%')
                  ->where('description', 'NOT LIKE', '%invoice%');
            });
        })
        ->get();
    
    echo "Additional Income (Deposits/Additions): " . $additionalIncome->count() . "\n";
    echo "  USD: " . $additionalIncome->whereIn('currency', ['USD', '$'])->sum('amount') . "\n";
    echo "  IQD: " . $additionalIncome->where('currency', 'IQD')->sum('amount') . "\n\n";
    
    // Expenses from Expense table
    $expenses = Expense::whereDate('expense_date', $today)->get();
    echo "Expenses (from Expense table): " . $expenses->count() . "\n";
    echo "  USD: " . $expenses->where('currency', 'USD')->sum('amount') . "\n";
    echo "  IQD: " . $expenses->where('currency', 'IQD')->sum('amount') . "\n\n";
    
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
    
    echo "Expenses (from transactions): " . $expenseTransactions->count() . "\n";
    echo "  USD: " . abs($expenseTransactions->whereIn('currency', ['USD', '$'])->sum('amount')) . "\n";
    echo "  IQD: " . abs($expenseTransactions->where('currency', 'IQD')->sum('amount')) . "\n\n";
    
    // Test 2: Calculate Daily Close
    echo "Test 2: Daily Close Calculation\n";
    echo "---------------------------------\n";
    
    $dailyClose = DailyClose::getToday();
    $dailyClose->calculateDailyData();
    
    echo "Total Sales:\n";
    echo "  USD: " . $dailyClose->total_sales_usd . "\n";
    echo "  IQD: " . $dailyClose->total_sales_iqd . "\n\n";
    
    echo "Total Expenses:\n";
    echo "  USD: " . $dailyClose->total_expenses_usd . "\n";
    echo "  IQD: " . $dailyClose->total_expenses_iqd . "\n\n";
    
    echo "Opening Balance:\n";
    echo "  USD: " . $dailyClose->opening_balance_usd . "\n";
    echo "  IQD: " . $dailyClose->opening_balance_iqd . "\n\n";
    
    echo "Closing Balance:\n";
    echo "  USD: " . $dailyClose->closing_balance_usd . "\n";
    echo "  IQD: " . $dailyClose->closing_balance_iqd . "\n\n";
    
    echo "Total Orders: " . $dailyClose->total_orders . "\n\n";
    
    // Test 3: Monthly Close
    echo "Test 3: Monthly Close Calculation\n";
    echo "----------------------------------\n";
    
    $monthlyClose = MonthlyClose::getCurrentMonth();
    $monthlyClose->calculateMonthlyData();
    
    echo "Total Sales (Month):\n";
    echo "  USD: " . $monthlyClose->total_sales_usd . "\n";
    echo "  IQD: " . $monthlyClose->total_sales_iqd . "\n\n";
    
    echo "Total Expenses (Month):\n";
    echo "  USD: " . $monthlyClose->total_expenses_usd . "\n";
    echo "  IQD: " . $monthlyClose->total_expenses_iqd . "\n\n";
    
    echo "Total Orders (Month): " . $monthlyClose->total_orders . "\n\n";
    
    // Test 4: Verification
    echo "Test 4: Verification\n";
    echo "--------------------\n";
    
    $expectedSalesUsd = $salesTransactions->whereIn('currency', ['USD', '$'])->sum('amount') + 
                       $additionalIncome->whereIn('currency', ['USD', '$'])->sum('amount');
    $expectedSalesIqd = $salesTransactions->where('currency', 'IQD')->sum('amount') + 
                       $additionalIncome->where('currency', 'IQD')->sum('amount');
    
    $expectedExpensesUsd = $expenses->where('currency', 'USD')->sum('amount') + 
                          abs($expenseTransactions->whereIn('currency', ['USD', '$'])->sum('amount'));
    $expectedExpensesIqd = $expenses->where('currency', 'IQD')->sum('amount') + 
                          abs($expenseTransactions->where('currency', 'IQD')->sum('amount'));
    
    echo "Expected Sales:\n";
    echo "  USD: " . $expectedSalesUsd . " (Actual: " . $dailyClose->total_sales_usd . ")\n";
    echo "  IQD: " . $expectedSalesIqd . " (Actual: " . $dailyClose->total_sales_iqd . ")\n";
    
    if (abs($expectedSalesUsd - $dailyClose->total_sales_usd) < 0.01 && 
        abs($expectedSalesIqd - $dailyClose->total_sales_iqd) < 0.01) {
        echo "  ✓ Sales calculation is correct!\n\n";
    } else {
        echo "  ✗ Sales calculation mismatch!\n\n";
    }
    
    echo "Expected Expenses:\n";
    echo "  USD: " . $expectedExpensesUsd . " (Actual: " . $dailyClose->total_expenses_usd . ")\n";
    echo "  IQD: " . $expectedExpensesIqd . " (Actual: " . $dailyClose->total_expenses_iqd . ")\n";
    
    if (abs($expectedExpensesUsd - $dailyClose->total_expenses_usd) < 0.01 && 
        abs($expectedExpensesIqd - $dailyClose->total_expenses_iqd) < 0.01) {
        echo "  ✓ Expenses calculation is correct!\n\n";
    } else {
        echo "  ✗ Expenses calculation mismatch!\n\n";
    }
    
    echo "========================================\n";
    echo "Test completed successfully!\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}

