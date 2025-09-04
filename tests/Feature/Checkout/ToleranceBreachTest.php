<?php

namespace Tests\Feature\Checkout;

use App\Models\PriceQuote;
use App\Models\Product;
use App\Models\SpotPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToleranceBreachTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the database with test data
        $this->seed();
        
        // Product is already created by the seeder, no need to create again
    }

    /** @test */
    public function it_rejects_quotes_when_spot_moves_beyond_tolerance()
    {
        // Mock fulfillment API to return available stock (should fail before inventory check)
        $this->mockFulfillmentAvailability('GOLD_1OZ', 10);

        // Create initial spot price of $2000
        $initialSpotPrice = SpotPrice::create([
            'metal_type' => 'gold',
            'price_per_oz_cents' => 200000,
            'effective_at' => now()->subMinutes(10),
            'is_current' => false,
        ]);

        // Create a quote based on this spot price with 50 bps tolerance
        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'total_price_cents' => 205000,
            'basis_spot_cents' => 200000,
            'basis_version' => $initialSpotPrice->id,
            'tolerance_bps' => 50, // 0.5% tolerance
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        // Update spot price to move beyond tolerance (more than 0.5% increase)
        // 50 bps = 0.5% of 200000 = 1000 cents
        // So 201100 cents should breach the tolerance (1100/200000 * 10000 = 55 bps)
        SpotPrice::where('metal_type', 'gold')->update(['is_current' => false]);
        SpotPrice::create([
            'metal_type' => 'gold',
            'price_per_oz_cents' => 201100, // Moved up by 1100 cents (55 bps > 50 bps)
            'effective_at' => now(),
            'is_current' => true,
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'error' => 'REQUOTE_REQUIRED',
            ]);
    }

    /** @test */
    public function it_accepts_quotes_when_spot_moves_within_tolerance()
    {
        // Mock fulfillment API to return available stock
        $this->mockFulfillmentAvailability('GOLD_1OZ', 10);

        // Create initial spot price
        $initialSpotPrice = SpotPrice::create([
            'metal_type' => 'gold',
            'price_per_oz_cents' => 200000,
            'effective_at' => now()->subMinutes(10),
            'is_current' => false,
        ]);

        // Create a quote with 50 bps tolerance
        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'total_price_cents' => 205000,
            'basis_spot_cents' => 200000,
            'basis_version' => $initialSpotPrice->id,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        // Update spot price to move within tolerance (less than 0.5%)
        // 50 bps = 0.5% of 200000 = 1000 cents
        // So 200999 cents should be within tolerance
        SpotPrice::where('metal_type', 'gold')->update(['is_current' => false]);
        SpotPrice::create([
            'metal_type' => 'gold',
            'price_per_oz_cents' => 200999, // Moved up by 999 cents (< 50 bps)
            'effective_at' => now(),
            'is_current' => true,
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_handles_spot_price_decreases_beyond_tolerance()
    {
        // Create initial spot price
        $initialSpotPrice = SpotPrice::create([
            'metal_type' => 'gold',
            'price_per_oz_cents' => 200000,
            'effective_at' => now()->subMinutes(10),
            'is_current' => false,
        ]);

        // Create a quote
        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'total_price_cents' => 205000,
            'basis_spot_cents' => 200000,
            'basis_version' => $initialSpotPrice->id,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        // Update spot price to decrease beyond tolerance
        // 50 bps = 0.5% of 200000 = 1000 cents
        // So 198900 cents should breach the tolerance (decrease of 1100 cents = 55 bps)
        SpotPrice::where('metal_type', 'gold')->update(['is_current' => false]);
        SpotPrice::create([
            'metal_type' => 'gold',
            'price_per_oz_cents' => 198900,
            'effective_at' => now(),
            'is_current' => true,
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'error' => 'REQUOTE_REQUIRED',
            ]);
    }

    /** @test */
    public function it_calculates_tolerance_correctly_for_different_basis_points()
    {
        // Create initial spot price
        $initialSpotPrice = SpotPrice::create([
            'metal_type' => 'gold',
            'price_per_oz_cents' => 200000,
            'effective_at' => now()->subMinutes(10),
            'is_current' => false,
        ]);

        // Create a quote with 100 bps (1%) tolerance
        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'total_price_cents' => 205000,
            'basis_spot_cents' => 200000,
            'basis_version' => $initialSpotPrice->id,
            'tolerance_bps' => 100, // 1% tolerance
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        // Update spot price to move beyond tolerance boundary
        // 100 bps = 1% of 200000 = 2000 cents
        // So 202100 cents should breach tolerance (2100/200000 * 10000 = 105 bps > 100 bps)
        SpotPrice::where('metal_type', 'gold')->update(['is_current' => false]);
        SpotPrice::create([
            'metal_type' => 'gold',
            'price_per_oz_cents' => 202100,
            'effective_at' => now(),
            'is_current' => true,
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        // At the boundary should still be rejected
        $response->assertStatus(409)
            ->assertJson([
                'error' => 'REQUOTE_REQUIRED',
            ]);
    }

    /**
     * Mock the fulfillment API availability response
     */
    private function mockFulfillmentAvailability(string $sku, int $availableQty): void
    {
        // Set mock fulfillment availability
        $this->postJson('/api/mock-fulfillment/availability', [
            'sku' => $sku,
            'available_qty' => $availableQty,
        ]);
    }
}
