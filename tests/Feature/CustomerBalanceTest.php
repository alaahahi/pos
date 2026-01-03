<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
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

class CustomerBalanceTest extends TestCase
{
    use DatabaseTransactions;

    protected $customer;
    protected $mainBoxUser;
    protected $mainBoxWallet;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء نوع مستخدم
        $userAccount = UserType::firstOrCreate(['name' => 'account']);

        // إنشاء main box user
        $this->mainBoxUser = User::firstOrCreate(
            ['email' => 'mainBox@account.com'],
            [
                'name' => 'Main Box',
                'type_id' => $userAccount->id,
                'password' => bcrypt('password'),
            ]
        );

        // إنشاء wallet للـ main box
        $this->mainBoxWallet = Wallet::firstOrCreate(
            ['user_id' => $this->mainBoxUser->id],
            [
                'balance' => 0,
                'balance_dinar' => 0,
            ]
        );

        // إنشاء عميل
        $this->customer = Customer::factory()->create();
    }

    /**
     * Test: إضافة رصيد يجب أن تزيد الصندوق الرئيسي
     */
    public function test_add_balance_increases_main_box()
    {
        $initialBalance = $this->mainBoxWallet->balance_dinar;
        $amount = 5000;

        // إضافة رصيد
        $response = $this->post('/decoration-payments/balance/add', [
            'customer_id' => $this->customer->id,
            'amount' => $amount,
            'currency' => 'dinar',
            'notes' => 'Test deposit',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // التحقق من زيادة الصندوق الرئيسي
        $this->mainBoxWallet->refresh();
        $this->assertEquals(
            $initialBalance + $amount,
            $this->mainBoxWallet->balance_dinar,
            'الصندوق الرئيسي يجب أن يزيد بمقدار الرصيد المضاف'
        );

        // التحقق من وجود معاملة في Transactions
        $transaction = Transactions::where('morphed_type', Customer::class)
            ->where('morphed_id', $this->customer->id)
            ->where('description', 'like', '%إضافة رصيد%')
            ->latest()
            ->first();

        $this->assertNotNull($transaction, 'يجب أن توجد معاملة في Transactions');
        $this->assertEquals($amount, $transaction->amount);
        $this->assertEquals('IQD', $transaction->currency);

        // التحقق من زيادة رصيد العميل
        $customerBalance = CustomerBalance::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($customerBalance);
        $this->assertEquals($amount, $customerBalance->balance_dinar);
    }

    /**
     * Test: دفع فاتورة من الرصيد يجب ألا يؤثر على الصندوق الرئيسي
     */
    public function test_pay_invoice_from_balance_does_not_affect_main_box()
    {
        // إضافة رصيد أولاً
        $depositAmount = 10000;
        $this->post('/decoration-payments/balance/add', [
            'customer_id' => $this->customer->id,
            'amount' => $depositAmount,
            'currency' => 'dinar',
            'notes' => 'Initial deposit',
        ]);

        // إنشاء فاتورة
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'total_amount' => 5000,
            'final_amount' => 5000,
            'total_paid' => 0,
            'status' => 'due',
            'currency' => 'IQD',
        ]);

        $mainBoxBalanceBefore = $this->mainBoxWallet->fresh()->balance_dinar;
        $transactionsCountBefore = Transactions::where('wallet_id', $this->mainBoxWallet->id)->count();

        // دفع الفاتورة من الرصيد
        $response = $this->post(route('customers.orders.pay', [
            'customer' => $this->customer->id,
            'order' => $order->id,
        ]), [
            'amount' => 5000,
            'payment_method' => 'balance',
            'notes' => 'Payment from balance',
        ]);

        $response->assertStatus(302); // Redirect after success

        // التحقق من عدم تغيير الصندوق الرئيسي
        $mainBoxBalanceAfter = $this->mainBoxWallet->fresh()->balance_dinar;
        $transactionsCountAfter = Transactions::where('wallet_id', $this->mainBoxWallet->id)->count();

        $this->assertEquals(
            $mainBoxBalanceBefore,
            $mainBoxBalanceAfter,
            'الصندوق الرئيسي يجب ألا يتغير عند الدفع من الرصيد'
        );

        $this->assertEquals(
            $transactionsCountBefore,
            $transactionsCountAfter,
            'عدد المعاملات في الصندوق الرئيسي يجب ألا يتغير'
        );

        // التحقق من وجود Box transaction للعميل فقط
        $boxTransaction = Box::where('morphed_type', Customer::class)
            ->where('morphed_id', $this->customer->id)
            ->where('type', 'payment')
            ->whereJsonContains('details->payment_method', 'balance')
            ->latest()
            ->first();

        $this->assertNotNull($boxTransaction, 'يجب أن توجد Box transaction للعميل');
        $this->assertEquals(5000, $boxTransaction->amount);

        // التحقق من نقصان رصيد العميل
        $customerBalance = CustomerBalance::where('customer_id', $this->customer->id)->first();
        $this->assertEquals($depositAmount - 5000, $customerBalance->balance_dinar);
    }

    /**
     * Test: دفع فاتورة نقدي يجب أن يزيد الصندوق الرئيسي
     */
    public function test_pay_invoice_cash_increases_main_box()
    {
        // إنشاء فاتورة
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'total_amount' => 5000,
            'final_amount' => 5000,
            'total_paid' => 0,
            'status' => 'due',
            'currency' => 'IQD',
        ]);

        $mainBoxBalanceBefore = $this->mainBoxWallet->fresh()->balance_dinar;
        $amount = 5000;

        // دفع الفاتورة نقدي
        $response = $this->post(route('customers.orders.pay', [
            'customer' => $this->customer->id,
            'order' => $order->id,
        ]), [
            'amount' => $amount,
            'payment_method' => 'cash',
            'notes' => 'Cash payment',
        ]);

        $response->assertStatus(302); // Redirect after success

        // التحقق من زيادة الصندوق الرئيسي
        $mainBoxBalanceAfter = $this->mainBoxWallet->fresh()->balance_dinar;
        $this->assertEquals(
            $mainBoxBalanceBefore + $amount,
            $mainBoxBalanceAfter,
            'الصندوق الرئيسي يجب أن يزيد عند الدفع النقدي'
        );

        // التحقق من وجود معاملة في Transactions
        $transaction = Transactions::where('wallet_id', $this->mainBoxWallet->id)
            ->where('morphed_type', Order::class)
            ->where('morphed_id', $order->id)
            ->latest()
            ->first();

        $this->assertNotNull($transaction, 'يجب أن توجد معاملة في Transactions');
        $this->assertEquals($amount, $transaction->amount);
    }
}

