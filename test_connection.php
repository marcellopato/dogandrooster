<?php

require 'vendor/autoload.php';

try {
    $app = require 'bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    echo "✓ Laravel bootstrapped successfully\n";
    
    // Test database connection
    $connection = DB::connection();
    echo "✓ Database connection established\n";
    
    // Test query
    $result = DB::select('SHOW TABLES');
    echo "✓ Database query successful\n";
    echo "Tables found: " . count($result) . "\n";
    
    foreach ($result as $table) {
        $tableName = array_values((array)$table)[0];
        echo "  - {$tableName}\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
