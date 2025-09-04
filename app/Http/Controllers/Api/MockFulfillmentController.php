<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Mock Fulfillment Controller for testing inventory checks
 */
class MockFulfillmentController extends Controller
{
    /**
     * Get availability for a specific SKU
     */
    public function getAvailability(string $sku): JsonResponse
    {
        $inventory = Cache::get('mock_inventory', [
            'GOLD_1OZ' => 100,
            'SILVER_1OZ' => 500,
            'PLATINUM_1OZ' => 50,
        ]);

        $availableQty = $inventory[$sku] ?? 0;

        return response()->json([
            'sku' => $sku,
            'available_qty' => $availableQty,
            'in_stock' => $availableQty > 0,
        ]);
    }

    /**
     * Set availability for a specific SKU (for testing)
     */
    public function setAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'sku' => 'required|string',
            'available_qty' => 'required|integer|min:0',
        ]);

        $sku = $request->input('sku');
        $quantity = $request->input('available_qty');

        $inventory = Cache::get('mock_inventory', [
            'GOLD_1OZ' => 100,
            'SILVER_1OZ' => 500,
            'PLATINUM_1OZ' => 50,
        ]);

        $inventory[$sku] = $quantity;
        Cache::put('mock_inventory', $inventory);

        return response()->json([
            'sku' => $sku,
            'available_qty' => $quantity,
            'message' => 'Inventory updated successfully',
        ]);
    }

    /**
     * Reset inventory to default values (for testing)
     */
    public function resetInventory(): JsonResponse
    {
        $defaultInventory = [
            'GOLD_1OZ' => 100,
            'SILVER_1OZ' => 500,
            'PLATINUM_1OZ' => 50,
        ];

        Cache::put('mock_inventory', $defaultInventory);

        return response()->json([
            'message' => 'Inventory reset to default values',
            'inventory' => $defaultInventory,
        ]);
    }
}
