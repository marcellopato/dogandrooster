<?php

// Test script for Checkout API
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\SpotPrice;
use App\Models\PriceQuote;
use Illuminate\Support\Facades\Http;

echo "=== Testing Checkout API ===\n\n";

try {
    // 1. Create a test quote first
    echo "1. Creating a test quote...\n";
    
    $product = Product::where('sku', 'GOLD_1OZ')->first();
    if (!$product) {
        echo "❌ Product GOLD_1OZ not found\n";
        exit(1);
    }
    
    $spotPrice = SpotPrice::getLatest();
    if (!$spotPrice) {
        echo "❌ No spot price found\n";
        exit(1);
    }
    
    $unitPrice = $product->calculateUnitPrice($spotPrice->price_per_oz_cents);
    
    $quote = PriceQuote::create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price_cents' => $unitPrice,
        'basis_spot_cents' => $spotPrice->price_per_oz_cents,
        'basis_version' => $spotPrice->id,
        'quote_expires_at' => now()->addMinutes(5),
        'tolerance_bps' => 50,
    ]);
    
    echo "✓ Quote created: ID {$quote->id}, Unit Price: $" . ($unitPrice/100) . "\n\n";
    
    // 2. Test Mock Fulfillment API
    echo "2. Testing Mock Fulfillment API...\n";
    
    $fulfillmentUrl = url('/api/mock-fulfillment/availability/GOLD_1OZ');
    echo "Making request to: {$fulfillmentUrl}\n";
    
    $response = Http::get($fulfillmentUrl);
    
    if ($response->successful()) {
        $data = $response->json();
        echo "✓ Fulfillment API response: {$data['available_qty']} units available\n\n";
    } else {
        echo "❌ Fulfillment API error: {$response->status()}\n";
        echo "Response: " . $response->body() . "\n\n";
    }
    
    // 3. Test Checkout API
    echo "3. Testing Checkout API...\n";
    
    $checkoutUrl = url('/api/checkout');
    $idempotencyKey = 'test-' . uniqid();
    
    echo "Making checkout request...\n";
    echo "Quote ID: {$quote->id}\n";
    echo "Idempotency Key: {$idempotencyKey}\n";
    
    $checkoutResponse = Http::withHeaders([
        'Idempotency-Key' => $idempotencyKey,
        'Content-Type' => 'application/json',
    ])->post($checkoutUrl, [
        'quote_id' => $quote->id,
    ]);
    
    echo "Response Status: {$checkoutResponse->status()}\n";
    echo "Response Body: " . $checkoutResponse->body() . "\n\n";
    
    if ($checkoutResponse->successful()) {
        $orderData = $checkoutResponse->json();
        echo "✓ Order created successfully!\n";
        echo "Order ID: {$orderData['order_id']}\n";
        echo "Payment Intent: {$orderData['payment_intent_id']}\n";
        echo "Total: $" . ($orderData['total_cents']/100) . "\n\n";
        
        // 4. Test idempotency
        echo "4. Testing idempotency...\n";
        $secondResponse = Http::withHeaders([
            'Idempotency-Key' => $idempotencyKey,
            'Content-Type' => 'application/json',
        ])->post($checkoutUrl, [
            'quote_id' => $quote->id,
        ]);
        
        if ($secondResponse->successful()) {
            $secondOrderData = $secondResponse->json();
            if ($secondOrderData['order_id'] === $orderData['order_id']) {
                echo "✓ Idempotency working correctly - same order returned\n";
            } else {
                echo "❌ Idempotency failed - different order returned\n";
            }
        } else {
            echo "❌ Second request failed: " . $secondResponse->body() . "\n";
        }
        
    } else {
        echo "❌ Checkout failed\n";
        
        if ($checkoutResponse->status() === 409) {
            echo "Conflict response (expected for some test cases)\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";
