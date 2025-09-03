<?php

// Simple test for models and database
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\SpotPrice;
use App\Models\PriceQuote;

echo "=== Simple Model Test ===\n\n";

try {
    // Test products
    echo "Products in database:\n";
    $products = Product::all();
    foreach ($products as $product) {
        echo "- {$product->sku}: {$product->name}\n";
    }
    echo "\n";
    
    // Test spot prices
    echo "Spot prices in database:\n";
    $spotPrices = SpotPrice::all();
    foreach ($spotPrices as $spot) {
        echo "- {$spot->metal_type}: $" . ($spot->price_per_oz_cents / 100) . "/oz\n";
    }
    echo "\n";
    
    // Test creating a quote
    echo "Creating a test quote...\n";
    $product = Product::first();
    $spotPrice = SpotPrice::getLatest();
    
    if ($product && $spotPrice) {
        $unitPrice = $product->calculateUnitPrice($spotPrice->price_per_oz_cents);
        
        $quote = PriceQuote::create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_cents' => $unitPrice,
            'basis_spot_cents' => $spotPrice->price_per_oz_cents,
            'basis_version' => $spotPrice->id,
            'quote_expires_at' => now()->addMinutes(5),
            'tolerance_bps' => 50,
        ]);
        
        echo "✓ Quote created successfully!\n";
        echo "Quote ID: {$quote->id}\n";
        echo "Product: {$product->sku}\n";
        echo "Unit Price: $" . ($unitPrice / 100) . "\n";
        echo "Expires: {$quote->quote_expires_at}\n\n";
        
        // Test quote relationships
        echo "Testing relationships...\n";
        $quoteProduct = $quote->product;
        echo "✓ Quote -> Product: {$quoteProduct->name}\n";
        
    } else {
        echo "❌ Missing product or spot price data\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";
