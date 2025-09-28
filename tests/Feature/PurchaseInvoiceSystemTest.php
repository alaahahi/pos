<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\ProductPriceHistory;
use App\Models\CashboxTransaction;
use App\Models\UserType;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $supplier;
    protected $products;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        // Create supplier
        $this->supplier = Customer::create([
            'name' => 'مورد تجريبي',
            'phone' => '07901234567',
            'is_supplier' => true,
        ]);
        
        // Create test products
        $this->products = [
            Product::create([
                'name' => 'منتج تجريبي 1',
                'barcode' => '123456789',
                'price' => 100.00,
                'price_cost' => 80.00,
                'quantity' => 10,
                'is_active' => true,
            ]),
            Product::create([
                'name' => 'منتج تجريبي 2',
                'barcode' => '987654321',
                'price' => 200.00,
                'price_cost' => 150.00,
                'quantity' => 5,
                'is_active' => true,
            ]),
        ];
    }

    /** @test */
    public function can_create_purchase_invoice()
    {
        $this->actingAs($this->user);

        $invoiceData = [
            'supplier_id' => $this->supplier->id,
            'invoice_date' => now()->format('Y-m-d'),
            'notes' => 'فاتورة تجريبية',
            'items' => [
                [
                    'product_id' => $this->products[0]->id,
                    'quantity' => 5,
                    'cost_price' => 75.00,
                    'sales_price' => 120.00,
                ],
                [
                    'product_id' => $this->products[1]->id,
                    'quantity' => 3,
                    'cost_price' => 140.00,
                    'sales_price' => 180.00,
                ],
            ],
            'withdraw_from_cashbox' => false,
            'currency' => 'IQD',
        ];

        $response = $this->post(route('purchase-invoices.store'), $invoiceData);

        $response->assertRedirect(route('purchase-invoices.index'));
        $response->assertSessionHas('success');

        // Check invoice was created
        $this->assertDatabaseHas('purchase_invoices', [
            'supplier_id' => $this->supplier->id,
            'total_amount' => 705.00, // (5 * 75) + (3 * 140)
            'created_by' => $this->user->id,
        ]);

        // Check invoice items were created
        $invoice = PurchaseInvoice::latest()->first();
        $this->assertCount(2, $invoice->items);

        // Check products were updated
        $this->products[0]->refresh();
        $this->products[1]->refresh();
        
        $this->assertEquals(15, $this->products[0]->quantity); // 10 + 5
        $this->assertEquals(8, $this->products[1]->quantity);  // 5 + 3
        $this->assertEquals(120.00, $this->products[0]->price);
        $this->assertEquals(180.00, $this->products[1]->price);
    }

    /** @test */
    public function can_create_purchase_invoice_with_cashbox_withdrawal()
    {
        $this->actingAs($this->user);

        // Create main box user
        $userAccount = UserType::create(['name' => 'account']);
        $mainBox = User::create([
            'name' => 'الصندوق الرئيسي',
            'email' => 'mainBox@account.com',
            'password' => bcrypt('password'),
            'type_id' => $userAccount->id,
        ]);

        $invoiceData = [
            'supplier_id' => $this->supplier->id,
            'invoice_date' => now()->format('Y-m-d'),
            'notes' => 'فاتورة مع سحب من الصندوق',
            'items' => [
                [
                    'product_id' => $this->products[0]->id,
                    'quantity' => 2,
                    'cost_price' => 80.00,
                    'sales_price' => 100.00,
                ],
            ],
            'withdraw_from_cashbox' => true,
            'currency' => 'IQD',
        ];

        $response = $this->post(route('purchase-invoices.store'), $invoiceData);

        $response->assertRedirect(route('purchase-invoices.index'));

        // Check cashbox transaction was created
        $this->assertDatabaseHas('cashbox_transactions', [
            'type' => 'withdrawal',
            'amount' => 160.00, // 2 * 80
            'currency' => 'IQD',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function creates_price_history_when_price_changes()
    {
        $this->actingAs($this->user);

        $invoiceData = [
            'supplier_id' => $this->supplier->id,
            'invoice_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'product_id' => $this->products[0]->id,
                    'quantity' => 1,
                    'cost_price' => 80.00,
                    'sales_price' => 150.00, // Different from current price (100.00)
                ],
            ],
            'withdraw_from_cashbox' => false,
            'currency' => 'IQD',
        ];

        $this->post(route('purchase-invoices.store'), $invoiceData);

        // Check price history was created
        $this->assertDatabaseHas('product_price_history', [
            'product_id' => $this->products[0]->id,
            'old_price' => 100.00,
            'new_price' => 150.00,
            'change_reason' => 'purchase',
            'changed_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function can_search_products()
    {
        $this->actingAs($this->user);

        // Search by name
        $response = $this->get(route('purchase-invoices.search-products', ['q' => 'تجريبي 1']));
        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'منتج تجريبي 1']);

        // Search by barcode
        $response = $this->get(route('purchase-invoices.search-products', ['q' => '123456789']));
        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['barcode' => '123456789']);
    }

    /** @test */
    public function can_search_suppliers()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('purchase-invoices.search-suppliers', ['q' => 'مورد']));
        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'مورد تجريبي']);
    }

    /** @test */
    public function can_view_purchase_invoices_index()
    {
        $this->actingAs($this->user);

        // Create a test invoice
        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'total_amount' => 500.00,
            'invoice_date' => now(),
            'invoice_number' => 'PI2025010001',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('purchase-invoices.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->component('PurchaseInvoices/Index')
                ->has('purchaseInvoices.data')
                ->has('suppliers')
        );
    }

    /** @test */
    public function can_view_purchase_invoice_create_page()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('purchase-invoices.create'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->component('PurchaseInvoices/Create')
                ->has('suppliers')
                ->has('products')
        );
    }

    /** @test */
    public function can_delete_purchase_invoice()
    {
        $this->actingAs($this->user);

        // Create invoice with items
        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'total_amount' => 500.00,
            'invoice_date' => now(),
            'invoice_number' => 'PI2025010001',
            'created_by' => $this->user->id,
        ]);

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $this->products[0]->id,
            'quantity' => 5,
            'cost_price' => 80.00,
            'sales_price' => 100.00,
            'total' => 400.00,
        ]);

        // Update product quantity
        $this->products[0]->update(['quantity' => 15]);

        $response = $this->delete(route('purchase-invoices.destroy', $invoice->id));

        $response->assertRedirect(route('purchase-invoices.index'));

        // Check invoice was deleted
        $this->assertDatabaseMissing('purchase_invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('purchase_invoice_items', ['purchase_invoice_id' => $invoice->id]);

        // Check product quantity was reversed
        $this->products[0]->refresh();
        $this->assertEquals(10, $this->products[0]->quantity); // 15 - 5
    }

    /** @test */
    public function validates_required_fields()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('purchase-invoices.store'), []);

        $response->assertSessionHasErrors([
            'invoice_date',
            'items',
            'currency',
        ]);
    }

    /** @test */
    public function validates_items_structure()
    {
        $this->actingAs($this->user);

        $invalidData = [
            'invoice_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'product_id' => 999, // Non-existent product
                    'quantity' => 0,     // Invalid quantity
                    'cost_price' => -10, // Invalid price
                ],
            ],
            'currency' => 'IQD',
        ];

        $response = $this->post(route('purchase-invoices.store'), $invalidData);

        $response->assertSessionHasErrors([
            'items.0.product_id',
            'items.0.quantity',
            'items.0.cost_price',
        ]);
    }

    /** @test */
    public function generates_unique_invoice_numbers()
    {
        $this->actingAs($this->user);

        // Create multiple invoices
        $invoice1 = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'total_amount' => 100.00,
            'invoice_date' => now(),
            'invoice_number' => PurchaseInvoice::generateInvoiceNumber(),
            'created_by' => $this->user->id,
        ]);

        $invoice2 = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'total_amount' => 200.00,
            'invoice_date' => now(),
            'invoice_number' => PurchaseInvoice::generateInvoiceNumber(),
            'created_by' => $this->user->id,
        ]);

        $this->assertNotEquals($invoice1->invoice_number, $invoice2->invoice_number);
        $this->assertStringStartsWith('PI', $invoice1->invoice_number);
        $this->assertStringStartsWith('PI', $invoice2->invoice_number);
    }

    /** @test */
    public function calculates_invoice_total_correctly()
    {
        $this->actingAs($this->user);

        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'total_amount' => 0,
            'invoice_date' => now(),
            'created_by' => $this->user->id,
        ]);

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $this->products[0]->id,
            'quantity' => 3,
            'cost_price' => 50.00,
            'sales_price' => 80.00,
            'total' => 150.00,
        ]);

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $this->products[1]->id,
            'quantity' => 2,
            'cost_price' => 100.00,
            'sales_price' => 150.00,
            'total' => 200.00,
        ]);

        $calculatedTotal = $invoice->calculateTotal();
        $this->assertEquals(350.00, $calculatedTotal);
    }

    /** @test */
    public function handles_database_transactions_correctly()
    {
        $this->actingAs($this->user);

        // Mock a database error by making product_id invalid
        $invalidData = [
            'supplier_id' => $this->supplier->id,
            'invoice_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'product_id' => 999999, // Non-existent product
                    'quantity' => 1,
                    'cost_price' => 50.00,
                    'sales_price' => 80.00,
                ],
            ],
            'currency' => 'IQD',
        ];

        $response = $this->post(route('purchase-invoices.store'), $invalidData);

        // Should not create any records due to transaction rollback
        $this->assertDatabaseCount('purchase_invoices', 0);
        $this->assertDatabaseCount('purchase_invoice_items', 0);
        $this->assertDatabaseCount('product_price_history', 0);
    }
}
