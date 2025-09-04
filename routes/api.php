<?php

use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\MockFulfillmentController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Quote API
Route::post('/quote', [QuoteController::class, 'store']);

// Checkout API
Route::post('/checkout', [CheckoutController::class, 'store']);

// Payment Webhooks
Route::post('/webhooks/payments', [WebhookController::class, 'handlePayment']);

// Mock Fulfillment API
Route::prefix('mock-fulfillment')->group(function () {
    Route::get('/availability/{sku}', [MockFulfillmentController::class, 'getAvailability']);
    Route::post('/availability', [MockFulfillmentController::class, 'setAvailability']);
    Route::post('/reset-inventory', [MockFulfillmentController::class, 'resetInventory']);
});
