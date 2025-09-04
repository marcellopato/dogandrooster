<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PriceQuote>
 */
class PriceQuoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = $this->faker->numberBetween(20000, 220000); // $200-$2200
        $quantity = $this->faker->numberBetween(1, 5);

        return [
            'quote_id' => $this->faker->uuid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => $quantity,
            'unit_price_cents' => $unitPrice,
            'total_price_cents' => $unitPrice * $quantity,
            'basis_spot_cents' => $this->faker->numberBetween(200000, 205000),
            'basis_version' => 1,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ];
    }

    /**
     * Indicate that the quote is expired.
     *
     * @return Factory
     */
    public function expired()
    {
        return $this->state(
            fn (array $attributes) => [
                'quote_expires_at' => now()->subMinute(),
            ]
        );
    }
}
