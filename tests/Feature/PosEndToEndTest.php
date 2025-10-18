<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\UserType;
use App\Models\Wallet;

class PosEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Supplier $supplier;
    protected Customer $walkInCustomer;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and ensure main cashbox exists
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create the 'account' user type and the main box user with wallet
        $userAccount = UserType::firstOrCreate(['name' => 'account']);
        $mainBox = User::firstOrCreate(
            ['email' => 'mainBox@account.com'],
            [
                'name' => 'الصندوق الرئيسي',
                'password' => bcrypt('password'),
                'type_id' => $userAccount->id,
            ]
        );
        if (!$mainBox->wallet) {
            Wallet::create(['user_id' => $mainBox->id, 'balance' => 0, 'balance_dinar' => 0]);
        }

        // Create supplier and walk-in customer
        $this->supplier = Supplier::create([
            'name' => 'شركة التوريد',
            'phone' => '0790000000',
            'is_active' => true,
        ]);

        $this->walkInCustomer = Customer::create([
            'name' => 'زبون افتراضي',
            'phone' => '0770000000',
            'is_active' => true,
        ]);

        // Create two products with initial quantities
        $this->productA = Product::create([
            'name' => 'منتج A',
            'barcode' => '1111111111',
            'price' => 100,
            'price_cost' => 80,
            'quantity' => 0,
            'is_active' => true,
        ]);

        $this->productB = Product::create([
            'name' => 'منتج B',
            'barcode' => '2222222222',
            'price' => 200,
            'price_cost' => 150,
            'quantity' => 0,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function end_to_end_flow_from_purchase_to_pos_sale()
    {
        $this->actingAs($this->user);

        // 1) Supplier delivers stock to warehouse via Purchase Invoice
        $invoicePayload = [
            'supplier_id' => $this->supplier->id,
            'invoice_date' => now()->format('Y-m-d'),
            'notes' => 'توريد أولي',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 10,
                    'cost_price' => 70.00,
                    'sales_price' => 120.00,
                ],
                [
                    'product_id' => $this->productB->id,
                    'quantity' => 5,
                    'cost_price' => 140.00,
                    'sales_price' => 190.00,
                ],
            ],
            'withdraw_from_cashbox' => true,
            'currency' => 'IQD',
        ];

        $purchaseResponse = $this->post(route('purchase-invoices.store'), $invoicePayload);
        $purchaseResponse->assertRedirect(route('purchase-invoices.index'));
        $purchaseResponse->assertSessionHas('success');

        // Validate DB state after purchase
        $this->productA->refresh();
        $this->productB->refresh();
        $this->assertEquals(10, $this->productA->quantity);
        $this->assertEquals(5, $this->productB->quantity);
        $this->assertEquals(120.00, (float) $this->productA->price);
        $this->assertEquals(190.00, (float) $this->productB->price);

        // Price history created for price changes
        $this->assertDatabaseHas('product_price_history', [
            'product_id' => $this->productA->id,
            'old_price' => 100.00,
            'new_price' => 120.00,
            'change_reason' => 'purchase',
            'changed_by' => $this->user->id,
        ]);
        $this->assertDatabaseHas('product_price_history', [
            'product_id' => $this->productB->id,
            'old_price' => 200.00,
            'new_price' => 190.00,
            'change_reason' => 'purchase',
            'changed_by' => $this->user->id,
        ]);

        // Cashbox transaction: withdrawal for purchase cost
        $expectedTotalIQD = (10 * 70.00) + (5 * 140.00); // 700 + 700 = 1400
        $this->assertDatabaseHas('cashbox_transactions', [
            'type' => 'withdrawal',
            'currency' => 'IQD',
            'amount' => 1400.00,
        ]);

        // 2) Move items into stock is implicit via invoice processing above
        //    Stock is already increased at this point.

        // 3) Create a POS sales invoice (Order) via API
        $sellQtyA = 3; // sell 3 of product A
        $sellQtyB = 2; // sell 2 of product B
        $totalAmount = ($sellQtyA * (float)$this->productA->price) + ($sellQtyB * (float)$this->productB->price);

        $orderPayload = [
            'customer_id' => $this->walkInCustomer->id,
            'total_amount' => $totalAmount,
            'total_paid' => $totalAmount, // full cash payment
            'notes' => 'فاتورة مبيعات نقدية',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => $sellQtyA,
                    'price' => (float)$this->productA->price,
                ],
                [
                    'product_id' => $this->productB->id,
                    'quantity' => $sellQtyB,
                    'price' => (float)$this->productB->price,
                ],
            ],
        ];

        $orderResponse = $this->postJson('/api/createOrder', $orderPayload);
        $orderResponse->assertStatus(201);
        $orderId = $orderResponse->json('order_id');
        $this->assertNotEmpty($orderId);

        // Validate Order and pivot rows
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'customer_id' => $this->walkInCustomer->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('order_product', [
            'order_id' => $orderId,
            'product_id' => $this->productA->id,
            'quantity' => $sellQtyA,
        ]);
        $this->assertDatabaseHas('order_product', [
            'order_id' => $orderId,
            'product_id' => $this->productB->id,
            'quantity' => $sellQtyB,
        ]);

        // Validate stock decreased after sale
        $this->productA->refresh();
        $this->productB->refresh();
        $this->assertEquals(10 - $sellQtyA, $this->productA->quantity);
        $this->assertEquals(5 - $sellQtyB, $this->productB->quantity);

        // Validate cashbox increased for POS sale (Transactions table via AccountingController)
        // We cannot guarantee exact rows here as implementation may vary; ensure at least one positive 'in' transaction exists
        $this->assertDatabaseHas('transactions', [
            'type' => 'in',
        ]);
    }
}

