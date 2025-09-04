<?php

// Test tolerance calculation
$basisPrice = 200000;  // $2000.00
$currentPrice = 201001; // $2010.01
$tolerance = 50; // 50 basis points

$difference = $currentPrice - $basisPrice;
$basisPoints = intval(($difference * 10000) / $basisPrice);
$absoluteBps = abs($basisPoints);

echo "Basis Price: $basisPrice cents\n";
echo "Current Price: $currentPrice cents\n";
echo "Difference: $difference cents\n";
echo "Basis Points: $basisPoints\n";
echo "Absolute BPS: $absoluteBps\n";
echo "Tolerance: $tolerance bps\n";
echo "Should reject (abs > tolerance)? " . ($absoluteBps > $tolerance ? 'YES' : 'NO') . "\n";
echo "Math check: $absoluteBps > $tolerance = " . ($absoluteBps > $tolerance ? 'true' : 'false') . "\n";

// Test with smaller numbers to verify
echo "\n--- Test with smaller numbers ---\n";
$smallBasis = 100;
$smallCurrent = 151; // 51% increase = 5100 bps
$smallDiff = $smallCurrent - $smallBasis;
$smallBps = intval(($smallDiff * 10000) / $smallBasis);
echo "Small basis: $smallBasis, current: $smallCurrent, diff: $smallDiff, bps: $smallBps\n";
