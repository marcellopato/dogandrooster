<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SpotPrice;
use Carbon\Carbon;

class SpotPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        $spotPrices = [
            [
                'metal_type' => 'gold',
                'price_per_oz_cents' => 200000, // $2,000.00 per ounce
                'effective_at' => $now,
                'is_current' => true,
            ],
            [
                'metal_type' => 'silver',
                'price_per_oz_cents' => 2500, // $25.00 per ounce
                'effective_at' => $now,
                'is_current' => true,
            ],
            [
                'metal_type' => 'platinum',
                'price_per_oz_cents' => 95000, // $950.00 per ounce
                'effective_at' => $now,
                'is_current' => true,
            ],
        ];

        foreach ($spotPrices as $priceData) {
            // First, mark any existing current prices as not current
            SpotPrice::where('metal_type', $priceData['metal_type'])
                     ->where('is_current', true)
                     ->update(['is_current' => false]);
            
            // Then create the new current price
            SpotPrice::create($priceData);
        }
    }
}
