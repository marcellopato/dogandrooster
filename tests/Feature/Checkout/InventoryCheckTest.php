<?php

namespace Tests\Feature\Checkout;

use App\Models\PriceQuote;
use App\Models\Product;
use App\Models\SpotPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        Product::create([
            'sku' => 'GOLD_1OZ',
            'name' => 'Gold 1 Ounce Coin',
            'metal_type' => 'gold',
            'weight_oz' => '1.0000',
            'premium_cents' => 5000,
            'active' => true,
        ]);

        SpotPrice::create([
            'metal_type' => 'gold',
            'price_per_oz_cents' => 200000,
            'effective_at' => now(),
            'is_current' => true,
        ]);
    }

    /** @test */
    public function it_rejects_checkout_when_insufficient_inventory()
    {
        // Set insufficient inventory
        $this->mockFulfillmentAvailability('GOLD_1OZ', 0);

        // Create a valid quote for 1 item
        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'total_price_cents' => 205000,
            'basis_spot_cents' => 200000,
            'basis_version' => 1,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'error' => 'OUT_OF_STOCK',
            ]);
    }

    /** @test */
    public function it_rejects_checkout_when_requested_quantity_exceeds_inventory()
    {
        // Set inventory to 5 items
        $this->mockFulfillmentAvailability('GOLD_1OZ', 5);

        // Create a quote for 10 items (more than available)
        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 10,
            'unit_price_cents' => 205000,
            'total_price_cents' => 2050000,
            'basis_spot_cents' => 200000,
            'basis_version' => 1,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'error' => 'OUT_OF_STOCK',
            ]);
    }

    /** @test */
    public function it_accepts_checkout_when_sufficient_inventory_available()
    {
        // Set sufficient inventory
        $this->mockFulfillmentAvailability('GOLD_1OZ', 10);

        // Create a quote for 5 items (less than available)
        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 5,
            'unit_price_cents' => 205000,
            'total_price_cents' => 1025000,
            'basis_spot_cents' => 200000,
            'basis_version' => 1,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_accepts_checkout_when_requested_quantity_equals_inventory()
    {
        // Set inventory to exact requested amount
        $this->mockFulfillmentAvailability('GOLD_1OZ', 3);

        // Create a quote for exactly 3 items
        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 3,
            'unit_price_cents' => 205000,
            'total_price_cents' => 615000,
            'basis_spot_cents' => 200000,
            'basis_version' => 1,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_handles_fulfillment_api_errors_as_out_of_stock()
    {
        // Don't set any mock availability (simulates API error)

        // Create a valid quote
        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'total_price_cents' => 205000,
            'basis_spot_cents' => 200000,
            'basis_version' => 1,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        // Should treat API error as out of stock
        $response->assertStatus(409)
            ->assertJson([
                'error' => 'OUT_OF_STOCK',
            ]);
    }

    /** @test */
    public function it_validates_inventory_before_creating_order()
    {
        // Set insufficient inventory
        $this->mockFulfillmentAvailability('GOLD_1OZ', 0);

        // Create a valid quote
        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'total_price_cents' => 205000,
            'basis_spot_cents' => 200000,
            'basis_version' => 1,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'error' => 'OUT_OF_STOCK',
            ]);

        // Should not create any orders when inventory check fails
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_lines', 0);
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
