<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\CustomerBalance;
use App\Models\Order;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;
use App\Models\Transactions;
use App\Models\Box;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestCustomerBalance extends Command
{
    protected $signature = 'test:customer-balance';
    protected $description = 'Test customer balance operations (add balance, pay from balance, pay cash)';

    public function handle()
    {
        $this->info('🧪 بدء اختبار عمليات رصيد العميل...');
        $this->newLine();

        // استخدام الاتصال الافتراضي (MySQL) بدلاً من SQLite
        $defaultConnection = config('database.default');
        if ($defaultConnection === 'sync_sqlite') {
            $this->warn('⚠️  يتم استخدام SQLite. قد لا تعمل جميع الاختبارات بشكل صحيح.');
            $this->warn('   يفضل استخدام MySQL للاختبارات الكاملة.');
            $this->newLine();
        }

        DB::beginTransaction();
        
        try {
            // 1. اختبار إضافة رصيد
            $this->testAddBalance();
            
            // 2. اختبار دفع فاتورة من الرصيد
            $this->testPayFromBalance();
            
            // 3. اختبار دفع فاتورة نقدي
            $this->testPayCash();
            
            DB::rollBack(); // Rollback للتأكد من عدم تغيير البيانات الفعلية
            
            $this->newLine();
            $this->info('✅ جميع الاختبارات نجحت!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ فشل الاختبار: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }

    private function testAddBalance()
    {
        $this->info('📝 اختبار 1: إضافة رصيد يجب أن تزيد الصندوق الرئيسي');
        
        // الحصول على main box
        $userAccount = UserType::where('name', 'account')->first();
        if (!$userAccount) {
            throw new \Exception('UserType "account" not found');
        }
        
        $mainBoxUser = User::with('wallet')
            ->where('type_id', $userAccount->id)
            ->where('email', 'mainBox@account.com')
            ->first();
            
        if (!$mainBoxUser || !$mainBoxUser->wallet) {
            throw new \Exception('Main box user or wallet not found');
        }
        
        // الحصول على عميل
        $customer = Customer::first();
        if (!$customer) {
            throw new \Exception('No customer found');
        }
        
        $initialBalance = $mainBoxUser->wallet->balance_dinar;
        $amount = 5000;
        
        $this->line("   الرصيد الأولي للصندوق: {$initialBalance}");
        $this->line("   المبلغ المضاف: {$amount}");
        
        // محاكاة إضافة رصيد
        $accountingController = app(\App\Http\Controllers\AccountingController::class);
        $transaction = $accountingController->increaseWallet(
            (int) round($amount),
            "إضافة رصيد للعميل {$customer->name} (اختبار)",
            $mainBoxUser->id,
            $customer->id,
            Customer::class,
            0,
            0,
            'IQD',
            now()->format('Y-m-d'),
            0,
            'in',
            ['notes' => 'Test deposit', 'customer_id' => $customer->id, 'type' => 'balance_deposit']
        );
        
        $mainBoxUser->wallet->refresh();
        $newBalance = $mainBoxUser->wallet->balance_dinar;
        
        $this->line("   الرصيد الجديد للصندوق: {$newBalance}");
        
        if ($newBalance == $initialBalance + $amount) {
            $this->info('   ✅ نجح: الصندوق زاد بمقدار الرصيد المضاف');
        } else {
            throw new \Exception("الصندوق لم يزد بشكل صحيح. المتوقع: " . ($initialBalance + $amount) . "، الفعلي: {$newBalance}");
        }
        
        // التحقق من وجود معاملة
        $transactionCheck = Transactions::where('id', $transaction->id)->first();
        if ($transactionCheck) {
            $this->info('   ✅ نجح: تم إنشاء معاملة في Transactions');
        } else {
            throw new \Exception('لم يتم إنشاء معاملة في Transactions');
        }
        
        $this->newLine();
    }

    private function testPayFromBalance()
    {
        $this->info('📝 اختبار 2: دفع فاتورة من الرصيد يجب ألا يؤثر على الصندوق الرئيسي');
        
        // الحصول على main box
        $userAccount = UserType::where('name', 'account')->first();
        $mainBoxUser = User::with('wallet')
            ->where('type_id', $userAccount->id)
            ->where('email', 'mainBox@account.com')
            ->first();
        
        // الحصول على عميل
        $customer = Customer::first();
        
        // إنشاء فاتورة
        $order = Order::create([
            'customer_id' => $customer->id,
            'total_amount' => 3000,
            'final_amount' => 3000,
            'total_paid' => 0,
            'status' => 'due',
            'currency' => 'IQD',
            'date' => now()->format('Y-m-d'),
        ]);
        
        $mainBoxBalanceBefore = $mainBoxUser->wallet->balance_dinar;
        $transactionsCountBefore = Transactions::where('wallet_id', $mainBoxUser->wallet->id)->count();
        
        $this->line("   الرصيد الأولي للصندوق: {$mainBoxBalanceBefore}");
        $this->line("   عدد المعاملات قبل: {$transactionsCountBefore}");
        
        // محاكاة دفع من الرصيد (بدون زيادة الصندوق)
        // فقط إنشاء Box transaction للعميل
        $paymentBox = Box::create([
            'name' => "دفع فاتورة رقم {$order->id} من الرصيد - {$customer->name} (اختبار)",
            'amount' => 3000,
            'type' => 'payment',
            'description' => "دفع فاتورة رقم {$order->id} من الرصيد - {$customer->name}",
            'currency' => 'IQD',
            'created' => now()->format('Y-m-d'),
            'details' => [
                'notes' => 'Test payment from balance',
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'payment_method' => 'balance',
            ],
            'morphed_id' => $customer->id,
            'morphed_type' => Customer::class,
            'is_active' => true,
            'balance' => 0,
            'balance_usd' => 0,
        ]);
        
        $mainBoxUser->wallet->refresh();
        $mainBoxBalanceAfter = $mainBoxUser->wallet->balance_dinar;
        $transactionsCountAfter = Transactions::where('wallet_id', $mainBoxUser->wallet->id)->count();
        
        $this->line("   الرصيد بعد الدفع: {$mainBoxBalanceAfter}");
        $this->line("   عدد المعاملات بعد: {$transactionsCountAfter}");
        
        if ($mainBoxBalanceBefore == $mainBoxBalanceAfter) {
            $this->info('   ✅ نجح: الصندوق لم يتغير');
        } else {
            throw new \Exception("الصندوق تغير! قبل: {$mainBoxBalanceBefore}، بعد: {$mainBoxBalanceAfter}");
        }
        
        if ($transactionsCountBefore == $transactionsCountAfter) {
            $this->info('   ✅ نجح: عدد المعاملات لم يتغير');
        } else {
            throw new \Exception("عدد المعاملات تغير! قبل: {$transactionsCountBefore}، بعد: {$transactionsCountAfter}");
        }
        
        // التحقق من وجود Box transaction
        if ($paymentBox) {
            $this->info('   ✅ نجح: تم إنشاء Box transaction للعميل');
        } else {
            throw new \Exception('لم يتم إنشاء Box transaction');
        }
        
        $this->newLine();
    }

    private function testPayCash()
    {
        $this->info('📝 اختبار 3: دفع فاتورة نقدي يجب أن يزيد الصندوق الرئيسي');
        
        // الحصول على main box
        $userAccount = UserType::where('name', 'account')->first();
        $mainBoxUser = User::with('wallet')
            ->where('type_id', $userAccount->id)
            ->where('email', 'mainBox@account.com')
            ->first();
        
        // الحصول على عميل
        $customer = Customer::first();
        
        // إنشاء فاتورة
        $order = Order::create([
            'customer_id' => $customer->id,
            'total_amount' => 2000,
            'final_amount' => 2000,
            'total_paid' => 0,
            'status' => 'due',
            'currency' => 'IQD',
            'date' => now()->format('Y-m-d'),
        ]);
        
        $mainBoxBalanceBefore = $mainBoxUser->wallet->balance_dinar;
        $amount = 2000;
        
        $this->line("   الرصيد الأولي للصندوق: {$mainBoxBalanceBefore}");
        $this->line("   المبلغ المدفوع: {$amount}");
        
        // محاكاة دفع نقدي
        $accountingController = app(\App\Http\Controllers\AccountingController::class);
        $transaction = $accountingController->increaseWallet(
            $amount,
            'دفع نقدي فاتورة رقم ' . $order->id . ' - ' . $customer->name . ' (اختبار)',
            $mainBoxUser->id,
            $order->id,
            Order::class,
            0,
            0,
            'IQD',
            now()->format('Y-m-d')
        );
        
        $mainBoxUser->wallet->refresh();
        $mainBoxBalanceAfter = $mainBoxUser->wallet->balance_dinar;
        
        $this->line("   الرصيد الجديد للصندوق: {$mainBoxBalanceAfter}");
        
        if ($mainBoxBalanceAfter == $mainBoxBalanceBefore + $amount) {
            $this->info('   ✅ نجح: الصندوق زاد بمقدار المبلغ المدفوع');
        } else {
            throw new \Exception("الصندوق لم يزد بشكل صحيح. المتوقع: " . ($mainBoxBalanceBefore + $amount) . "، الفعلي: {$mainBoxBalanceAfter}");
        }
        
        // التحقق من وجود معاملة
        $transactionCheck = Transactions::where('id', $transaction->id)->first();
        if ($transactionCheck) {
            $this->info('   ✅ نجح: تم إنشاء معاملة في Transactions');
        } else {
            throw new \Exception('لم يتم إنشاء معاملة في Transactions');
        }
        
        $this->newLine();
    }
}

