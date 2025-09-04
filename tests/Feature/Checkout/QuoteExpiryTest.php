<?php

namespace Tests\Feature\Checkout;

use App\Models\PriceQuote;
use App\Models\Product;
use App\Models\SpotPrice;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the database with test data
        $this->seed();
    }

    /** @test */
    public function it_rejects_expired_quotes_with_requote_required_error()
    {
        // Get the spot price created in setUp
        $spotPrice = SpotPrice::where('metal_type', 'gold')->first();

        // Create an expired quote
        $expiredQuote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'total_price_cents' => 205000,
            'basis_spot_cents' => 200000,
            'basis_version' => $spotPrice->id,
            'tolerance_bps' => 50,
            'quote_expires_at' => Carbon::now()->subMinutes(1), // Expired 1 minute ago
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $expiredQuote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'error' => 'REQUOTE_REQUIRED',
            ]);
    }

    /** @test */
    public function it_accepts_quotes_that_are_still_valid()
    {
        // Mock fulfillment API to return available stock
        $this->mockFulfillmentAvailability('GOLD_1OZ', 10);

        // Get the spot price created in setUp
        $spotPrice = SpotPrice::where('metal_type', 'gold')->first();

        // Create a valid quote (expires in 5 minutes)
        $validQuote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'total_price_cents' => 205000,
            'basis_spot_cents' => 200000,
            'basis_version' => $spotPrice->id,
            'tolerance_bps' => 50,
            'quote_expires_at' => Carbon::now()->addMinutes(5), // Valid for 5 more minutes
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $validQuote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_treats_exact_expiry_time_as_expired()
    {
        // Get the spot price created in setUp
        $spotPrice = SpotPrice::where('metal_type', 'gold')->first();

        // Create a quote that expires exactly now
        $expiredQuote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'total_price_cents' => 205000,
            'basis_spot_cents' => 200000,
            'basis_version' => $spotPrice->id,
            'tolerance_bps' => 50,
            'quote_expires_at' => Carbon::now(), // Expires exactly now
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $expiredQuote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'error' => 'REQUOTE_REQUIRED',
            ]);
    }

    /** @test */
    public function it_handles_utc_time_correctly_for_expiry()
    {
        // Set a specific UTC time for testing
        Carbon::setTestNow(Carbon::parse('2025-09-04 12:00:00 UTC'));

        // Get the spot price created in setUp
        $spotPrice = SpotPrice::where('metal_type', 'gold')->first();

        // Create a quote that expired 1 second ago in UTC
        $expiredQuote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'total_price_cents' => 205000,
            'basis_spot_cents' => 200000,
            'basis_version' => $spotPrice->id,
            'tolerance_bps' => 50,
            'quote_expires_at' => Carbon::parse('2025-09-04 11:59:59 UTC'),
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $expiredQuote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'error' => 'REQUOTE_REQUIRED',
            ]);

        // Reset Carbon
        Carbon::setTestNow();
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
