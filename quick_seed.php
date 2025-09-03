<?php

// Quick seeder script
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\SpotPrice;

// Create products if they don't exist
if (Product::count() === 0) {
    echo "Creating products...\n";
    
    Product::create([
        'sku' => 'GOLD-1OZ',
        'name' => 'Gold 1 Ounce Coin',
        'metal_type' => 'gold',
        'weight_oz' => '1.0000',
        'premium_cents' => 5000,
        'active' => true,
    ]);
    
    Product::create([
        'sku' => 'SILVER-1OZ',
        'name' => 'Silver 1 Ounce Coin',
        'metal_type' => 'silver',
        'weight_oz' => '1.0000',
        'premium_cents' => 300,
        'active' => true,
    ]);
    
    echo "Products created!\n";
} else {
    echo "Products already exist: " . Product::count() . "\n";
}

// Create spot prices if they don't exist
if (SpotPrice::count() === 0) {
    echo "Creating spot prices...\n";
    
    SpotPrice::create([
        'metal_type' => 'gold',
        'price_per_oz_cents' => 200000, // $2000
        'effective_at' => now(),
        'is_current' => true,
    ]);
    
    SpotPrice::create([
        'metal_type' => 'silver',
        'price_per_oz_cents' => 2500, // $25
        'effective_at' => now(),
        'is_current' => true,
    ]);
    
    echo "Spot prices created!\n";
} else {
    echo "Spot prices already exist: " . SpotPrice::count() . "\n";
}

echo "Seeding complete!\n";
echo "Products: " . Product::count() . "\n";
echo "Spot Prices: " . SpotPrice::count() . "\n";
