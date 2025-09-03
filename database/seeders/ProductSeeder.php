<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'sku' => 'GOLD_1OZ',
                'name' => 'Gold 1 Ounce Coin',
                'metal_type' => 'gold',
                'weight_oz' => '1.0000',
                'premium_cents' => 5000, // $50.00 premium
                'active' => true,
            ],
            [
                'sku' => 'SILVER_1OZ',
                'name' => 'Silver 1 Ounce Coin',
                'metal_type' => 'silver',
                'weight_oz' => '1.0000',
                'premium_cents' => 300, // $3.00 premium
                'active' => true,
            ],
            [
                'sku' => 'GOLD_HALF_OZ',
                'name' => 'Gold Half Ounce Coin',
                'metal_type' => 'gold',
                'weight_oz' => '0.5000',
                'premium_cents' => 3000, // $30.00 premium
                'active' => true,
            ],
            [
                'sku' => 'SILVER_10OZ',
                'name' => 'Silver 10 Ounce Bar',
                'metal_type' => 'silver',
                'weight_oz' => '10.0000',
                'premium_cents' => 2000, // $20.00 premium
                'active' => true,
            ],
        ];

        foreach ($products as $productData) {
            Product::updateOrCreate(
                ['sku' => $productData['sku']],
                $productData
            );
        }
    }
}
