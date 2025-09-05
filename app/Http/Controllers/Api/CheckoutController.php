<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckoutRequest;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\PriceQuote;
use App\Models\Product;
use App\Models\SpotPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Checkout Controller for precious metals orders
 */
class CheckoutController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/checkout",
     *     summary="Process checkout for a price quote",
     *     tags={"Checkout"},
     *
     *     @OA\Parameter(
     *         name="Idempotency-Key",
     *         in="header",
     *         required=true,
     *
     *         @OA\Schema(type="string"),
     *         description="Unique key for idempotent requests"
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"quote_id"},
     *
     *             @OA\Property(property="quote_id", type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="order_id", type="integer", example=1),
     *             @OA\Property(property="payment_intent_id", type="string", example="pi_1234567890"),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="total_cents", type="integer", example=410000)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=409,
     *         description="Business logic conflict",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", enum={"REQUOTE_REQUIRED", "OUT_OF_STOCK"})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Idempotency-Key header is required")
     *         )
     *     )
     * )
    /**
     * Process checkout for a price quote
     */
    public function store(CheckoutRequest $request): JsonResponse
    {
        $quoteId = $request->input('quote_id');
        $idempotencyKey = $request->getIdempotencyKey();

        // Validate idempotency key is provided
        if (! $idempotencyKey) {
            return response()->json(
                ['error' => 'Idempotency-Key header is required'],
                400
            );
        }

        try {
            return DB::transaction(function () use ($quoteId, $idempotencyKey) {
                // Check if order already exists for this idempotency key
                $existingOrder = Order::where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existingOrder) {
                    // Return existing order (idempotency)
                    return response()->json([
                        'order_id' => $existingOrder->id,
                        'payment_intent_id' => $existingOrder->payment_intent_id,
                        'status' => $existingOrder->status,
                        'total_cents' => $existingOrder->total_cents,
                    ]);
                }

                // Load quote
                $quote = PriceQuote::where('quote_id', $quoteId)->first();

                if (! $quote) {
                    return response()->json(
                        ['error' => 'QUOTE_NOT_FOUND'],
                        404
                    );
                }

                // Check if quote has expired
                if (now()->gte($quote->quote_expires_at)) {
                    return response()->json(
                        ['error' => 'REQUOTE_REQUIRED'],
                        409
                    );
                }

                // Check price tolerance
                // Get the product to determine metal type
                $product = Product::where('sku', $quote->sku)->first();
                if (! $product) {
                    return response()->json(
                        ['error' => 'PRODUCT_NOT_FOUND'],
                        404
                    );
                }

                $currentSpot = SpotPrice::getCurrent($product->metal_type);
                if (! $currentSpot) {
                    return response()->json(
                        ['error' => 'SPOT_PRICE_UNAVAILABLE'],
                        503
                    );
                }

                // Calculate basis points difference
                $basisPointsDiff = abs($currentSpot->calculateBasisPointsDiff(
                    $quote->basis_spot_cents
                ));

                if ($basisPointsDiff > $quote->tolerance_bps) {
                    Log::info('Price tolerance breached', [
                        'quote_id' => $quoteId,
                        'basis_spot' => $quote->basis_spot_cents,
                        'current_spot' => $currentSpot->price_per_oz_cents,
                        'tolerance_bps' => $quote->tolerance_bps,
                        'actual_diff_bps' => $basisPointsDiff,
                    ]);

                    return response()->json(
                        ['error' => 'REQUOTE_REQUIRED'],
                        409
                    );
                }

                // Check fulfillment inventory
                $inventoryCheck = $this->checkInventory(
                    $quote->sku,
                    $quote->quantity
                );

                if (! $inventoryCheck['available']) {
                    Log::info('Inventory check failed', [
                        'sku' => $quote->sku,
                        'requested_qty' => $quote->quantity,
                        'available_qty' => $inventoryCheck['available_qty'] ?? 0,
                    ]);

                    return response()->json(
                        ['error' => 'OUT_OF_STOCK'],
                        409
                    );
                }

                // Create order and order lines within transaction
                $paymentIntentId = 'pi_'.Str::random(24);
                $totalCents = $quote->unit_price_cents * $quote->quantity;

                $order = Order::create([
                    'idempotency_key' => $idempotencyKey,
                    'quote_id' => $quote->quote_id,
                    'payment_intent_id' => $paymentIntentId,
                    'status' => 'pending',
                    'total_cents' => $totalCents,
                ]);

                OrderLine::create([
                    'order_id' => $order->id,
                    'sku' => $quote->sku,
                    'quantity' => $quote->quantity,
                    'unit_price_cents' => $quote->unit_price_cents,
                    'subtotal_cents' => $totalCents,
                ]);

                Log::info('Order created successfully', [
                    'order_id' => $order->id,
                    'quote_id' => $quoteId,
                    'payment_intent_id' => $paymentIntentId,
                    'total_cents' => $totalCents,
                ]);

                return response()->json([
                    'order_id' => $order->id,
                    'payment_intent_id' => $paymentIntentId,
                    'status' => $order->status,
                    'total_cents' => $totalCents,
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('Checkout failed', [
                'quote_id' => $quoteId,
                'idempotency_key' => $idempotencyKey,
                'error' => $e->getMessage(),
            ]);

            return response()->json(
                ['error' => 'CHECKOUT_FAILED'],
                500
            );
        }
    }

    /**
     * Check inventory availability with fulfillment partner
     *
     * @return array{available: bool, available_qty?: int}
     */
    private function checkInventory(string $sku, int $quantity): array
    {
        try {
            // Check cache first (for testing and local development)
            if (app()->environment(['testing', 'local'])) {
                if (Cache::has('mock_inventory')) {
                    $inventory = Cache::get('mock_inventory', []);
                    $availableQty = $inventory[$sku] ?? 0;

                    return [
                        'available' => $availableQty >= $quantity,
                        'available_qty' => $availableQty,
                    ];
                } else {
                    // No mock set - simulate API error
                    return ['available' => false];
                }
            }

            // For production, use HTTP API call
            $baseUrl = config('app.url');
            $url = $baseUrl."/api/mock-fulfillment/availability/{$sku}";

            $response = Http::timeout(5)->get($url);

            if (! $response->successful()) {
                Log::warning('Fulfillment API unavailable', [
                    'sku' => $sku,
                    'status' => $response->status(),
                    'url' => $url,
                ]);

                return ['available' => false];
            }

            $data = $response->json();
            $availableQty = $data['available_qty'] ?? 0;

            return [
                'available' => $availableQty >= $quantity,
                'available_qty' => $availableQty,
            ];

        } catch (\Exception $e) {
            Log::error('Inventory check error', [
                'sku' => $sku,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);

            return ['available' => false];
        }
    }
}
