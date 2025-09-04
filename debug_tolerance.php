<?php

require_once 'vendor/autoload.php';

use App\Models\SpotPrice;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->bootstrap();

echo "Testing tolerance calculation...\n";

// Test values from the test
$basisPrice = 200000;
$currentPrice = 201001;
$tolerance = 50;

echo "Basis price: $basisPrice\n";
echo "Current price: $currentPrice\n";
echo "Tolerance: $tolerance bps\n";

// Manual calculation
$difference = $currentPrice - $basisPrice;
$basisPoints = intval(($difference * 10000) / $basisPrice);

echo "Difference: $difference\n";
echo "Calculated basis points: $basisPoints\n";
echo "Should breach tolerance (> $tolerance)? " . ($basisPoints > $tolerance ? 'YES' : 'NO') . "\n";

// Test with model
$spot = new SpotPrice();
$spot->price_per_oz_cents = $currentPrice;
$calculatedBps = $spot->calculateBasisPointsDiff($basisPrice);

echo "Model calculated basis points: $calculatedBps\n";
echo "Absolute value: " . abs($calculatedBps) . "\n";
echo "Should breach tolerance (abs > $tolerance)? " . (abs($calculatedBps) > $tolerance ? 'YES' : 'NO') . "\n";
