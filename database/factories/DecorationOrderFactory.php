<?php

namespace Database\Factories;

use App\Models\DecorationOrder;
use App\Models\Decoration;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DecorationOrderFactory extends Factory
{
    protected $model = DecorationOrder::class;

    public function definition(): array
    {
        $basePrice = $this->faker->numberBetween(50, 300);
        $additionalCost = $this->faker->numberBetween(0, 50);
        $discount = $this->faker->numberBetween(0, 20);
        $totalPrice = $basePrice + $additionalCost - $discount;
        
        return [
            'decoration_id' => Decoration::factory(),
            'customer_id' => Customer::factory(),
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
            'customer_email' => $this->faker->email(),
            'event_address' => $this->faker->address(),
            'event_date' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'event_time' => $this->faker->time('H:i'),
            'guest_count' => $this->faker->numberBetween(10, 200),
            'special_requests' => $this->faker->optional()->sentence(),
            'base_price' => $basePrice,
            'additional_cost' => $additionalCost,
            'discount' => $discount,
            'total_price' => $totalPrice,
            'currency' => $this->faker->randomElement(['dollar', 'dinar']),
            'status' => $this->faker->randomElement(['created', 'received', 'executing', 'partial_payment', 'full_payment', 'completed']),
            'paid_amount' => 0,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function withEmployee(): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_employee_id' => User::factory(),
        ]);
    }
}
