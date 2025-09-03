<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\SpotPrice;

echo "=== Database Check ===\n\n";

try {
    $products = Product::all();
    echo "Products in database:\n";
    foreach ($products as $product) {
        echo "- SKU: {$product->sku} | Name: {$product->name}\n";
    }
    echo "Total products: " . $products->count() . "\n\n";
    
    $spotPrices = SpotPrice::all();
    echo "Spot prices in database:\n";
    foreach ($spotPrices as $spot) {
        echo "- {$spot->metal_type}: \${$spot->price_per_oz_cents}\n";
    }
    echo "Total spot prices: " . $spotPrices->count() . "\n\n";
    
    // Test if INVALID-SKU exists
    $invalidProduct = Product::where('sku', 'INVALID-SKU')->first();
    if ($invalidProduct) {
        echo "❌ INVALID-SKU found in database (unexpected)\n";
    } else {
        echo "✓ INVALID-SKU not found in database (expected)\n";
    }
    
    // Test if GOLD_1OZ exists
    $goldProduct = Product::where('sku', 'GOLD_1OZ')->first();
    if ($goldProduct) {
        echo "✓ GOLD_1OZ found in database\n";
    } else {
        echo "❌ GOLD_1OZ not found in database\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Check Complete ===\n";
