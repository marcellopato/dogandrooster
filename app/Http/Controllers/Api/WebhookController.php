<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

/**
 * Webhook Controller for payment processing
 */
class WebhookController extends Controller
{
    /**
     * Handle payment webhook events
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handlePayment(Request $request): JsonResponse
    {
        try {
            // Get webhook secret
            $webhookSecret = config('pricing.payment_webhook_secret');
            
            if (empty($webhookSecret)) {
                Log::error('Payment webhook secret not configured');
                return response()->json(['error' => 'Webhook not configured'], 400);
            }

            // Verify HMAC signature
            $signature = $request->header('X-Signature-SHA256');
            $payload = $request->getContent();
            
            if (!$this->verifySignature($payload, $signature, $webhookSecret)) {
                Log::warning('Invalid webhook signature', [
                    'signature' => $signature,
                    'payload_length' => strlen($payload),
                ]);
                
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            // Parse webhook data
            $data = $request->json()->all();
            
            $eventType = $data['event_type'] ?? null;
            $paymentIntentId = $data['payment_intent_id'] ?? null;
            
            if (!$eventType || !$paymentIntentId) {
                Log::warning('Missing required webhook fields', [
                    'event_type' => $eventType,
                    'payment_intent_id' => $paymentIntentId,
                ]);
                
                return response()->json(['error' => 'Missing required fields'], 400);
            }

            // Find the order
            $order = Order::where('payment_intent_id', $paymentIntentId)->first();
            
            if (!$order) {
                Log::warning('Order not found for payment intent', [
                    'payment_intent_id' => $paymentIntentId,
                ]);
                
                return response()->json(['error' => 'Order not found'], 400);
            }

            // Process the event
            $result = $this->processWebhookEvent($order, $eventType, $data);
            
            if ($result['success']) {
                Log::info('Webhook processed successfully', [
                    'event_type' => $eventType,
                    'payment_intent_id' => $paymentIntentId,
                    'order_id' => $order->id,
                    'old_status' => $result['old_status'],
                    'new_status' => $result['new_status'],
                ]);
                
                return response()->json(['message' => 'Webhook processed'], 200);
            } else {
                Log::warning('Webhook processing failed', [
                    'event_type' => $eventType,
                    'payment_intent_id' => $paymentIntentId,
                    'error' => $result['error'],
                ]);
                
                return response()->json(['error' => $result['error']], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Webhook processing exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Verify HMAC signature
     *
     * @param string $payload
     * @param string|null $signature
     * @param string $secret
     * @return bool
     */
    private function verifySignature(string $payload, ?string $signature, string $secret): bool
    {
        if (!$signature) {
            return false;
        }

        // Remove 'sha256=' prefix if present
        $signature = str_replace('sha256=', '', $signature);
        
        // Calculate expected signature
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        // Use hash_equals for timing-safe comparison
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process webhook event and update order status
     *
     * @param Order $order
     * @param string $eventType
     * @param array $data
     * @return array{success: bool, old_status?: string, new_status?: string, error?: string}
     */
    private function processWebhookEvent(Order $order, string $eventType, array $data): array
    {
        $oldStatus = $order->status;
        
        switch ($eventType) {
            case 'payment_authorized':
                if ($order->status !== 'pending') {
                    return [
                        'success' => false,
                        'error' => "Cannot authorize payment for order in status: {$order->status}",
                    ];
                }
                
                $order->status = 'authorized';
                $order->save();
                
                return [
                    'success' => true,
                    'old_status' => $oldStatus,
                    'new_status' => 'authorized',
                ];

            case 'payment_captured':
                if ($order->status !== 'authorized') {
                    return [
                        'success' => false,
                        'error' => "Cannot capture payment for order in status: {$order->status}",
                    ];
                }
                
                $order->status = 'captured';
                $order->save();
                
                return [
                    'success' => true,
                    'old_status' => $oldStatus,
                    'new_status' => 'captured',
                ];

            case 'payment_failed':
                $order->status = 'failed';
                $order->save();
                
                return [
                    'success' => true,
                    'old_status' => $oldStatus,
                    'new_status' => 'failed',
                ];

            default:
                return [
                    'success' => false,
                    'error' => "Unknown event type: {$eventType}",
                ];
        }
    }
}
