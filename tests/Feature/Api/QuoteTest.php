<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;
use App\Models\SpotPrice;

class QuoteTest extends TestCase
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
    public function it_can_generate_a_price_quote()
    {
        $response = $this->postJson('/api/quote', [
            'sku' => 'GOLD_1OZ',
            'qty' => 1,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'quote_id',
                    'unit_price_cents',
                    'quote_expires_at',
                ]);

        // Check that unit price is spot + premium (2000.00 + 50.00 = $2050.00)
        $response->assertJson([
            'unit_price_cents' => 205000,
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/quote', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['sku', 'qty']);
    }

    /** @test */
    public function it_returns_404_for_invalid_sku()
    {
        $response = $this->postJson('/api/quote', [
            'sku' => 'INVALID-SKU',
            'qty' => 1,
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'error' => 'PRODUCT_NOT_FOUND',
                ]);
    }

    /** @test */
    public function it_validates_quantity_bounds()
    {
        $response = $this->postJson('/api/quote', [
            'sku' => 'GOLD_1OZ',
            'qty' => 0, // Invalid quantity
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['qty']);

        $response = $this->postJson('/api/quote', [
            'sku' => 'GOLD_1OZ',
            'qty' => 1001, // Exceeds max
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['qty']);
    }
}
