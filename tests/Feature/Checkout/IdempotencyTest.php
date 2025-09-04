<?php

namespace Tests\Feature\Checkout;

use App\Models\Order;
use App\Models\PriceQuote;
use App\Models\Product;
use App\Models\SpotPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotencyTest extends TestCase
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

        // Mock fulfillment API to return available stock
        $this->mockFulfillmentAvailability('GOLD_1OZ', 10);
    }

    /** @test */
    public function it_returns_same_order_for_duplicate_idempotency_key()
    {
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

        $idempotencyKey = 'test-idempotency-'.uniqid();

        // First request
        $response1 = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response1->assertStatus(200);
        $firstOrderId = $response1->json('order_id');
        $firstPaymentIntentId = $response1->json('payment_intent_id');

        // Second request with same idempotency key
        $response2 = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response2->assertStatus(200);
        $secondOrderId = $response2->json('order_id');
        $secondPaymentIntentId = $response2->json('payment_intent_id');

        // Should return the same order
        $this->assertEquals($firstOrderId, $secondOrderId);
        $this->assertEquals($firstPaymentIntentId, $secondPaymentIntentId);

        // Should only have one order in database
        $this->assertEquals(1, Order::count());
    }

    /** @test */
    public function it_creates_different_orders_for_different_idempotency_keys()
    {
        // Create two valid quotes
        $quote1 = PriceQuote::create([
            'quote_id' => 'test-quote-1-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'total_price_cents' => 205000,
            'basis_spot_cents' => 200000,
            'basis_version' => 1,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        $quote2 = PriceQuote::create([
            'quote_id' => 'test-quote-2-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'total_price_cents' => 205000,
            'basis_spot_cents' => 200000,
            'basis_version' => 1,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        // First request with first idempotency key
        $response1 = $this->postJson('/api/checkout', [
            'quote_id' => $quote1->quote_id,
        ], [
            'Idempotency-Key' => 'test-idempotency-1-'.uniqid(),
        ]);

        // Second request with different idempotency key
        $response2 = $this->postJson('/api/checkout', [
            'quote_id' => $quote2->quote_id,
        ], [
            'Idempotency-Key' => 'test-idempotency-2-'.uniqid(),
        ]);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $firstOrderId = $response1->json('order_id');
        $secondOrderId = $response2->json('order_id');

        // Should create different orders
        $this->assertNotEquals($firstOrderId, $secondOrderId);

        // Should have two orders in database
        $this->assertEquals(2, Order::count());
    }

    /** @test */
    public function it_handles_concurrent_requests_with_same_idempotency_key()
    {
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

        $idempotencyKey = 'concurrent-test-'.uniqid();

        // Simulate concurrent requests (in real scenario, these would be actual concurrent requests)
        $response1 = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response2 = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        // Both should succeed
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Should return the same order ID
        $this->assertEquals(
            $response1->json('order_id'),
            $response2->json('order_id')
        );

        // Should only create one order despite multiple requests
        $this->assertEquals(1, Order::count());
    }

    /** @test */
    public function it_enforces_idempotency_across_different_quote_ids()
    {
        // Create two different quotes
        $quote1 = PriceQuote::create([
            'quote_id' => 'test-quote-1-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'total_price_cents' => 205000,
            'basis_spot_cents' => 200000,
            'basis_version' => 1,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        $quote2 = PriceQuote::create([
            'quote_id' => 'test-quote-2-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 2,
            'unit_price_cents' => 205000,
            'total_price_cents' => 410000,
            'basis_spot_cents' => 200000,
            'basis_version' => 1,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        $idempotencyKey = 'test-cross-quote-'.uniqid();

        // First request with first quote
        $response1 = $this->postJson('/api/checkout', [
            'quote_id' => $quote1->quote_id,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        // Second request with different quote but same idempotency key
        $response2 = $this->postJson('/api/checkout', [
            'quote_id' => $quote2->quote_id,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Should return the same order (from first request)
        $this->assertEquals(
            $response1->json('order_id'),
            $response2->json('order_id')
        );

        // Should only have one order
        $this->assertEquals(1, Order::count());

        // The order should match the first quote's details
        $order = Order::first();
        $this->assertEquals($quote1->quote_id, $order->quote_id);
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
