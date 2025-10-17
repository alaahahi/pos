<?php

namespace Database\Factories;

use App\Models\Box;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoxFactory extends Factory
{
    protected $model = Box::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'balance' => $this->faker->numberBetween(1000000, 5000000),
            'balance_usd' => $this->faker->numberBetween(5000, 20000),
            'is_active' => true,
            'amount' => 0,
            'type' => null,
            'description' => $this->faker->sentence(),
            'is_pay' => 1,
            'morphed_id' => null,
            'morphed_type' => null,
            'currency' => $this->faker->randomElement(['USD', 'IQD']),
            'created' => now(),
            'discount' => 0,
            'parent_id' => null,
            'details' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

