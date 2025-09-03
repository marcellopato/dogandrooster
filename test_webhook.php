<?php

// Test script for Webhook API
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;

echo "=== Testing Webhook API ===\n\n";

try {
    // Create a test order first
    echo "1. Creating a test order...\n";
    
    $order = Order::create([
        'idempotency_key' => 'test-webhook-' . uniqid(),
        'payment_intent_id' => 'pi_test_' . uniqid(),
        'status' => 'pending',
        'total_cents' => 205000,
    ]);
    
    echo "✓ Order created: ID {$order->id}, Payment Intent: {$order->payment_intent_id}\n\n";
    
    // Prepare webhook payload
    $payload = [
        'event_type' => 'payment_authorized',
        'payment_intent_id' => $order->payment_intent_id,
        'amount_cents' => 205000,
        'currency' => 'USD',
        'timestamp' => now()->toISOString(),
    ];
    
    $payloadJson = json_encode($payload);
    $webhookSecret = config('pricing.payment_webhook_secret');
    
    // Calculate HMAC signature
    $signature = hash_hmac('sha256', $payloadJson, $webhookSecret);
    
    echo "2. Testing webhook with valid signature...\n";
    echo "Payload: {$payloadJson}\n";
    echo "Secret: {$webhookSecret}\n";
    echo "Signature: sha256={$signature}\n\n";
    
    // Test with curl (would need actual HTTP server)
    $webhookUrl = url('/api/webhooks/payments');
    echo "Webhook URL: {$webhookUrl}\n";
    
    // For now, just test the signature verification logic
    $controller = new \App\Http\Controllers\Api\WebhookController();
    
    // Use reflection to test private methods
    $reflection = new ReflectionClass($controller);
    $verifyMethod = $reflection->getMethod('verifySignature');
    $verifyMethod->setAccessible(true);
    
    $isValid = $verifyMethod->invoke($controller, $payloadJson, "sha256={$signature}", $webhookSecret);
    
    if ($isValid) {
        echo "✓ Signature verification passed\n";
    } else {
        echo "❌ Signature verification failed\n";
    }
    
    // Test invalid signature
    $invalidSignature = hash_hmac('sha256', $payloadJson, 'wrong_secret');
    $isInvalid = $verifyMethod->invoke($controller, $payloadJson, "sha256={$invalidSignature}", $webhookSecret);
    
    if (!$isInvalid) {
        echo "✓ Invalid signature correctly rejected\n";
    } else {
        echo "❌ Invalid signature incorrectly accepted\n";
    }
    
    echo "\n3. Testing order status transitions...\n";
    
    // Test the event processing logic
    $processMethod = $reflection->getMethod('processWebhookEvent');
    $processMethod->setAccessible(true);
    
    // Test authorization
    $result = $processMethod->invoke($controller, $order, 'payment_authorized', $payload);
    
    if ($result['success'] && $result['new_status'] === 'authorized') {
        echo "✓ Payment authorization processed correctly\n";
    } else {
        echo "❌ Payment authorization failed\n";
    }
    
    // Refresh order from database
    $order->refresh();
    echo "Order status: {$order->status}\n";
    
    // Test capture
    $capturePayload = array_merge($payload, ['event_type' => 'payment_captured']);
    $result = $processMethod->invoke($controller, $order, 'payment_captured', $capturePayload);
    
    if ($result['success'] && $result['new_status'] === 'captured') {
        echo "✓ Payment capture processed correctly\n";
    } else {
        echo "❌ Payment capture failed\n";
    }
    
    $order->refresh();
    echo "Final order status: {$order->status}\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";
