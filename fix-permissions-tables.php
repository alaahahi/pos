<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Fixing permissions tables ===\n\n";

// Fix model_has_permissions
try {
    echo "1. Fixing model_has_permissions...\n";
    
    // Drop if exists
    DB::connection('sync_sqlite')->statement('DROP TABLE IF EXISTS model_has_permissions');
    
    // Get structure from MySQL
    $mysqlColumns = DB::connection('mysql')->select("SHOW COLUMNS FROM model_has_permissions");
    
    // Create table
    $createTable = "CREATE TABLE model_has_permissions (";
    $columnDefinitions = [];
    $primaryKeyColumns = [];
    
    foreach ($mysqlColumns as $column) {
        $name = $column->Field;
        $type = strtoupper($column->Type);
        
        if (strpos($type, 'INT') !== false || strpos($type, 'BIGINT') !== false) {
            $sqliteType = 'INTEGER';
        } else {
            $sqliteType = 'TEXT';
        }
        
        $null = $column->Null === 'YES' ? '' : 'NOT NULL';
        
        if ($column->Key === 'PRI') {
            $primaryKeyColumns[] = "`{$name}`";
            $columnDefinitions[] = "`{$name}` {$sqliteType} {$null}";
        } else {
            $columnDefinitions[] = "`{$name}` {$sqliteType} {$null}";
        }
    }
    
    $createTable .= implode(', ', $columnDefinitions);
    
    if (count($primaryKeyColumns) > 0) {
        $createTable .= ', PRIMARY KEY (' . implode(', ', $primaryKeyColumns) . ')';
    }
    
    $createTable .= ')';
    
    DB::connection('sync_sqlite')->statement($createTable);
    echo "   ✅ Table created\n";
    
    // Copy data
    $rows = DB::connection('mysql')->table('model_has_permissions')->get();
    $synced = 0;
    foreach ($rows as $row) {
        try {
            $rowArray = (array) $row;
            DB::connection('sync_sqlite')->table('model_has_permissions')->insert($rowArray);
            $synced++;
        } catch (\Exception $e) {
            // Skip
        }
    }
    echo "   ✅ Copied {$synced} records\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Fix role_has_permissions
try {
    echo "\n2. Fixing role_has_permissions...\n";
    
    // Drop if exists
    DB::connection('sync_sqlite')->statement('DROP TABLE IF EXISTS role_has_permissions');
    
    // Get structure from MySQL
    $mysqlColumns = DB::connection('mysql')->select("SHOW COLUMNS FROM role_has_permissions");
    
    // Create table
    $createTable = "CREATE TABLE role_has_permissions (";
    $columnDefinitions = [];
    $primaryKeyColumns = [];
    
    foreach ($mysqlColumns as $column) {
        $name = $column->Field;
        $type = strtoupper($column->Type);
        
        if (strpos($type, 'INT') !== false || strpos($type, 'BIGINT') !== false) {
            $sqliteType = 'INTEGER';
        } else {
            $sqliteType = 'TEXT';
        }
        
        $null = $column->Null === 'YES' ? '' : 'NOT NULL';
        
        if ($column->Key === 'PRI') {
            $primaryKeyColumns[] = "`{$name}`";
            $columnDefinitions[] = "`{$name}` {$sqliteType} {$null}";
        } else {
            $columnDefinitions[] = "`{$name}` {$sqliteType} {$null}";
        }
    }
    
    $createTable .= implode(', ', $columnDefinitions);
    
    if (count($primaryKeyColumns) > 0) {
        $createTable .= ', PRIMARY KEY (' . implode(', ', $primaryKeyColumns) . ')';
    }
    
    $createTable .= ')';
    
    DB::connection('sync_sqlite')->statement($createTable);
    echo "   ✅ Table created\n";
    
    // Copy data
    $rows = DB::connection('mysql')->table('role_has_permissions')->get();
    $synced = 0;
    foreach ($rows as $row) {
        try {
            $rowArray = (array) $row;
            DB::connection('sync_sqlite')->table('role_has_permissions')->insert($rowArray);
            $synced++;
        } catch (\Exception $e) {
            // Skip
        }
    }
    echo "   ✅ Copied {$synced} records\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Done!\n";


