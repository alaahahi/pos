<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierCreationTest extends TestCase
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
            'email' => 'supplier-test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->assignRole('admin');
    }

    /** @test */
    public function can_create_supplier_with_uuid_and_balance(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('suppliers.store'), [
                'name' => 'مورد تجريبي',
                'phone' => '07901234567',
                'address' => 'بغداد',
                'notes' => 'ملاحظة',
            ]);

        $response->assertRedirect(route('suppliers.index'));
        $response->assertSessionHas('success');

        $supplier = Supplier::where('name', 'مورد تجريبي')->first();
        $this->assertNotNull($supplier);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $supplier->id
        );

        $this->assertDatabaseHas('supplier_balances', [
            'supplier_id' => $supplier->id,
            'balance_dollar' => 0,
            'balance_dinar' => 0,
        ]);
    }

    /** @test */
    public function supplier_create_page_loads(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('suppliers.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Supplier/Create'));
    }
}
