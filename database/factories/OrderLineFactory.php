<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderLine>
 */
class OrderLineFactory extends Factory
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
            'order_id' => 1,
            'sku' => 'GOLD_1OZ',
            'quantity' => $quantity,
            'unit_price_cents' => $unitPrice,
            'subtotal_cents' => $unitPrice * $quantity,
        ];
    }
}
