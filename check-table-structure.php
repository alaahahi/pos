<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking model_has_roles table ===\n\n";

try {
    // Check if table exists
    $tableExists = DB::connection('sync_sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' AND name='model_has_roles'");
    
    if (count($tableExists) > 0) {
        echo "✅ Table exists\n\n";
        
        // Get table structure
        $structure = DB::connection('sync_sqlite')->select("PRAGMA table_info(model_has_roles)");
        echo "Columns:\n";
        foreach ($structure as $col) {
            echo "  - {$col->name} ({$col->type})\n";
        }
        
        // Get data count
        $count = DB::connection('sync_sqlite')->table('model_has_roles')->count();
        echo "\nRecords: {$count}\n";
        
        if ($count > 0) {
            $first = DB::connection('sync_sqlite')->table('model_has_roles')->first();
            echo "\nFirst record:\n";
            print_r($first);
        }
    } else {
        echo "❌ Table does NOT exist\n";
    }
    
    // Check MySQL structure for comparison
    echo "\n=== MySQL structure (for comparison) ===\n";
    try {
        $mysqlStructure = DB::connection('mysql')->select("SHOW COLUMNS FROM model_has_roles");
        echo "MySQL Columns:\n";
        foreach ($mysqlStructure as $col) {
            echo "  - {$col->Field} ({$col->Type})\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}


