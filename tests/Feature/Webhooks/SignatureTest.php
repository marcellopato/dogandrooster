<?php

namespace Tests\Feature\Webhooks;

use App\Models\Order;
use App\Models\PriceQuote;
use App\Models\Product;
use App\Models\SpotPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignatureTest extends TestCase
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

        // Mock fulfillment API
        $this->mockFulfillmentAvailability('GOLD_1OZ', 10);
    }

    /** @test */
    public function it_processes_payment_authorized_webhook_with_valid_signature()
    {
        // Create an order
        $order = $this->createTestOrder();

        $payload = [
            'event' => 'payment_authorized',
            'payment_intent_id' => $order->payment_intent_id,
            'timestamp' => now()->toISOString(),
        ];

        $signature = $this->generateValidSignature($payload);

        $response = $this->postJson('/api/webhooks/payments', $payload, [
            'X-Payment-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Check that order status was updated
        $order->refresh();
        $this->assertEquals('authorized', $order->status);
    }

    /** @test */
    public function it_processes_payment_captured_webhook_with_valid_signature()
    {
        // Create an order and set it to authorized first
        $order = $this->createTestOrder();
        $order->update(['status' => 'authorized']);

        $payload = [
            'event' => 'payment_captured',
            'payment_intent_id' => $order->payment_intent_id,
            'timestamp' => now()->toISOString(),
        ];

        $signature = $this->generateValidSignature($payload);

        $response = $this->postJson('/api/webhooks/payments', $payload, [
            'X-Payment-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Check that order status was updated
        $order->refresh();
        $this->assertEquals('captured', $order->status);
    }

    /** @test */
    public function it_only_allows_payment_captured_from_authorized_status()
    {
        // Create an order in pending status
        $order = $this->createTestOrder();
        $this->assertEquals('pending', $order->status);

        $payload = [
            'event' => 'payment_captured',
            'payment_intent_id' => $order->payment_intent_id,
            'timestamp' => now()->toISOString(),
        ];

        $signature = $this->generateValidSignature($payload);

        $response = $this->postJson('/api/webhooks/payments', $payload, [
            'X-Payment-Signature' => $signature,
        ]);

        // Should fail because order is not in authorized status
        $response->assertStatus(400);

        // Status should remain unchanged
        $order->refresh();
        $this->assertEquals('pending', $order->status);
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_signature()
    {
        // Create an order
        $order = $this->createTestOrder();

        $payload = [
            'event' => 'payment_authorized',
            'payment_intent_id' => $order->payment_intent_id,
            'timestamp' => now()->toISOString(),
        ];

        // Use an invalid signature
        $invalidSignature = 'invalid_signature_hash';

        $response = $this->postJson('/api/webhooks/payments', $payload, [
            'X-Payment-Signature' => $invalidSignature,
        ]);

        $response->assertStatus(400);

        // Status should remain unchanged
        $order->refresh();
        $this->assertEquals('pending', $order->status);
    }

    /** @test */
    public function it_rejects_webhook_with_unknown_payment_intent()
    {
        $payload = [
            'event' => 'payment_authorized',
            'payment_intent_id' => 'unknown_intent_id',
            'timestamp' => now()->toISOString(),
        ];

        $signature = $this->generateValidSignature($payload);

        $response = $this->postJson('/api/webhooks/payments', $payload, [
            'X-Payment-Signature' => $signature,
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_rejects_webhook_without_signature_header()
    {
        // Create an order
        $order = $this->createTestOrder();

        $payload = [
            'event' => 'payment_authorized',
            'payment_intent_id' => $order->payment_intent_id,
            'timestamp' => now()->toISOString(),
        ];

        // Send without signature header
        $response = $this->postJson('/api/webhooks/payments', $payload);

        $response->assertStatus(400);

        // Status should remain unchanged
        $order->refresh();
        $this->assertEquals('pending', $order->status);
    }

    /** @test */
    public function it_handles_unsupported_webhook_events()
    {
        // Create an order
        $order = $this->createTestOrder();

        $payload = [
            'event' => 'payment_unknown_event',
            'payment_intent_id' => $order->payment_intent_id,
            'timestamp' => now()->toISOString(),
        ];

        $signature = $this->generateValidSignature($payload);

        $response = $this->postJson('/api/webhooks/payments', $payload, [
            'X-Payment-Signature' => $signature,
        ]);

        // Should accept but not process unsupported events
        $response->assertStatus(200);

        // Status should remain unchanged
        $order->refresh();
        $this->assertEquals('pending', $order->status);
    }

    /**
     * Create a test order for webhook testing
     */
    private function createTestOrder(): Order
    {
        // Get the spot price created in setUp
        $spotPrice = SpotPrice::where('metal_type', 'gold')->first();

        // Create a quote first
        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 1,
            'unit_price_cents' => 205000,
            'total_price_cents' => 205000,
            'basis_spot_cents' => 200000,
            'basis_version' => $spotPrice->id,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        // Create an order through checkout
        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        // Ensure the order was created successfully
        $response->assertStatus(201);
        
        $orderId = $response->json('order_id');

        return Order::find($orderId);
    }

    /**
     * Generate a valid HMAC signature for the payload
     */
    private function generateValidSignature(array $payload): string
    {
        $secret = config('services.payment.webhook_secret', 'test_webhook_secret');
        $jsonPayload = json_encode($payload);

        return hash_hmac('sha256', $jsonPayload, $secret);
    }

    /**
     * Mock the fulfillment API availability response
     */
    private function mockFulfillmentAvailability(string $sku, int $availableQty): void
    {
        $this->postJson('/api/mock-fulfillment/availability', [
            'sku' => $sku,
            'available_qty' => $availableQty,
        ]);
    }
}
