<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\PurchaseInvoice;

class SimplePurchaseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_create_user_and_product()
    {
        // Create test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create supplier
        $supplier = Customer::create([
            'name' => 'مورد تجريبي',
            'phone' => '07901234567',
            'is_supplier' => true,
        ]);

        // Create test product
        $product = Product::create([
            'name' => 'منتج تجريبي',
            'barcode' => '123456789',
            'price' => 100.00,
            'price_cost' => 80.00,
            'quantity' => 10,
            'is_active' => true,
        ]);

        // Create purchase invoice
        $invoice = PurchaseInvoice::create([
            'supplier_id' => $supplier->id,
            'total_amount' => 500.00,
            'invoice_date' => now(),
            'invoice_number' => 'PI2025010001',
            'created_by' => $user->id,
        ]);

        // Assertions
        $this->assertDatabaseHas('users', ['name' => 'Test User']);
        $this->assertDatabaseHas('customers', ['name' => 'مورد تجريبي']);
        $this->assertDatabaseHas('products', ['name' => 'منتج تجريبي']);
        $this->assertDatabaseHas('purchase_invoices', ['invoice_number' => 'PI2025010001']);

        $this->assertTrue(true);
    }
}
