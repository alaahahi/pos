<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseInvoicePriceCostTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        $this->seed(\Database\Seeders\UserRolePermissionSeeder::class);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'purchase-cost-test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->assignRole('admin');
    }

    /** @test */
    public function auto_created_product_gets_price_cost_on_purchase(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('purchase-invoices.store'), [
                'invoice_date' => now()->toDateString(),
                'currency' => 'IQD',
                'items' => [
                    [
                        'product_name' => 'سيارة جديدة',
                        'quantity' => 1,
                        'cost_price' => 15000000,
                        'sales_price' => 18000000,
                    ],
                ],
            ]);

        $response->assertRedirect(route('purchase-invoices.index'));

        $product = Product::where('name', 'سيارة جديدة')->first();
        $this->assertNotNull($product);
        $this->assertEquals(15000000, (float) $product->price_cost);
        $this->assertEquals(18000000, (float) $product->price);
    }

    /** @test */
    public function existing_product_price_cost_updated_on_purchase(): void
    {
        $product = Product::create([
            'name' => 'منتج موجود',
            'price' => 100,
            'price_cost' => 0,
            'quantity' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->post(route('purchase-invoices.store'), [
                'invoice_date' => now()->toDateString(),
                'currency' => 'IQD',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                        'cost_price' => 75,
                        'sales_price' => 120,
                    ],
                ],
            ]);

        $product->refresh();
        $this->assertEquals(75, (float) $product->price_cost);
        $this->assertEquals(120, (float) $product->price);
        $this->assertEquals(7, $product->quantity);
    }
}
