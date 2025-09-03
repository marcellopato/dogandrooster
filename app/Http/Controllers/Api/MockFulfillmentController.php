<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Mock Fulfillment Controller for testing inventory checks
 */
class MockFulfillmentController extends Controller
{
    /**
     * Mock inventory data
     */
    private static array $inventory = [
        'GOLD_1OZ' => 100,
        'SILVER-1OZ' => 500,
        'PLATINUM-1OZ' => 50,
    ];

    /**
     * Get availability for a specific SKU
     *
     * @param string $sku
     * @return JsonResponse
     */
    public function getAvailability(string $sku): JsonResponse
    {
        $availableQty = self::$inventory[$sku] ?? 0;

        return response()->json([
            'sku' => $sku,
            'available_qty' => $availableQty,
            'in_stock' => $availableQty > 0,
        ]);
    }

    /**
     * Set availability for a specific SKU (for testing)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'sku' => 'required|string',
            'quantity' => 'required|integer|min:0',
        ]);

        $sku = $request->input('sku');
        $quantity = $request->input('quantity');

        self::$inventory[$sku] = $quantity;

        return response()->json([
            'sku' => $sku,
            'available_qty' => $quantity,
            'message' => 'Inventory updated successfully',
        ]);
    }

    /**
     * Reset inventory to default values (for testing)
     *
     * @return JsonResponse
     */
    public function resetInventory(): JsonResponse
    {
        self::$inventory = [
            'GOLD_1OZ' => 100,
            'SILVER-1OZ' => 500,
            'PLATINUM-1OZ' => 50,
        ];

        return response()->json([
            'message' => 'Inventory reset to default values',
            'inventory' => self::$inventory,
        ]);
    }
}
