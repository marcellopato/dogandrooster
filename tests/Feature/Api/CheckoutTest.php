<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\PriceQuote;
use App\Models\Product;
use App\Models\SpotPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the database with test data
        $this->seed();

        // Mock fulfillment API to return available stock
        $this->mockFulfillmentAvailability('GOLD_1OZ', 10);
    }

    /** @test */
    public function it_requires_idempotency_key()
    {
        $quote = $this->createTestQuote();

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Idempotency-Key header is required',
            ]);
    }

    /** @test */
    public function it_creates_order_successfully()
    {
        $quote = $this->createTestQuote();

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-12345',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'order_id',
                'payment_intent_id',
                'status',
                'total_cents',
            ]);

        // Verify order was created
        $this->assertDatabaseHas('orders', [
            'idempotency_key' => 'test-12345',
            'status' => 'pending',
            'total_cents' => 205000, // $2050 (spot + premium)
        ]);

        // Verify order line was created
        $this->assertDatabaseHas('order_lines', [
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'subtotal_cents' => 205000,
        ]);
    }

    /** @test */
    public function it_enforces_idempotency()
    {
        $quote = $this->createTestQuote();
        $idempotencyKey = 'test-idempotency-123';

        // First request
        $response1 = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response1->assertStatus(201);
        $orderId1 = $response1->json('order_id');

        // Second request with same idempotency key
        $response2 = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response2->assertStatus(200);
        $orderId2 = $response2->json('order_id');

        // Should return same order
        $this->assertEquals($orderId1, $orderId2);

        // Should only have one order in database
        $this->assertEquals(1, Order::where('idempotency_key', $idempotencyKey)->count());
    }

    /** @test */
    public function it_rejects_expired_quotes()
    {
        $quote = $this->createTestQuote([
            'quote_expires_at' => now()->subMinute(), // Expired 1 minute ago
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-expired',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'error' => 'REQUOTE_REQUIRED',
            ]);
    }

    /** @test */
    public function it_rejects_when_price_tolerance_exceeded()
    {
        // Create quote with spot price of $2000
        $quote = $this->createTestQuote();

        // Create new spot price that exceeds tolerance (more than 0.5% = 50 bps)
        // $2000 + 1% = $2020 (exceeds 50 bps tolerance)
        
        // First mark existing current spot price as not current
        SpotPrice::where('metal_type', 'gold')
            ->where('is_current', true)
            ->update(['is_current' => false]);

        SpotPrice::create([
            'metal_type' => 'gold',
            'price_per_oz_cents' => 202000, // $2020 (1% increase)
            'effective_at' => now(),
            'is_current' => true,
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-tolerance',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'error' => 'REQUOTE_REQUIRED',
            ]);
    }

    /** @test */
    public function it_validates_quote_exists()
    {
        $response = $this->postJson('/api/checkout', [
            'quote_id' => '99999999-9999-9999-9999-999999999999', // Non-existent
        ], [
            'Idempotency-Key' => 'test-not-found',
        ]);

        $response->assertStatus(422); // Validation error
    }

    /**
     * Create a test quote
     */
    private function createTestQuote(array $overrides = []): PriceQuote
    {
        $product = Product::first();
        $spotPrice = SpotPrice::getLatest();
        $unitPrice = $product->calculateUnitPrice($spotPrice->price_per_oz_cents);

        $defaults = [
            'sku' => $product->sku,
            'quantity' => 1,
            'unit_price_cents' => $unitPrice,
            'total_price_cents' => $unitPrice, // Will be recalculated after merge
            'basis_spot_cents' => $spotPrice->price_per_oz_cents,
            'basis_version' => $spotPrice->id,
            'quote_expires_at' => now()->addMinutes(5),
            'tolerance_bps' => 50,
        ];

        $data = array_merge($defaults, $overrides);
        $data['total_price_cents'] = $data['unit_price_cents'] * $data['quantity'];

        return PriceQuote::create($data);
    }

    /**
     * Mock fulfillment availability for testing
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
