<?php

// Simple seeder with direct SQL
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Creating tables and inserting data...\n";
    
    // Recreate tables
    DB::statement('DROP TABLE IF EXISTS order_lines');
    DB::statement('DROP TABLE IF EXISTS orders');
    DB::statement('DROP TABLE IF EXISTS price_quotes');
    DB::statement('DROP TABLE IF EXISTS spot_prices');
    DB::statement('DROP TABLE IF EXISTS products');
    
    // Create products table
    DB::statement('
        CREATE TABLE products (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sku VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            metal_type ENUM("gold", "silver", "platinum") NOT NULL,
            weight_oz DECIMAL(8,4) NOT NULL,
            premium_cents INT NOT NULL,
            active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )
    ');
    
    // Create spot_prices table
    DB::statement('
        CREATE TABLE spot_prices (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            metal_type ENUM("gold", "silver", "platinum") NOT NULL,
            price_per_oz_cents INT NOT NULL,
            effective_at TIMESTAMP NOT NULL,
            is_current BOOLEAN NOT NULL DEFAULT FALSE,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )
    ');
    
    // Create price_quotes table
    DB::statement('
        CREATE TABLE price_quotes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            quantity INT NOT NULL,
            unit_price_cents INT NOT NULL,
            basis_spot_cents INT NOT NULL,
            basis_version BIGINT UNSIGNED NOT NULL,
            quote_expires_at TIMESTAMP NOT NULL,
            tolerance_bps INT NOT NULL DEFAULT 50,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )
    ');
    
    // Insert sample data
    DB::table('products')->insert([
        [
            'sku' => 'GOLD_1OZ',
            'name' => 'Gold 1 Ounce Coin',
            'metal_type' => 'gold',
            'weight_oz' => 1.0000,
            'premium_cents' => 5000,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'sku' => 'SILVER-1OZ',
            'name' => 'Silver 1 Ounce Coin',
            'metal_type' => 'silver',
            'weight_oz' => 1.0000,
            'premium_cents' => 300,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    ]);
    
    DB::table('spot_prices')->insert([
        [
            'metal_type' => 'gold',
            'price_per_oz_cents' => 200000,
            'effective_at' => now(),
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'metal_type' => 'silver',
            'price_per_oz_cents' => 2500,
            'effective_at' => now(),
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    ]);
    
    echo "✓ Tables created successfully\n";
    echo "✓ Data inserted successfully\n";
    echo "Products: " . DB::table('products')->count() . "\n";
    echo "Spot Prices: " . DB::table('spot_prices')->count() . "\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
