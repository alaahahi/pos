<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\AccountingController;
use App\Models\User;
use App\Models\UserType;
use App\Models\DailyClose;
use Carbon\Carbon;

class TestAddToBox extends Command
{
    protected $signature = 'test:add-to-box {amount=100} {currency=IQD}';
    protected $description = 'Test adding amount to box and check if it appears in daily close';

    public function handle()
    {
        $amount = (float) $this->argument('amount');
        $currency = $this->argument('currency');
        
        $this->info("Creating test addition: {$amount} {$currency}");
        
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
        
        $this->info("Main box user ID: {$mainBoxUser->id}");
        $this->info("Wallet ID: {$mainBoxUser->wallet->id}");
        
        // Get today's date
        $today = Carbon::today()->format('Y-m-d');
        $this->info("Today date: {$today}");
        
        // Create transaction
        $accountingController = new AccountingController();
        $transaction = $accountingController->increaseWallet(
            $amount,
            'إضافة تجريبية للاختبار - ' . now()->format('H:i:s'),
            $mainBoxUser->id,
            $mainBoxUser->id,
            'App\Models\User',
            0,
            0,
            $currency,
            $today
        );
        
        if (!$transaction) {
            $this->error('Failed to create transaction!');
            return 1;
        }
        
        $this->info("✓ Transaction created successfully!");
        $this->info("  Transaction ID: {$transaction->id}");
        $this->info("  Transaction date: {$transaction->created}");
        $this->info("  Amount: {$transaction->amount} {$transaction->currency}");
        $this->info("  Description: {$transaction->description}");
        
        // Calculate daily close
        $this->info("\nCalculating daily close...");
        $dailyClose = DailyClose::getToday();
        $dailyClose->calculateDailyData();
        $dailyClose->save();
        
        $this->info("✓ Daily close calculated!");
        $this->info("  Direct deposits USD: {$dailyClose->direct_deposits_usd}");
        $this->info("  Direct deposits IQD: {$dailyClose->direct_deposits_iqd}");
        
        // Check if our transaction is included
        if ($currency === 'IQD' && $dailyClose->direct_deposits_iqd >= $amount) {
            $this->info("\n✓ SUCCESS: The addition appears in daily close!");
        } elseif ($currency === 'USD' && $dailyClose->direct_deposits_usd >= $amount) {
            $this->info("\n✓ SUCCESS: The addition appears in daily close!");
        } else {
            $this->warn("\n⚠ WARNING: The addition may not appear correctly in daily close.");
            $this->warn("  Expected: {$amount} {$currency}");
            $this->warn("  Found in daily close: USD={$dailyClose->direct_deposits_usd}, IQD={$dailyClose->direct_deposits_iqd}");
        }
        
        return 0;
    }
}


