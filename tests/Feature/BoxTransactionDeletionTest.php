<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transactions;
use App\Models\UserType;
use App\Http\Controllers\AccountingController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

class BoxTransactionDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected $accountingController;
    protected $mainBox;
    protected $mainBoxWallet;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء نوع المستخدم (account)
        $userType = UserType::create([
            'name' => 'account',
            'translation_key' => 'account'
        ]);

        // إنشاء الصندوق الرئيسي
        $this->mainBox = User::create([
            'name' => 'Main Box',
            'email' => 'mainBox@account.com',
            'password' => bcrypt('password'),
            'type_id' => $userType->id,
            'gender' => 'male',
            'phone' => '1234567890',
            'address' => 'test address'
        ]);

        // إنشاء محفظة للصندوق
        $this->mainBoxWallet = Wallet::create([
            'user_id' => $this->mainBox->id,
            'balance' => 1000,  // USD
            'balance_dinar' => 100000, // IQD
            'card' => null
        ]);

        $this->accountingController = new AccountingController();
    }

    /** @test */
    public function test_delete_usd_deposit_transaction_decreases_balance_correctly()
    {
        // الرصيد الأولي
        $initialBalance = $this->mainBoxWallet->balance;
        
        // إنشاء معاملة إيداع USD
        $transaction = Transactions::create([
            'wallet_id' => $this->mainBoxWallet->id,
            'amount' => 100, // إيداع 100 دولار
            'type' => 'inUserBox',
            'description' => 'Test deposit',
            'currency' => '$',
            'morphed_type' => 'App\Models\User',
            'morphed_id' => $this->mainBox->id,
            'created' => now(),
        ]);

        // تحديث الرصيد (كما يحدث عند الإضافة)
        $this->mainBoxWallet->increment('balance', 100);
        $this->mainBoxWallet->refresh();

        // التحقق من أن الرصيد زاد
        $this->assertEquals($initialBalance + 100, $this->mainBoxWallet->balance);

        // حذف المعاملة
        $request = Request::create('/api/transactions/delete', 'POST', [
            'id' => $transaction->id
        ]);
        
        $response = $this->accountingController->delTransactions($request);

        // التحقق من الرصيد بعد الحذف
        $this->mainBoxWallet->refresh();
        $this->assertEquals($initialBalance, $this->mainBoxWallet->balance, 
            "Balance should return to initial amount after deleting deposit transaction");
    }

    /** @test */
    public function test_delete_iqd_deposit_transaction_decreases_balance_dinar_correctly()
    {
        // الرصيد الأولي
        $initialBalance = $this->mainBoxWallet->balance_dinar;
        
        // إنشاء معاملة إيداع IQD
        $transaction = Transactions::create([
            'wallet_id' => $this->mainBoxWallet->id,
            'amount' => 50000, // إيداع 50000 دينار
            'type' => 'inUserBox',
            'description' => 'Test deposit IQD',
            'currency' => 'IQD',
            'morphed_type' => 'App\Models\User',
            'morphed_id' => $this->mainBox->id,
            'created' => now(),
        ]);

        // تحديث الرصيد (كما يحدث عند الإضافة)
        $this->mainBoxWallet->increment('balance_dinar', 50000);
        $this->mainBoxWallet->refresh();

        // التحقق من أن الرصيد زاد
        $this->assertEquals($initialBalance + 50000, $this->mainBoxWallet->balance_dinar);

        // حذف المعاملة
        $request = Request::create('/api/transactions/delete', 'POST', [
            'id' => $transaction->id
        ]);
        
        $response = $this->accountingController->delTransactions($request);

        // التحقق من الرصيد بعد الحذف
        $this->mainBoxWallet->refresh();
        $this->assertEquals($initialBalance, $this->mainBoxWallet->balance_dinar, 
            "Balance dinar should return to initial amount after deleting deposit transaction");
    }

    /** @test */
    public function test_delete_usd_withdrawal_transaction_increases_balance_correctly()
    {
        // الرصيد الأولي
        $initialBalance = $this->mainBoxWallet->balance;
        
        // إنشاء معاملة سحب USD
        $transaction = Transactions::create([
            'wallet_id' => $this->mainBoxWallet->id,
            'amount' => -100, // سحب 100 دولار (قيمة سالبة)
            'type' => 'outUserBox',
            'description' => 'Test withdrawal',
            'currency' => '$',
            'morphed_type' => 'App\Models\User',
            'morphed_id' => $this->mainBox->id,
            'created' => now(),
        ]);

        // تحديث الرصيد (كما يحدث عند السحب)
        $this->mainBoxWallet->decrement('balance', 100);
        $this->mainBoxWallet->refresh();

        // التحقق من أن الرصيد نقص
        $this->assertEquals($initialBalance - 100, $this->mainBoxWallet->balance);

        // حذف المعاملة
        $request = Request::create('/api/transactions/delete', 'POST', [
            'id' => $transaction->id
        ]);
        
        $response = $this->accountingController->delTransactions($request);

        // التحقق من الرصيد بعد الحذف
        $this->mainBoxWallet->refresh();
        $this->assertEquals($initialBalance, $this->mainBoxWallet->balance, 
            "Balance should return to initial amount after deleting withdrawal transaction");
    }

    /** @test */
    public function test_delete_transaction_with_child_transactions()
    {
        $initialBalanceUSD = $this->mainBoxWallet->balance;
        $initialBalanceIQD = $this->mainBoxWallet->balance_dinar;
        
        // إنشاء معاملة رئيسية
        $parentTransaction = Transactions::create([
            'wallet_id' => $this->mainBoxWallet->id,
            'amount' => 100,
            'type' => 'inUserBox',
            'description' => 'Parent transaction',
            'currency' => '$',
            'morphed_type' => 'App\Models\User',
            'morphed_id' => $this->mainBox->id,
            'created' => now(),
        ]);

        // تحديث الرصيد
        $this->mainBoxWallet->increment('balance', 100);

        // إنشاء معاملة فرعية USD
        $childTransaction1 = Transactions::create([
            'wallet_id' => $this->mainBoxWallet->id,
            'amount' => 50,
            'type' => 'relatedTransaction',
            'description' => 'Child transaction USD',
            'currency' => '$',
            'parent_id' => $parentTransaction->id,
            'morphed_type' => 'App\Models\User',
            'morphed_id' => $this->mainBox->id,
            'created' => now(),
        ]);

        // تحديث الرصيد للمعاملة الفرعية
        $this->mainBoxWallet->increment('balance', 50);

        // إنشاء معاملة فرعية IQD
        $childTransaction2 = Transactions::create([
            'wallet_id' => $this->mainBoxWallet->id,
            'amount' => 25000,
            'type' => 'relatedTransaction',
            'description' => 'Child transaction IQD',
            'currency' => 'IQD',
            'parent_id' => $parentTransaction->id,
            'morphed_type' => 'App\Models\User',
            'morphed_id' => $this->mainBox->id,
            'created' => now(),
        ]);

        // تحديث الرصيد للمعاملة الفرعية
        $this->mainBoxWallet->increment('balance_dinar', 25000);
        $this->mainBoxWallet->refresh();

        // حذف المعاملة الرئيسية
        $request = Request::create('/api/transactions/delete', 'POST', [
            'id' => $parentTransaction->id
        ]);
        
        $response = $this->accountingController->delTransactions($request);

        // التحقق من الرصيد بعد الحذف
        $this->mainBoxWallet->refresh();
        $this->assertEquals($initialBalanceUSD, $this->mainBoxWallet->balance, 
            "USD balance should return to initial amount after deleting parent transaction");
        $this->assertEquals($initialBalanceIQD, $this->mainBoxWallet->balance_dinar, 
            "IQD balance should return to initial amount after deleting parent transaction");
        
        // التحقق من حذف المعاملات الفرعية
        $this->assertNull(Transactions::find($childTransaction1->id));
        $this->assertNull(Transactions::find($childTransaction2->id));
    }
}

