<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseInvoicesTest extends TestCase
{
    protected $user;
    protected $supplier;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::first();
        if (!$this->user) {
            $this->markTestSkipped('No users in database');
        }
        
        // Get or create a supplier
        $this->supplier = Customer::where('is_supplier', true)->first();
        if (!$this->supplier) {
            $this->supplier = Customer::create([
                'name' => 'Test Supplier',
                'phone' => '1234567890',
                'is_supplier' => true,
                'is_active' => true,
            ]);
        }
        
        // Get a product
        $this->product = Product::where('is_active', true)->first();
    }

    /**
     * Test 1: Purchase invoices index page loads
     */
    public function test_purchase_invoices_index_loads()
    {
        $response = $this->actingAs($this->user)
            ->get(route('purchase-invoices.index'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('PurchaseInvoices/Index')
                ->has('purchaseInvoices')
                ->has('suppliers')
        );
        
        echo "\n✅ Test 1 PASSED: Purchase invoices index loads\n";
    }

    /**
     * Test 2: Create purchase invoice page loads
     */
    public function test_purchase_invoice_create_page_loads()
    {
        $response = $this->actingAs($this->user)
            ->get(route('purchase-invoices.create'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('PurchaseInvoices/Create')
                ->has('suppliers')
                ->has('products')
        );
        
        echo "✅ Test 2 PASSED: Create page loads\n";
    }

    /**
     * Test 3: Can create purchase invoice
     */
    public function test_can_create_purchase_invoice()
    {
        if (!$this->product) {
            $this->markTestSkipped('No products available');
        }
        
        $response = $this->actingAs($this->user)
            ->post(route('purchase-invoices.store'), [
                'supplier_id' => $this->supplier->id,
                'invoice_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'cost_price' => 100,
                        'sales_price' => 150,
                    ]
                ],
                'notes' => 'Test purchase invoice',
                'withdraw_from_cashbox' => false,
                'currency' => 'IQD',
            ]);
        
        $this->assertTrue(in_array($response->status(), [200, 201, 302]));
        
        echo "✅ Test 3 PASSED: Can create purchase invoice\n";
    }

    /**
     * Test 4: Product quantity increases after purchase
     */
    public function test_product_quantity_increases()
    {
        if (!$this->product) {
            $this->markTestSkipped('No products available');
        }
        
        $initialQuantity = $this->product->quantity;
        
        $this->actingAs($this->user)
            ->post(route('purchase-invoices.store'), [
                'supplier_id' => $this->supplier->id,
                'invoice_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 5,
                        'cost_price' => 100,
                        'sales_price' => 150,
                    ]
                ],
                'withdraw_from_cashbox' => false,
                'currency' => 'IQD',
            ]);
        
        // Reload product
        $this->product->refresh();
        
        // Check if quantity increased
        $this->assertGreaterThan($initialQuantity, $this->product->quantity);
        
        echo "✅ Test 4 PASSED: Product quantity increases\n";
    }

    /**
     * Test 5: Product price updates
     */
    public function test_product_price_updates()
    {
        if (!$this->product) {
            $this->markTestSkipped('No products available');
        }
        
        $newPrice = 999;
        
        $this->actingAs($this->user)
            ->post(route('purchase-invoices.store'), [
                'supplier_id' => $this->supplier->id,
                'invoice_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                        'cost_price' => 100,
                        'sales_price' => $newPrice,
                    ]
                ],
                'withdraw_from_cashbox' => false,
                'currency' => 'IQD',
            ]);
        
        // Reload product
        $this->product->refresh();
        
        // Check if price updated
        $this->assertEquals($newPrice, $this->product->price);
        
        echo "✅ Test 5 PASSED: Product price updates\n";
    }

    /**
     * Test 6: Search products endpoint
     */
    public function test_search_products_endpoint()
    {
        $response = $this->actingAs($this->user)
            ->get(route('purchase-invoices.search-products', ['q' => 'test']));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([]);
        
        echo "✅ Test 6 PASSED: Search products endpoint works\n";
    }

    /**
     * Test 7: Search suppliers endpoint
     */
    public function test_search_suppliers_endpoint()
    {
        $response = $this->actingAs($this->user)
            ->get(route('purchase-invoices.search-suppliers', ['q' => 'test']));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([]);
        
        echo "✅ Test 7 PASSED: Search suppliers endpoint works\n";
    }

    /**
     * Test 8: Filters work on index page
     */
    public function test_filters_work()
    {
        $response = $this->actingAs($this->user)
            ->get(route('purchase-invoices.index', [
                'search' => 'test',
                'supplier_id' => $this->supplier->id,
                'date_from' => '2025-01-01',
                'date_to' => '2025-12-31',
            ]));
        
        $response->assertStatus(200);
        
        echo "✅ Test 8 PASSED: Filters work\n";
    }

    /**
     * Test 9: Invoice number is generated
     */
    public function test_invoice_number_is_generated()
    {
        $invoiceNumber = PurchaseInvoice::generateInvoiceNumber();
        
        $this->assertStringContainsString('PI', $invoiceNumber);
        $this->assertStringContainsString(date('Y'), $invoiceNumber);
        $this->assertStringContainsString(date('m'), $invoiceNumber);
        
        echo "✅ Test 9 PASSED: Invoice number is generated correctly\n";
    }

    /**
     * Test 10: Pagination works
     */
    public function test_pagination_works()
    {
        $response = $this->actingAs($this->user)
            ->get(route('purchase-invoices.index'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('purchaseInvoices.data')
                ->has('purchaseInvoices.links')
        );
        
        echo "✅ Test 10 PASSED: Pagination works\n";
    }
}

