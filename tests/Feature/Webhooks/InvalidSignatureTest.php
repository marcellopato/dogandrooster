<?php

namespace Tests\Feature\Webhooks;

use App\Models\Order;
use App\Models\PriceQuote;
use App\Models\Product;
use App\Models\SpotPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvalidSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the database with test data
        $this->seed();

        // Mock fulfillment API
        $this->mockFulfillmentAvailability('GOLD_1OZ', 10);
    }

    /** @test */
    public function it_returns_400_for_invalid_signature_and_no_state_change()
    {
        // Create an order
        $order = $this->createTestOrder();
        $originalStatus = $order->status;

        $payload = [
            'event' => 'payment_authorized',
            'payment_intent_id' => $order->payment_intent_id,
            'timestamp' => now()->toISOString(),
        ];

        // Use completely invalid signature
        $invalidSignature = 'completely_invalid_signature';

        $response = $this->postJson('/api/webhooks/payments', $payload, [
            'X-Payment-Signature' => $invalidSignature,
        ]);

        // Should return 400
        $response->assertStatus(400);

        // Order status should remain unchanged
        $order->refresh();
        $this->assertEquals($originalStatus, $order->status);
    }

    /** @test */
    public function it_returns_400_for_tampered_payload_and_no_state_change()
    {
        // Create an order
        $order = $this->createTestOrder();
        $originalStatus = $order->status;

        $originalPayload = [
            'event' => 'payment_authorized',
            'payment_intent_id' => $order->payment_intent_id,
            'timestamp' => now()->toISOString(),
        ];

        // Generate signature for original payload
        $validSignature = $this->generateValidSignature($originalPayload);

        // Tamper with the payload after signature generation
        $tamperedPayload = $originalPayload;
        $tamperedPayload['event'] = 'payment_captured'; // Changed event

        $response = $this->postJson('/api/webhooks/payments', $tamperedPayload, [
            'X-Payment-Signature' => $validSignature,
        ]);

        // Should return 400 because signature doesn't match tampered payload
        $response->assertStatus(400);

        // Order status should remain unchanged
        $order->refresh();
        $this->assertEquals($originalStatus, $order->status);
    }

    /** @test */
    public function it_returns_400_for_malformed_signature_and_no_state_change()
    {
        // Create an order
        $order = $this->createTestOrder();
        $originalStatus = $order->status;

        $payload = [
            'event' => 'payment_authorized',
            'payment_intent_id' => $order->payment_intent_id,
            'timestamp' => now()->toISOString(),
        ];

        // Use malformed signature (not hex)
        $malformedSignature = 'not_a_hex_signature!@#$';

        $response = $this->postJson('/api/webhooks/payments', $payload, [
            'X-Payment-Signature' => $malformedSignature,
        ]);

        // Should return 400
        $response->assertStatus(400);

        // Order status should remain unchanged
        $order->refresh();
        $this->assertEquals($originalStatus, $order->status);
    }

    /** @test */
    public function it_returns_400_for_empty_signature_and_no_state_change()
    {
        // Create an order
        $order = $this->createTestOrder();
        $originalStatus = $order->status;

        $payload = [
            'event' => 'payment_authorized',
            'payment_intent_id' => $order->payment_intent_id,
            'timestamp' => now()->toISOString(),
        ];

        $response = $this->postJson('/api/webhooks/payments', $payload, [
            'X-Payment-Signature' => '',
        ]);

        // Should return 400
        $response->assertStatus(400);

        // Order status should remain unchanged
        $order->refresh();
        $this->assertEquals($originalStatus, $order->status);
    }

    /** @test */
    public function it_returns_400_for_unknown_intent_with_valid_signature_and_no_state_change()
    {
        $payload = [
            'event' => 'payment_authorized',
            'payment_intent_id' => 'non_existent_intent_id',
            'timestamp' => now()->toISOString(),
        ];

        // Generate valid signature for the payload
        $validSignature = $this->generateValidSignature($payload);

        $response = $this->postJson('/api/webhooks/payments', $payload, [
            'X-Payment-Signature' => $validSignature,
        ]);

        // Should return 400 for unknown intent
        $response->assertStatus(400);

        // No orders should be affected
        $this->assertEquals(0, Order::where('payment_intent_id', 'non_existent_intent_id')->count());
    }

    /** @test */
    public function it_preserves_order_state_across_multiple_invalid_attempts()
    {
        // Create an order
        $order = $this->createTestOrder();
        $originalStatus = $order->status;
        $originalUpdatedAt = $order->updated_at;

        $payload = [
            'event' => 'payment_authorized',
            'payment_intent_id' => $order->payment_intent_id,
            'timestamp' => now()->toISOString(),
        ];

        // Make multiple requests with invalid signatures
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/webhooks/payments', $payload, [
                'X-Payment-Signature' => 'invalid_signature_'.$i,
            ]);

            $response->assertStatus(400);
        }

        // Order should remain completely unchanged
        $order->refresh();
        $this->assertEquals($originalStatus, $order->status);
        $this->assertEquals($originalUpdatedAt->timestamp, $order->updated_at->timestamp);
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
