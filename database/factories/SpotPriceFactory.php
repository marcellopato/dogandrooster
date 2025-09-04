<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SpotPrice>
 */
class SpotPriceFactory extends Factory
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

        $priceRanges = [
            'gold' => [180000, 220000], // $1800-$2200
            'silver' => [2000, 3500], // $20-$35
            'platinum' => [90000, 110000], // $900-$1100
        ];

        $priceRange = $priceRanges[$metalType];

        return [
            'metal_type' => $metalType,
            'price_per_oz_cents' => $this->faker->numberBetween(
                $priceRange[0],
                $priceRange[1]
            ),
            'effective_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'is_current' => false,
        ];
    }

    /**
     * Indicate that the spot price is current.
     *
     * @return Factory
     */
    public function current()
    {
        return $this->state(
            fn (array $attributes) => [
                'is_current' => true,
                'effective_at' => now(),
            ]
        );
    }
}
