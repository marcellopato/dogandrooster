<?php

// Test API internally without HTTP
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Requests\Api\QuoteRequest;
use Illuminate\Support\Facades\Validator;

echo "=== Testing Quote API with INVALID-SKU ===\n\n";

try {
    // Simulate the exact request you made
    $data = [
        'sku' => 'INVALID-SKU',
        'qty' => 5
    ];
    
    echo "Testing data: " . json_encode($data) . "\n\n";
    
    // Create a mock request
    $request = Request::create('/api/quote', 'POST', $data);
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('Accept', 'application/json');
    
    // Validate the request first
    echo "1. Testing validation...\n";
    $validator = Validator::make($data, [
        'sku' => 'required|string|max:255',
        'qty' => 'required|integer|min:1|max:1000',
    ]);
    
    if ($validator->fails()) {
        echo "❌ Validation failed:\n";
        foreach ($validator->errors()->all() as $error) {
            echo "  - {$error}\n";
        }
        exit(1);
    }
    
    echo "✓ Validation passed\n\n";
    
    // Test the controller directly
    echo "2. Testing QuoteController...\n";
    
    $controller = new QuoteController();
    
    // Create a proper QuoteRequest instance
    $quoteRequest = QuoteRequest::createFrom($request);
    
    // Call the controller method
    $response = $controller->store($quoteRequest);
    
    echo "Response Status: {$response->getStatusCode()}\n";
    echo "Response Content: {$response->getContent()}\n\n";
    
    // Decode and analyze the response
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() === 404) {
        echo "✓ Correct! Returned 404 for invalid SKU\n";
        if (isset($responseData['error']) && $responseData['error'] === 'PRODUCT_NOT_FOUND') {
            echo "✓ Correct error message: PRODUCT_NOT_FOUND\n";
        } else {
            echo "❌ Unexpected error message: " . ($responseData['error'] ?? 'none') . "\n";
        }
    } else {
        echo "❌ Unexpected status code. Expected 404\n";
    }
    
    // Now test with valid SKU for comparison
    echo "\n3. Testing with valid SKU for comparison...\n";
    
    $validData = [
        'sku' => 'GOLD_1OZ',
        'qty' => 1
    ];
    
    $validRequest = Request::create('/api/quote', 'POST', $validData);
    $validQuoteRequest = QuoteRequest::createFrom($validRequest);
    
    $validResponse = $controller->store($validQuoteRequest);
    
    echo "Valid SKU Response Status: {$validResponse->getStatusCode()}\n";
    echo "Valid SKU Response: " . substr($validResponse->getContent(), 0, 100) . "...\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
