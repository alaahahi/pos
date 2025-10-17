<?php

namespace Database\Factories;

use App\Models\Transactions;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionsFactory extends Factory
{
    protected $model = Transactions::class;

    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'amount' => $this->faker->numberBetween(1000, 100000),
            'type' => $this->faker->randomElement(['in', 'out', 'inUser', 'outUser']),
            'description' => $this->faker->sentence(),
            'is_pay' => 1,
            'morphed_id' => User::factory(),
            'morphed_type' => 'App\\Models\\User',
            'currency' => $this->faker->randomElement(['USD', 'IQD', '$']),
            'created' => now(),
            'discount' => 0,
            'parent_id' => null,
            'details' => null,
        ];
    }

    public function incoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $this->faker->randomElement(['in', 'inUser']),
        ]);
    }

    public function outgoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $this->faker->randomElement(['out', 'outUser']),
        ]);
    }

    public function usd(): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => 'USD',
        ]);
    }

    public function iqd(): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => 'IQD',
        ]);
    }
}

