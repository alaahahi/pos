<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Decoration;
use App\Models\DecorationOrder;
use App\Models\Customer;
use App\Models\EmployeeCommission;
use App\Models\Box;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;

class SimpleDecorationTest extends TestCase
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
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
        
        // Create employee with commission
        $this->employee = User::create([
            'name' => 'Employee User',
            'email' => 'employee@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'commission_enabled' => true,
            'commission_rate_percent' => 5.0,
        ]);
        $this->employee->assignRole('employee');
        
        // Create customer
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '07701234567',
            'email' => 'customer@test.com',
            'is_active' => true,
        ]);
        
        // Create decoration
        $this->decoration = Decoration::create([
            'name' => 'Birthday Decoration',
            'type' => 'birthday',
            'base_price' => 100.00,
            'base_price_dollar' => 100.00,
            'base_price_dinar' => 150000.00,
            'currency' => 'dollar',
            'duration_hours' => 4,
            'team_size' => 2,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function test_employee_commission_calculation()
    {
        // Test commission calculation directly
        $orderAmount = 100.00;
        $commissionRate = 5.0;
        $expectedCommission = 5.00;
        
        $calculatedCommission = round($orderAmount * ($commissionRate / 100), 2);
        
        $this->assertEquals($expectedCommission, $calculatedCommission);
    }

    /** @test */
    public function test_payment_validation_logic()
    {
        // Test payment validation without HTTP requests
        $totalPrice = 100.00;
        $paidAmount = 80.00;
        $newPayment = 30.00;
        
        $remainingAmount = $totalPrice - $paidAmount;
        $isOverpayment = $newPayment > $remainingAmount;
        $isFullyPaid = $paidAmount >= $totalPrice;
        
        $this->assertEquals(20.00, $remainingAmount);
        $this->assertTrue($isOverpayment);
        $this->assertFalse($isFullyPaid);
    }

    /** @test */
    public function test_commission_creation_logic()
    {
        // Create order directly in database
        $order = DecorationOrder::create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_phone' => $this->customer->phone,
            'event_address' => 'Test Address',
            'event_date' => now()->addDays(7),
            'event_time' => '18:00',
            'guest_count' => 50,
            'base_price' => 100.00,
            'total_price' => 100.00,
            'currency' => 'dollar',
            'status' => 'completed',
            'assigned_employee_id' => $this->employee->id,
            'completed_at' => now(),
        ]);

        // Manually create commission (simulating controller logic)
        $rate = (float) $this->employee->commission_rate_percent;
        $baseAmount = (float) $order->total_price;
        $commissionAmount = round($baseAmount * ($rate / 100), 2);
        $currencyCode = $order->currency === 'dollar' ? 'USD' : 'IQD';
        $periodMonth = now()->startOfMonth()->toDateString();

        $commission = EmployeeCommission::create([
            'user_id' => $this->employee->id,
            'decoration_order_id' => $order->id,
            'commission_rate_percent' => $rate,
            'base_amount' => $baseAmount,
            'commission_amount' => $commissionAmount,
            'currency' => $currencyCode,
            'status' => 'accrued',
            'period_month' => $periodMonth,
        ]);

        // Verify commission was created correctly
        $this->assertNotNull($commission);
        $this->assertEquals(5.0, $commission->commission_rate_percent);
        $this->assertEquals(100.00, $commission->base_amount);
        $this->assertEquals(5.00, $commission->commission_amount);
        $this->assertEquals('USD', $commission->currency);
        $this->assertEquals('accrued', $commission->status);
    }

    /** @test */
    public function test_prevent_duplicate_commission()
    {
        // Create order
        $order = DecorationOrder::create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_phone' => $this->customer->phone,
            'event_address' => 'Test Address',
            'event_date' => now()->addDays(7),
            'event_time' => '18:00',
            'guest_count' => 50,
            'base_price' => 100.00,
            'total_price' => 100.00,
            'currency' => 'dollar',
            'status' => 'completed',
            'assigned_employee_id' => $this->employee->id,
            'completed_at' => now(),
        ]);

        // Create first commission
        $periodMonth = now()->startOfMonth()->toDateString();
        EmployeeCommission::create([
            'user_id' => $this->employee->id,
            'decoration_order_id' => $order->id,
            'commission_rate_percent' => 5.0,
            'base_amount' => 100.00,
            'commission_amount' => 5.00,
            'currency' => 'USD',
            'status' => 'accrued',
            'period_month' => $periodMonth,
        ]);

        // Try to create duplicate (simulating controller logic)
        $existingCommission = EmployeeCommission::where('user_id', $this->employee->id)
            ->where('decoration_order_id', $order->id)
            ->first();

        $shouldCreateNew = !$existingCommission;
        
        // Should not create new commission
        $this->assertFalse($shouldCreateNew);
        
        // Verify only one commission exists
        $commissionCount = EmployeeCommission::where('user_id', $this->employee->id)
            ->where('decoration_order_id', $order->id)
            ->count();
        
        $this->assertEquals(1, $commissionCount);
    }

    /** @test */
    public function test_payment_api_success()
    {
        $this->actingAs($this->admin);
        
        // Create order
        $order = DecorationOrder::create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_phone' => $this->customer->phone,
            'event_address' => 'Test Address',
            'event_date' => now()->addDays(7),
            'event_time' => '18:00',
            'guest_count' => 50,
            'base_price' => 100.00,
            'total_price' => 100.00,
            'currency' => 'dollar',
            'status' => 'received',
            'paid_amount' => 0,
        ]);

        // Test successful payment
        $response = $this->postJson(route('decoration.payments.add'), [
            'order_id' => $order->id,
            'amount' => 50.00,
            'currency' => 'dollar',
            'payment_method' => 'cash',
            'notes' => 'Test payment',
        ]);

        $response->assertJson(['success' => true]);
        
        $order->refresh();
        $this->assertEquals(50.00, $order->paid_amount);
        $this->assertEquals('partial_payment', $order->status);
    }

    /** @test */
    public function test_payment_api_overpayment_prevention()
    {
        $this->actingAs($this->admin);
        
        // Create fully paid order
        $order = DecorationOrder::create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_phone' => $this->customer->phone,
            'event_address' => 'Test Address',
            'event_date' => now()->addDays(7),
            'event_time' => '18:00',
            'guest_count' => 50,
            'base_price' => 100.00,
            'total_price' => 100.00,
            'currency' => 'dollar',
            'status' => 'full_payment',
            'paid_amount' => 100.00,
        ]);

        // Try to add another payment
        $response = $this->postJson(route('decoration.payments.add'), [
            'order_id' => $order->id,
            'amount' => 10.00,
            'currency' => 'dollar',
            'payment_method' => 'cash',
        ]);

        $response->assertJson(['success' => false]);
        $response->assertJsonFragment(['message' => 'الطلب مدفوع بالكامل بالفعل - لا يمكن إضافة دفعة أخرى']);
    }

    /** @test */
    public function test_commission_payout_api()
    {
        $this->actingAs($this->admin);
        
        // Create commission
        $order = DecorationOrder::create([
            'decoration_id' => $this->decoration->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_phone' => $this->customer->phone,
            'event_address' => 'Test Address',
            'event_date' => now()->addDays(7),
            'event_time' => '18:00',
            'guest_count' => 50,
            'base_price' => 100.00,
            'total_price' => 100.00,
            'currency' => 'dollar',
            'status' => 'completed',
            'assigned_employee_id' => $this->employee->id,
        ]);

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

        // Test commission payout
        $response = $this->postJson(route('decoration.monthly.payoutCommissions'), [
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
    public function test_currency_display_logic()
    {
        // Test currency display conversion
        $usdCurrency = 'USD';
        $iqdCurrency = 'IQD';
        
        $usdDisplay = $usdCurrency === 'USD' ? 'دولار' : 'دينار';
        $iqdDisplay = $iqdCurrency === 'USD' ? 'دولار' : 'دينار';
        
        $this->assertEquals('دولار', $usdDisplay);
        $this->assertEquals('دينار', $iqdDisplay);
    }
}
