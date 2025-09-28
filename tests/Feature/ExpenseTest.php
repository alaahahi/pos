<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use App\Models\Box;
use App\Models\SystemConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $box;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations
        $this->artisan('migrate');
        
        // Seed roles and permissions
        $this->seed(\Database\Seeders\UserRolePermissionSeeder::class);
        
        // Create a user with admin role
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->assignRole('admin');
        
        // Create a box
        $this->box = Box::create([
            'name' => 'Test Box',
            'balance' => 1000000, // 1,000,000 IQD
            'balance_usd' => 1000, // 1,000 USD
            'is_active' => true,
        ]);

        // Create system config with exchange rate
        SystemConfig::create([
            'exchange_rate' => 1500, // 1 USD = 1500 IQD
        ]);
    }

    /** @test */
    public function user_can_create_expense_in_iqd()
    {
        $expenseData = [
            'title' => 'راتب الموظف',
            'description' => 'راتب شهري للموظف',
            'amount' => 500000,
            'currency' => 'IQD',
            'category' => 'salaries',
            'expense_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('expenses.store'), $expenseData);

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('expenses', [
            'title' => 'راتب الموظف',
            'amount' => 500000,
            'currency' => 'IQD',
            'category' => 'salaries',
        ]);

        // Check if box balance is updated
        $this->box->refresh();
        $this->assertEquals(500000, $this->box->balance); // 1,000,000 - 500,000
    }

    /** @test */
    public function user_can_create_expense_in_usd()
    {
        $expenseData = [
            'title' => 'مصاريف المحل',
            'description' => 'إيجار المحل',
            'amount' => 100,
            'currency' => 'USD',
            'category' => 'shop_expenses',
            'expense_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('expenses.store'), $expenseData);

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('expenses', [
            'title' => 'مصاريف المحل',
            'amount' => 100,
            'currency' => 'USD',
            'category' => 'shop_expenses',
        ]);

        // Check if box balance is updated (100 USD from USD balance)
        $this->box->refresh();
        $this->assertEquals(900, $this->box->balance_usd); // 1,000 - 100
    }

    /** @test */
    public function user_can_update_expense()
    {
        $expense = Expense::create([
            'title' => 'مصاريف قديمة',
            'amount' => 100000,
            'currency' => 'IQD',
            'category' => 'other',
            'expense_date' => now(),
            'created_by' => $this->user->id,
        ]);

        $updateData = [
            'title' => 'مصاريف محدثة',
            'description' => 'وصف محدث',
            'amount' => 200000,
            'currency' => 'IQD',
            'category' => 'utilities',
            'expense_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->put(route('expenses.update', $expense), $updateData);

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'title' => 'مصاريف محدثة',
            'amount' => 200000,
            'category' => 'utilities',
        ]);
    }

    /** @test */
    public function user_can_delete_expense()
    {
        $expense = Expense::create([
            'title' => 'مصاريف للحذف',
            'amount' => 50000,
            'currency' => 'IQD',
            'category' => 'other',
            'expense_date' => now(),
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('expenses.destroy', $expense));

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('expenses', [
            'id' => $expense->id,
        ]);
    }

    /** @test */
    public function expense_validation_works()
    {
        $response = $this->actingAs($this->user)
            ->post(route('expenses.store'), []);

        $response->assertSessionHasErrors(['title', 'amount', 'currency', 'category', 'expense_date']);
    }

    /** @test */
    public function expense_currency_validation_works()
    {
        $expenseData = [
            'title' => 'مصاريف',
            'amount' => 100,
            'currency' => 'EUR', // Invalid currency
            'category' => 'other',
            'expense_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('expenses.store'), $expenseData);

        $response->assertSessionHasErrors(['currency']);
    }

    /** @test */
    public function expense_amount_validation_works()
    {
        $expenseData = [
            'title' => 'مصاريف',
            'amount' => -100, // Negative amount
            'currency' => 'IQD',
            'category' => 'other',
            'expense_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('expenses.store'), $expenseData);

        $response->assertSessionHasErrors(['amount']);
    }

    /** @test */
    public function expense_categories_are_available()
    {
        $categories = Expense::getCategories();
        
        $this->assertArrayHasKey('salaries', $categories);
        $this->assertArrayHasKey('shop_expenses', $categories);
        $this->assertArrayHasKey('utilities', $categories);
        $this->assertArrayHasKey('maintenance', $categories);
        $this->assertArrayHasKey('marketing', $categories);
        $this->assertArrayHasKey('other', $categories);
    }

    /** @test */
    public function expense_currencies_are_available()
    {
        $currencies = Expense::getCurrencies();
        
        $this->assertArrayHasKey('IQD', $currencies);
        $this->assertArrayHasKey('USD', $currencies);
        $this->assertEquals('دينار عراقي', $currencies['IQD']);
        $this->assertEquals('دولار أمريكي', $currencies['USD']);
    }

    /** @test */
    public function expense_formatted_amount_works()
    {
        $expense = new Expense([
            'amount' => 150000.50,
            'currency' => 'IQD',
        ]);

        $this->assertEquals('150,000.50 IQD', $expense->formatted_amount);
    }

    /** @test */
    public function expense_creator_relationship_works()
    {
        $expense = Expense::create([
            'title' => 'مصاريف',
            'amount' => 100000,
            'currency' => 'IQD',
            'category' => 'other',
            'expense_date' => now(),
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals($this->user->id, $expense->creator->id);
    }
}