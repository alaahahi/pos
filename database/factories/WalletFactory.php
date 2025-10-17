<?php

namespace Database\Factories;

use App\Models\Wallet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance' => $this->faker->numberBetween(1000000, 10000000), // IQD
            'balance_dinar' => $this->faker->numberBetween(1000000, 10000000),
            'card' => $this->faker->creditCardNumber(),
        ];
    }
}

