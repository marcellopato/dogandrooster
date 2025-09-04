<?php

namespace Tests\Feature\Checkout;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\PriceQuote;
use App\Models\Product;
use App\Models\SpotPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TotalsIntegrityTest extends TestCase
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

        Product::create([
            'sku' => 'SILVER_1OZ',
            'name' => 'Silver 1 Ounce Coin',
            'metal_type' => 'silver',
            'weight_oz' => '1.0000',
            'premium_cents' => 300,
            'active' => true,
        ]);

        SpotPrice::create([
            'metal_type' => 'gold',
            'price_per_oz_cents' => 200000,
            'effective_at' => now(),
            'is_current' => true,
        ]);

        SpotPrice::create([
            'metal_type' => 'silver',
            'price_per_oz_cents' => 2500,
            'effective_at' => now(),
            'is_current' => true,
        ]);

        // Mock fulfillment API
        $this->mockFulfillmentAvailability('GOLD_1OZ', 10);
        $this->mockFulfillmentAvailability('SILVER_1OZ', 20);
    }

    /** @test */
    public function it_ensures_order_total_equals_sum_of_order_lines_subtotals()
    {
        // Create a quote for 3 gold coins
        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 3,
            'unit_price_cents' => 205000, // $2050 per coin
            'total_price_cents' => 615000, // $6150 for 3 coins
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

        $orderId = $response->json('order_id');
        $order = Order::find($orderId);
        $orderLines = OrderLine::where('order_id', $orderId)->get();

        // Calculate sum of order lines subtotals
        $orderLinesTotal = $orderLines->sum('subtotal_cents');

        // Order total should equal sum of order lines subtotals
        $this->assertEquals($orderLinesTotal, $order->total_cents);
        $this->assertEquals(615000, $order->total_cents);
        $this->assertEquals(615000, $orderLinesTotal);
    }

    /** @test */
    public function it_ensures_order_line_subtotal_equals_unit_price_times_quantity()
    {
        // Create a quote for 5 gold coins
        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 5,
            'unit_price_cents' => 205000,
            'total_price_cents' => 1025000, // 5 * 205000
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

        $orderId = $response->json('order_id');
        $orderLine = OrderLine::where('order_id', $orderId)->first();

        // Order line subtotal should equal unit_price_cents * quantity
        $expectedSubtotal = $orderLine->unit_price_cents * $orderLine->quantity;
        $this->assertEquals($expectedSubtotal, $orderLine->subtotal_cents);
        $this->assertEquals(1025000, $orderLine->subtotal_cents);
    }

    /** @test */
    public function it_maintains_integrity_with_single_item_orders()
    {
        // Create a quote for 1 gold coin
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

        $response->assertStatus(200);

        $orderId = $response->json('order_id');
        $order = Order::find($orderId);
        $orderLine = OrderLine::where('order_id', $orderId)->first();

        // For single item, order total should equal order line subtotal
        $this->assertEquals($order->total_cents, $orderLine->subtotal_cents);
        $this->assertEquals(205000, $order->total_cents);
        $this->assertEquals(205000, $orderLine->subtotal_cents);

        // And subtotal should equal unit price * quantity (1)
        $this->assertEquals($orderLine->unit_price_cents * $orderLine->quantity, $orderLine->subtotal_cents);
    }

    /** @test */
    public function it_maintains_integrity_with_different_product_types()
    {
        // Create a quote for silver coins (different pricing)
        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'SILVER_1OZ',
            'quantity' => 10,
            'unit_price_cents' => 2800, // $28 per silver coin (2500 + 300 premium)
            'total_price_cents' => 28000, // $280 for 10 coins
            'basis_spot_cents' => 2500,
            'basis_version' => 2,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        $response->assertStatus(200);

        $orderId = $response->json('order_id');
        $order = Order::find($orderId);
        $orderLine = OrderLine::where('order_id', $orderId)->first();

        // Verify totals integrity
        $this->assertEquals($order->total_cents, $orderLine->subtotal_cents);
        $this->assertEquals(28000, $order->total_cents);
        $this->assertEquals(28000, $orderLine->subtotal_cents);

        // Verify unit calculation
        $this->assertEquals($orderLine->unit_price_cents * $orderLine->quantity, $orderLine->subtotal_cents);
        $this->assertEquals(2800 * 10, $orderLine->subtotal_cents);
    }

    /** @test */
    public function it_maintains_integrity_with_large_quantities()
    {
        // Create a quote for a large quantity
        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 100,
            'unit_price_cents' => 205000,
            'total_price_cents' => 20500000, // 100 * 205000
            'basis_spot_cents' => 200000,
            'basis_version' => 1,
            'tolerance_bps' => 50,
            'quote_expires_at' => now()->addMinutes(5),
        ]);

        // Mock higher inventory for this test
        $this->mockFulfillmentAvailability('GOLD_1OZ', 150);

        $response = $this->postJson('/api/checkout', [
            'quote_id' => $quote->quote_id,
        ], [
            'Idempotency-Key' => 'test-'.uniqid(),
        ]);

        $response->assertStatus(200);

        $orderId = $response->json('order_id');
        $order = Order::find($orderId);
        $orderLine = OrderLine::where('order_id', $orderId)->first();

        // Verify totals integrity with large numbers
        $this->assertEquals($order->total_cents, $orderLine->subtotal_cents);
        $this->assertEquals(20500000, $order->total_cents);
        $this->assertEquals(20500000, $orderLine->subtotal_cents);

        // Verify unit calculation
        $this->assertEquals($orderLine->unit_price_cents * $orderLine->quantity, $orderLine->subtotal_cents);
        $this->assertEquals(205000 * 100, $orderLine->subtotal_cents);
    }

    /** @test */
    public function it_ensures_no_rounding_errors_in_calculations()
    {
        // Test with a price that might cause rounding issues if using floats
        SpotPrice::where('metal_type', 'gold')->update([
            'price_per_oz_cents' => 199999, // $1999.99 - edge case pricing
        ]);

        $quote = PriceQuote::create([
            'quote_id' => 'test-quote-'.uniqid(),
            'sku' => 'GOLD_1OZ',
            'quantity' => 7,
            'unit_price_cents' => 204999, // 199999 + 5000 premium
            'total_price_cents' => 1434993, // 7 * 204999
            'basis_spot_cents' => 199999,
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

        $orderId = $response->json('order_id');
        $order = Order::find($orderId);
        $orderLine = OrderLine::where('order_id', $orderId)->first();

        // Ensure exact integer calculations with no rounding errors
        $this->assertEquals($order->total_cents, $orderLine->subtotal_cents);
        $this->assertEquals(1434993, $order->total_cents);
        $this->assertEquals(1434993, $orderLine->subtotal_cents);
        $this->assertEquals(204999 * 7, $orderLine->subtotal_cents);
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
