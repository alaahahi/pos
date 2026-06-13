<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleInventoryTest extends TestCase
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
            'email' => 'vehicle-test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->assignRole('admin');

        $this->withoutVite();
    }

    /** @test */
    public function vehicles_index_lists_inventory_with_stats(): void
    {
        $product = Product::create([
            'name' => 'Toyota Camry',
            'price' => 20000000,
            'price_cost' => 18000000,
            'quantity' => 1,
            'is_active' => true,
        ]);

        $invoice = PurchaseInvoice::create([
            'total_amount' => 18000000,
            'invoice_date' => now(),
            'invoice_number' => 'PI-TEST-001',
            'created_by' => $this->user->id,
        ]);

        Vehicle::create([
            'vin' => '1HGBH41JXMN109186',
            'color' => 'أبيض',
            'vehicle_model' => 'Camry',
            'make' => 'Toyota',
            'year' => '2024',
            'product_id' => $product->id,
            'purchase_invoice_id' => $invoice->id,
            'status' => 'available',
        ]);

        Vehicle::create([
            'vin' => '2HGBH41JXMN109187',
            'color' => 'أسود',
            'vehicle_model' => 'Corolla',
            'make' => 'Toyota',
            'year' => '2023',
            'product_id' => $product->id,
            'status' => 'sold',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('vehicles.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Vehicles/Index')
            ->has('vehicles.data', 2)
            ->where('stats.available', 1)
            ->where('stats.sold', 1)
            ->where('stats.total', 2)
        );
    }

    /** @test */
    public function vehicles_index_filters_by_status(): void
    {
        $product = Product::create([
            'name' => 'Test Car',
            'price' => 100,
            'price_cost' => 80,
            'quantity' => 2,
            'is_active' => true,
        ]);

        Vehicle::create([
            'vin' => '1HGBH41JXMN109186',
            'product_id' => $product->id,
            'status' => 'available',
        ]);

        Vehicle::create([
            'vin' => '2HGBH41JXMN109187',
            'product_id' => $product->id,
            'status' => 'sold',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('vehicles.index', ['status' => 'available']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.status', 'available')
        );
    }
}
