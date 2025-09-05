<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Set up mock inventory for development and testing
        $inventory = [
            'GOLD_1OZ' => 100,
            'SILVER_1OZ' => 500,
            'PLATINUM_1OZ' => 50,
        ];

        Cache::put('mock_inventory', $inventory);

        $this->command->info('Mock inventory seeded with default values');
    }
}
