<?php

namespace Database\Factories;

use App\Models\Decoration;
use Illuminate\Database\Eloquent\Factories\Factory;

class DecorationFactory extends Factory
{
    protected $model = Decoration::class;

    public function definition(): array
    {
        $types = ['birthday', 'gender_reveal', 'baby_shower', 'wedding', 'graduation', 'corporate', 'religious'];
        $currency = $this->faker->randomElement(['dollar', 'dinar']);
        $basePrice = $this->faker->numberBetween(50, 500);
        
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement($types),
            'base_price' => $basePrice,
            'currency' => $currency,
            'base_price_dollar' => $currency === 'dollar' ? $basePrice : round($basePrice / 1500, 2),
            'base_price_dinar' => $currency === 'dinar' ? $basePrice : $basePrice * 1500,
            'duration_hours' => $this->faker->numberBetween(2, 12),
            'team_size' => $this->faker->numberBetween(1, 6),
            'is_active' => true,
            'is_featured' => $this->faker->boolean(20),
        ];
    }
}
