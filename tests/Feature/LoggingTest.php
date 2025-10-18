<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use Spatie\Permission\Models\Permission;
use App\Services\LogService;

class LoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Use in-memory SQLite for faster tests
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        Artisan::call('migrate');
    }

    public function test_log_service_creates_log_entry(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        LogService::createLog('TestModule', 'TestAction', 123, ['a' => 1], ['b' => 2], 'info');

        $this->assertDatabaseHas('logs', [
            'module_name' => 'TestModule',
            'action' => 'TestAction',
            'affected_record_id' => 123,
            'by_user_id' => $user->id,
        ]);
    }

    public function test_frontend_logs_endpoint_stores_log(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $payload = [
            'module_name' => 'UI',
            'action' => 'Clicked Button',
            'affected_record_id' => 0,
            'updated_data' => ['x' => 1],
        ];

        $response = $this->post('/logs', $payload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('logs', [
            'module_name' => 'UI',
            'action' => 'Clicked Button',
            'by_user_id' => $user->id,
        ]);
    }

    public function test_customer_store_creates_log(): void
    {
        $user = User::factory()->create();
        // Grant necessary permissions
        Permission::findOrCreate('create customer');
        Permission::findOrCreate('read customers');
        $user->givePermissionTo('create customer', 'read customers');
        $this->actingAs($user);

        $response = $this->post('/customers', [
            'name' => 'Ahmed',
        ]);
        $response->assertStatus(302);

        $this->assertDatabaseHas('logs', [
            'module_name' => 'Customer',
            'action' => 'Created',
            'badge' => 'success',
            'by_user_id' => $user->id,
        ]);
    }

    public function test_product_update_creates_log(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('create product');
        Permission::findOrCreate('update product');
        Permission::findOrCreate('read product');
        $user->givePermissionTo('create product', 'update product', 'read product');
        $this->actingAs($user);

        // Create product via controller
        $createResponse = $this->post('/products', [
            'name' => 'P1',
            'price' => 10,
            'price_cost' => 5,
            'quantity' => 1,
        ]);
        $createResponse->assertStatus(302);
        $product = Product::first();

        // Update product via controller (route uses POST for update)
        $updateResponse = $this->post('/products/'.$product->id, [
            'name' => 'P1 updated',
            'price' => 12,
            'price_cost' => 6,
            'quantity' => 2,
        ]);
        $updateResponse->assertStatus(302);

        $this->assertDatabaseHas('logs', [
            'module_name' => 'Product',
            'action' => 'Updated',
            'by_user_id' => $user->id,
        ]);
    }

    public function test_api_order_creation_creates_log(): void
    {
        // Create required models
        $customer = Customer::create(['name' => 'C1']);
        $product = Product::create([
            'name' => 'P1',
            'price' => 10,
            'quantity' => 5,
        ]);

        $payload = [
            'customer_id' => $customer->id,
            'total_amount' => 10,
            'total_paid' => 0,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'price' => 10],
            ],
        ];

        $response = $this->postJson('/api/createOrder', $payload);
        $response->assertStatus(201);

        $this->assertDatabaseHas('logs', [
            'module_name' => 'Order',
            'action' => 'Created',
        ]);
    }
}
