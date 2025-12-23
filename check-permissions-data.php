<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking model_has_permissions data ===\n\n";

try {
    // Check data in SQLite
    $data = DB::connection('sync_sqlite')->table('model_has_permissions')->get();
    echo "SQLite records: " . count($data) . "\n";
    
    if (count($data) > 0) {
        echo "\nFirst 5 records:\n";
        foreach (array_slice($data, 0, 5) as $record) {
            echo "  - permission_id: {$record->permission_id}, model_id: {$record->model_id}, model_type: '{$record->model_type}'\n";
        }
    }
    
    // Check data in MySQL for comparison
    echo "\n=== MySQL data (for comparison) ===\n";
    $mysqlData = DB::connection('mysql')->table('model_has_permissions')->get();
    echo "MySQL records: " . count($mysqlData) . "\n";
    
    if (count($mysqlData) > 0) {
        echo "\nFirst 5 records:\n";
        foreach (array_slice($mysqlData, 0, 5) as $record) {
            echo "  - permission_id: {$record->permission_id}, model_id: {$record->model_id}, model_type: '{$record->model_type}'\n";
        }
    }
    
    // Test query
    echo "\n=== Testing query ===\n";
    try {
        $result = DB::connection('sync_sqlite')
            ->table('model_has_permissions')
            ->where('model_id', 2)
            ->where('model_type', 'App\Models\User')
            ->get();
        echo "✅ Query successful: " . count($result) . " records\n";
    } catch (\Exception $e) {
        echo "❌ Query failed: " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}


