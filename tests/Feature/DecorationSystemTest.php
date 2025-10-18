<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Decoration;
use App\Models\DecorationOrder;
use App\Models\Customer;
use App\Models\EmployeeCommission;
use App\Models\MonthlyAccount;
use App\Models\Box;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;

class DecorationSystemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $employee;
    protected $customer;
    protected $decoration;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'employee']);
        
        // Create admin user
        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
        
        // Create employee with commission
        $this->employee = User::factory()->create([
            'name' => 'Employee User',
            'email' => 'employee@test.com',
            'is_active' => true,
            'commission_enabled' => true,
            'commission_rate_percent' => 5.0, // 5%
        ]);
        $this->employee->assignRole('employee');
        
        // Create customer
        $this->customer = Customer::factory()->create([
            'name' => 'Test Customer',
            'phone' => '07701234567',
            'email' => 'customer@test.com',
            'is_active' => true,
        ]);
        
        // Create decoration
        $this->decoration = Decoration::factory()->create([
            'name' => 'Birthday Decoration',
            'type' => 'birthday',
            'base_price' => 100.00,
            'base_price_dollar' => 100.00,
            'base_price_dinar' => 150000.00,
            'currency' => 'dollar',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function test_can_create_decoration_order()
    {
        $this->actingAs($this->admin);
        
        $orderData = [
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_phone' => $this->customer->phone,
            'customer_email' => $this->customer->email,
            'event_address' => '123 Test Street, Baghdad',
            'event_date' => now()->addDays(7)->format('Y-m-d'),
            'event_time' => '18:00',
            'guest_count' => 50,
            'special_requests' => 'Blue and white theme',
            'base_price' => 100.00,
            'additional_cost' => 20.00,
            'discount' => 10.00,
            'total_price' => 110.00,
        ];
        
        $response = $this->post(route('decoration.orders.store'), $orderData);
        
        // Check if there are validation errors
        if ($response->status() === 200) {
            $this->fail('Order creation failed. Response: ' . $response->getContent());
        }
        
        $response->assertStatus(302);
        $this->assertDatabaseHas('decoration_orders', [
            'decoration_id' => $this->decoration->id,
            'customer_name' => $this->customer->name,
            'total_price' => 110.00,
            'status' => 'created',
        ]);
    }

    /** @test */
    public function test_can_assign_employee_to_order()
    {
        $this->actingAs($this->admin);
        
        $order = DecorationOrder::factory()->create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'total_price' => 100.00,
            'status' => 'created',
        ]);
        
        $response = $this->patch(route('decoration.orders.status', $order), [
            'assigned_employee_id' => $this->employee->id,
            'status' => 'received',
        ]);
        
        $response->assertStatus(302);
        $order->refresh();
        
        $this->assertEquals($this->employee->id, $order->assigned_employee_id);
        $this->assertEquals('received', $order->status);
        $this->assertNotNull($order->received_at);
    }

    /** @test */
    public function test_order_status_progression()
    {
        $this->actingAs($this->admin);
        
        $order = DecorationOrder::factory()->create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'assigned_employee_id' => $this->employee->id,
            'total_price' => 100.00,
            'status' => 'created',
        ]);
        
        // Test status progression: created -> received -> executing -> completed
        $statuses = ['received', 'executing', 'completed'];
        
        foreach ($statuses as $status) {
            $response = $this->patch(route('decoration.orders.status', $order), [
                'status' => $status,
            ]);
            
            $response->assertStatus(302);
            $order->refresh();
            $this->assertEquals($status, $order->status);
            
            // Check timestamp fields
            $timestampField = $status . '_at';
            if ($status === 'completed') {
                $this->assertNotNull($order->completed_at);
            } else {
                $this->assertNotNull($order->$timestampField);
            }
        }
    }

    /** @test */
    public function test_commission_accrual_on_order_completion()
    {
        $this->actingAs($this->admin);
        
        $order = DecorationOrder::factory()->create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'assigned_employee_id' => $this->employee->id,
            'total_price' => 100.00,
            'currency' => 'dollar',
            'status' => 'executing',
        ]);
        
        // Complete the order
        $response = $this->patch(route('decoration.orders.status', $order), [
            'status' => 'completed',
        ]);
        
        $response->assertStatus(302);
        
        // Check commission was created
        $commission = EmployeeCommission::where('user_id', $this->employee->id)
            ->where('decoration_order_id', $order->id)
            ->first();
            
        $this->assertNotNull($commission);
        $this->assertEquals(5.0, $commission->commission_rate_percent);
        $this->assertEquals(100.00, $commission->base_amount);
        $this->assertEquals(5.00, $commission->commission_amount); // 5% of 100
        $this->assertEquals('USD', $commission->currency);
        $this->assertEquals('accrued', $commission->status);
    }

    /** @test */
    public function test_no_duplicate_commission_on_multiple_completions()
    {
        $this->actingAs($this->admin);
        
        $order = DecorationOrder::factory()->create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'assigned_employee_id' => $this->employee->id,
            'total_price' => 100.00,
            'currency' => 'dollar',
            'status' => 'executing',
        ]);
        
        // Complete the order first time
        $this->patch(route('decoration.orders.status', $order), [
            'status' => 'completed',
        ]);
        
        // Complete the order multiple times more
        for ($i = 0; $i < 2; $i++) {
            $this->patch(route('decoration.orders.status', $order), [
                'status' => 'completed',
            ]);
        }
        
        // Should only have one commission record
        $commissionCount = EmployeeCommission::where('user_id', $this->employee->id)
            ->where('decoration_order_id', $order->id)
            ->count();
            
        $this->assertEquals(1, $commissionCount);
    }

    /** @test */
    public function test_payment_processing()
    {
        $this->actingAs($this->admin);
        
        $order = DecorationOrder::factory()->create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'total_price' => 100.00,
            'currency' => 'dollar',
            'paid_amount' => 0,
            'status' => 'received',
        ]);
        
        // Add partial payment
        $response = $this->post(route('decoration.payments.add'), [
            'order_id' => $order->id,
            'amount' => 50.00,
            'currency' => 'dollar',
            'payment_method' => 'cash',
            'notes' => 'Partial payment',
        ]);
        
        $response->assertJson(['success' => true]);
        
        $order->refresh();
        $this->assertEquals(50.00, $order->paid_amount);
        $this->assertEquals('partial_payment', $order->status);
        
        // Check payment record
        $payment = Box::where('morphed_type', DecorationOrder::class)
            ->where('morphed_id', $order->id)
            ->where('type', 'payment')
            ->first();
            
        $this->assertNotNull($payment);
        $this->assertEquals(50.00, $payment->amount);
        $this->assertEquals('USD', $payment->currency);
    }

    /** @test */
    public function test_prevent_overpayment()
    {
        $this->actingAs($this->admin);
        
        $order = DecorationOrder::factory()->create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'total_price' => 100.00,
            'currency' => 'dollar',
            'paid_amount' => 100.00, // Already fully paid
            'status' => 'full_payment',
        ]);
        
        // Try to add another payment
        $response = $this->post(route('decoration.payments.add'), [
            'order_id' => $order->id,
            'amount' => 10.00,
            'currency' => 'dollar',
            'payment_method' => 'cash',
        ]);
        
        $response->assertJson(['success' => false]);
        $response->assertJsonFragment(['message' => 'الطلب مدفوع بالكامل بالفعل - لا يمكن إضافة دفعة أخرى']);
    }

    /** @test */
    public function test_prevent_payment_exceeding_remaining_amount()
    {
        $this->actingAs($this->admin);
        
        $order = DecorationOrder::factory()->create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'total_price' => 100.00,
            'currency' => 'dollar',
            'paid_amount' => 80.00, // 20 remaining
            'status' => 'partial_payment',
        ]);
        
        // Try to pay more than remaining
        $response = $this->post(route('decoration.payments.add'), [
            'order_id' => $order->id,
            'amount' => 30.00, // More than 20 remaining
            'currency' => 'dollar',
            'payment_method' => 'cash',
        ]);
        
        $response->assertJson(['success' => false]);
        $response->assertJsonFragment(['message' => 'المبلغ المدخل أكبر من المبلغ المتبقي (20.00)']);
    }

    /** @test */
    public function test_monthly_account_calculation_with_commissions()
    {
        $this->actingAs($this->admin);
        
        // Create orders and commissions for current month
        $order1 = DecorationOrder::factory()->create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'assigned_employee_id' => $this->employee->id,
            'total_price' => 100.00,
            'currency' => 'dollar',
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        
        $order2 = DecorationOrder::factory()->create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'assigned_employee_id' => $this->employee->id,
            'total_price' => 200.00,
            'currency' => 'dollar',
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        
        // Create commissions manually (simulating auto-creation)
        $periodMonth = now()->startOfMonth()->toDateString();
        
        EmployeeCommission::create([
            'user_id' => $this->employee->id,
            'decoration_order_id' => $order1->id,
            'commission_rate_percent' => 5.0,
            'base_amount' => 100.00,
            'commission_amount' => 5.00,
            'currency' => 'USD',
            'status' => 'accrued',
            'period_month' => $periodMonth,
        ]);
        
        EmployeeCommission::create([
            'user_id' => $this->employee->id,
            'decoration_order_id' => $order2->id,
            'commission_rate_percent' => 5.0,
            'base_amount' => 200.00,
            'commission_amount' => 10.00,
            'currency' => 'USD',
            'status' => 'accrued',
            'period_month' => $periodMonth,
        ]);
        
        // Get current month and calculate
        $monthlyAccount = MonthlyAccount::getCurrentMonth();
        $monthlyAccount->calculateMonthlyData();
        
        $this->assertEquals(15.00, $monthlyAccount->total_commissions_usd); // 5 + 10
        $this->assertEquals(0.00, $monthlyAccount->total_commissions_iqd);
    }

    /** @test */
    public function test_commission_payout()
    {
        $this->actingAs($this->admin);
        
        // Create a real order first
        $order = DecorationOrder::factory()->create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'assigned_employee_id' => $this->employee->id,
            'total_price' => 100.00,
            'currency' => 'dollar',
            'status' => 'completed',
        ]);
        
        // Create commission for current month
        $periodMonth = now()->startOfMonth()->toDateString();
        
        $commission = EmployeeCommission::create([
            'user_id' => $this->employee->id,
            'decoration_order_id' => $order->id,
            'commission_rate_percent' => 5.0,
            'base_amount' => 100.00,
            'commission_amount' => 5.00,
            'currency' => 'USD',
            'status' => 'accrued',
            'period_month' => $periodMonth,
        ]);
        
        // Payout commissions
        $response = $this->post(route('decoration.monthly.payoutCommissions'), [
            'year' => now()->year,
            'month' => now()->month,
        ]);
        
        $response->assertJson(['success' => true]);
        
        $commission->refresh();
        $this->assertEquals('paid', $commission->status);
        $this->assertNotNull($commission->paid_at);
        $this->assertEquals($this->admin->id, $commission->paid_by);
    }

    /** @test */
    public function test_decoration_crud_operations()
    {
        $this->actingAs($this->admin);
        
        // Test create decoration
        $decorationData = [
            'name' => 'Wedding Decoration',
            'description' => 'Beautiful wedding setup',
            'type' => 'wedding',
            'currency' => 'dollar',
            'base_price' => 500.00,
            'duration_hours' => 8,
            'team_size' => 4,
            'is_active' => true,
            'is_featured' => false,
        ];
        
        $response = $this->post(route('decorations.store'), $decorationData);
        $response->assertStatus(302);
        
        $decoration = Decoration::where('name', 'Wedding Decoration')->first();
        $this->assertNotNull($decoration);
        $this->assertEquals(500.00, $decoration->base_price_dollar);
        
        // Test update decoration
        $updateData = [
            'name' => 'Premium Wedding Decoration',
            'base_price' => 600.00,
            'base_price_dollar' => 600.00,
            'base_price_dinar' => 900000.00,
            'currency' => 'dollar',
            'description' => 'Updated description',
            'type' => 'wedding',
            'duration_hours' => 10,
            'team_size' => 5,
            'is_active' => true,
            'is_featured' => true,
        ];
        
        $response = $this->patch(route('decorations.update', $decoration), $updateData);
        $response->assertRedirect();
        
        $decoration->refresh();
        $this->assertEquals('Premium Wedding Decoration', $decoration->name);
        $this->assertEquals(600.00, $decoration->base_price_dollar);
        
        // Test delete decoration
        $response = $this->delete(route('decorations.destroy', $decoration));
        $response->assertRedirect();
        
        $this->assertDatabaseMissing('decorations', ['id' => $decoration->id]);
    }

    /** @test */
    public function test_employee_commission_settings()
    {
        $this->actingAs($this->admin);
        
        // Test create employee with commission
        $employeeData = [
            'name' => 'Commission Employee',
            'email' => 'commission@test.com',
            'password' => 'password123',
            'commission_enabled' => true,
            'commission_rate_percent' => 7.5,
            'selectedRoles' => ['employee'],
        ];
        
        $response = $this->post(route('users.store'), $employeeData);
        $response->assertStatus(302);
        
        $employee = User::where('email', 'commission@test.com')->first();
        $this->assertNotNull($employee);
        $this->assertTrue((bool) $employee->commission_enabled);
        $this->assertEquals(7.5, (float) $employee->commission_rate_percent);
        
        // Test update commission settings
        $updateData = [
            'name' => $employee->name,
            'email' => $employee->email,
            'commission_enabled' => false,
            'commission_rate_percent' => 0,
            'selectedRoles' => ['employee'],
        ];
        
        $response = $this->post(route('users.update', $employee), $updateData);
        $response->assertStatus(302);
        
        $employee->refresh();
        $this->assertFalse((bool) $employee->commission_enabled);
        $this->assertEquals(0, (float) $employee->commission_rate_percent);
    }

    /** @test */
    public function test_currency_handling_in_payments()
    {
        $this->actingAs($this->admin);
        
        // Test USD order and payment
        $usdOrder = DecorationOrder::factory()->create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'total_price' => 100.00,
            'currency' => 'dollar',
            'paid_amount' => 0,
        ]);
        
        $response = $this->post(route('decoration.payments.add'), [
            'order_id' => $usdOrder->id,
            'amount' => 50.00,
            'currency' => 'dollar',
            'payment_method' => 'cash',
        ]);
        
        $response->assertJson(['success' => true]);
        
        $payment = Box::where('morphed_id', $usdOrder->id)->where('type', 'payment')->first();
        $this->assertEquals('USD', $payment->currency);
        
        // Test IQD order and payment
        $iqdOrder = DecorationOrder::factory()->create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'total_price' => 150000.00,
            'currency' => 'dinar',
            'paid_amount' => 0,
        ]);
        
        $response = $this->post(route('decoration.payments.add'), [
            'order_id' => $iqdOrder->id,
            'amount' => 75000.00,
            'currency' => 'dinar',
            'payment_method' => 'cash',
        ]);
        
        $response->assertJson(['success' => true]);
        
        $payment = Box::where('morphed_id', $iqdOrder->id)->where('type', 'payment')->first();
        $this->assertEquals('IQD', $payment->currency);
    }

    /** @test */
    public function test_order_pricing_updates()
    {
        $this->actingAs($this->admin);
        
        $order = DecorationOrder::factory()->create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'base_price' => 100.00,
            'additional_cost' => 20.00,
            'discount' => 10.00,
            'total_price' => 110.00,
        ]);
        
        // Update pricing
        $pricingData = [
            'base_price' => 120.00,
            'additional_cost' => 30.00,
            'discount' => 15.00,
            'total_price' => 135.00,
            'notes' => 'Updated pricing due to additional requirements',
        ];
        
        $response = $this->patch(route('decoration.orders.pricing', $order), $pricingData);
        $response->assertRedirect();
        
        $order->refresh();
        $this->assertEquals(120.00, $order->base_price);
        $this->assertEquals(30.00, $order->additional_cost);
        $this->assertEquals(15.00, $order->discount);
        $this->assertEquals(135.00, $order->total_price);
    }
}
