<?php

// Script para corrigir todas as ocorrências de basis_version hardcoded
$files = [
    'tests/Feature/Checkout/InventoryCheckTest.php',
    'tests/Feature/Checkout/QuoteExpiryTest.php', 
    'tests/Feature/Checkout/TotalsIntegrityTest.php',
    'tests/Feature/Webhooks/InvalidSignatureTest.php'
];

foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (!file_exists($fullPath)) {
        echo "File not found: $fullPath\n";
        continue;
    }
    
    $content = file_get_contents($fullPath);
    
    // Pattern to find PriceQuote::create calls that have 'basis_version' => 1
    $pattern = '/(\$\w+\s*=\s*PriceQuote::create\(\[\s*[^}]+?)\'basis_version\'\s*=>\s*1,/s';
    
    $replacement = '$1\'basis_version\' => SpotPrice::where(\'metal_type\', \'gold\')->first()->id,';
    
    $newContent = preg_replace($pattern, $replacement, $content);
    
    if ($newContent !== $content) {
        file_put_contents($fullPath, $newContent);
        echo "Fixed $file\n";
    } else {
        echo "No changes needed in $file\n";
    }
}

echo "Done.\n";
