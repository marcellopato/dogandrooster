<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'idempotency_key' => $this->faker->uuid(),
            'payment_intent_id' => $this->faker->uuid(),
            'status' => 'pending',
            'total_cents' => $this->faker->numberBetween(50000, 500000), // $500-5k
        ];
    }

    /**
     * Indicate that the order is authorized.
     *
     * @return Factory
     */
    public function authorized()
    {
        return $this->state(
            fn (array $attributes) => [
                'status' => 'authorized',
            ]
        );
    }

    /**
     * Indicate that the order is captured.
     *
     * @return Factory
     */
    public function captured()
    {
        return $this->state(
            fn (array $attributes) => [
                'status' => 'captured',
            ]
        );
    }
}
