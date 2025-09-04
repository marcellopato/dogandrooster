<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $metalTypes = ['gold', 'silver', 'platinum'];
        $metalType = $this->faker->randomElement($metalTypes);
        $weight = $this->faker->randomFloat(4, 0.25, 10);
        $unitType = $this->faker->randomElement(['Coin', 'Bar']);

        return [
            'sku' => strtoupper($metalType).'_'.
                     $this->faker->unique()->numberBetween(1, 999),
            'name' => ucfirst($metalType).' '.$weight.' Ounce '.$unitType,
            'metal_type' => $metalType,
            'weight_oz' => $weight,
            'premium_cents' => $this->faker->numberBetween(500, 10000), // $5-$100
            'active' => true,
        ];
    }
}
