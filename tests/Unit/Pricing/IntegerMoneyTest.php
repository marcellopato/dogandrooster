<?php

namespace Tests\Unit\Pricing;

use App\Models\Product;
use App\Models\SpotPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegerMoneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test product
        Product::create([
            'sku' => 'GOLD_1OZ',
            'name' => 'Gold 1 Ounce Coin',
            'metal_type' => 'gold',
            'weight_oz' => '1.0000',
            'premium_cents' => 5000, // $50 premium
            'active' => true,
        ]);

        // Create a spot price
        SpotPrice::create([
            'metal_type' => 'gold',
            'price_per_oz_cents' => 200000, // $2000 per ounce
            'effective_at' => now(),
            'is_current' => true,
        ]);
    }

    /** @test */
    public function it_calculates_unit_price_with_integer_cents_only()
    {
        $response = $this->postJson('/api/quote', [
            'sku' => 'GOLD_1OZ',
            'qty' => 1,
        ]);

        $response->assertStatus(200);

        $data = $response->json();

        // Verify unit price is an integer (in cents)
        $this->assertIsInt($data['unit_price_cents']);

        // Expected: spot_per_oz_cents * weight_oz + premium_cents
        // 200000 * 1.0000 + 5000 = 205000 cents
        $this->assertEquals(205000, $data['unit_price_cents']);
    }

    /** @test */
    public function it_uses_only_integer_math_in_pricing_calculation()
    {
        // Create product with fractional weight
        Product::create([
            'sku' => 'GOLD_HALF_OZ',
            'name' => 'Gold Half Ounce Coin',
            'metal_type' => 'gold',
            'weight_oz' => '0.5000',
            'premium_cents' => 2500, // $25 premium
            'active' => true,
        ]);

        $response = $this->postJson('/api/quote', [
            'sku' => 'GOLD_HALF_OZ',
            'qty' => 1,
        ]);

        $response->assertStatus(200);

        $data = $response->json();

        // Verify result is integer
        $this->assertIsInt($data['unit_price_cents']);

        // Expected: 200000 * 0.5 + 2500 = 100000 + 2500 = 102500 cents
        $this->assertEquals(102500, $data['unit_price_cents']);
    }

    /** @test */
    public function it_maintains_integer_precision_with_multiple_quantities()
    {
        $response = $this->postJson('/api/quote', [
            'sku' => 'GOLD_1OZ',
            'qty' => 3,
        ]);

        $response->assertStatus(200);

        $data = $response->json();

        // Unit price should remain the same regardless of quantity
        $this->assertIsInt($data['unit_price_cents']);
        $this->assertEquals(205000, $data['unit_price_cents']);
    }

    /** @test */
    public function it_avoids_floating_point_numbers_in_pricing()
    {
        // Test with a spot price that might cause floating point issues
        SpotPrice::where('metal_type', 'gold')->update([
            'price_per_oz_cents' => 199999, // $1999.99 - could cause float issues
        ]);

        $response = $this->postJson('/api/quote', [
            'sku' => 'GOLD_1OZ',
            'qty' => 1,
        ]);

        $response->assertStatus(200);

        $data = $response->json();

        // Verify result is integer
        $this->assertIsInt($data['unit_price_cents']);

        // Expected: 199999 * 1.0000 + 5000 = 204999 cents
        $this->assertEquals(204999, $data['unit_price_cents']);

        // Ensure no decimal places in response
        $this->assertDoesNotMatchRegularExpression('/\.\d/', (string) $data['unit_price_cents']);
    }
}
