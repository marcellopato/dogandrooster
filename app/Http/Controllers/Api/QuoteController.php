<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\QuoteRequest;
use App\Models\PriceQuote;
use App\Models\Product;
use App\Models\SpotPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(
 *     title="Precious Metals E-commerce API",
 *     version="1.0.0",
 *     description="API for precious metals pricing with volatile spot-indexed prices"
 * )
 */
class QuoteController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/quote",
     *     summary="Generate a price quote for precious metals",
     *     tags={"Quotes"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"sku","qty"},
     *
     *             @OA\Property(property="sku", type="string", example="GOLD_1OZ"),
     *             @OA\Property(property="qty", type="integer", example=1)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Quote generated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="quote_id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="unit_price_cents", type="integer", example=205000),
     *             @OA\Property(property="quote_expires_at", type="string", example="2025-09-03T15:30:00.000000Z")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="PRODUCT_NOT_FOUND")
     *         )
     *     )
     * )
     */
    public function store(QuoteRequest $request): JsonResponse
    {
        try {
            $sku = $request->input('sku');
            $qty = $request->input('qty');

            // Find the product
            $product = Product::where('sku', $sku)->first();
            if (! $product) {
                return response()->json(
                    ['error' => 'PRODUCT_NOT_FOUND'],
                    404
                );
            }

            // Get current spot price
            $currentSpot = SpotPrice::getLatest();
            if (! $currentSpot) {
                return response()->json(
                    ['error' => 'SPOT_PRICE_UNAVAILABLE'],
                    503
                );
            }

            // Calculate unit price using integer math only
            $unitPriceCents = $product->calculateUnitPrice(
                $currentSpot->price_per_oz_cents
            );

            // Create the price quote
            $quote = PriceQuote::create(
                [
                    'sku' => $sku,
                    'quantity' => $qty,
                    'unit_price_cents' => $unitPriceCents,
                    'total_price_cents' => $unitPriceCents * $qty,
                    'basis_spot_cents' => $currentSpot->price_per_oz_cents,
                    'basis_version' => $currentSpot->id,
                    'quote_expires_at' => now()->addMinutes(
                        config('pricing.quote_expiry_minutes', 5)
                    ),
                    'tolerance_bps' => config('pricing.default_tolerance_bps', 50),
                ]
            );

            // Log::info(
            //     'Price quote generated',
            //     [
            //         'quote_id' => $quote->quote_id,
            //         'sku' => $sku,
            //         'qty' => $qty,
            //         'unit_price_cents' => $unitPriceCents,
            //         'basis_spot_cents' => $currentSpot->price_per_oz_cents,
            //         'expires_at' => $quote->quote_expires_at->toISOString(),
            //     ]
            // );

            return response()->json(
                [
                    'quote_id' => $quote->quote_id,
                    'unit_price_cents' => $unitPriceCents,
                    'quote_expires_at' => $quote->quote_expires_at->toISOString(),
                ]
            );

        } catch (\Exception $e) {
            // Log::error(
            //     'Quote generation failed',
            //     [
            //         'error' => $e->getMessage(),
            //         'sku' => $request->input('sku'),
            //         'qty' => $request->input('qty'),
            //     ]
            // );

            return response()->json(
                ['error' => 'QUOTE_GENERATION_FAILED'],
                500
            );
        }
    }
}
