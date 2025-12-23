<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Fixing model_has_roles table ===\n\n";

try {
    // Drop existing table if it has wrong structure
    DB::connection('sync_sqlite')->statement('DROP TABLE IF EXISTS model_has_roles');
    echo "✅ Dropped old table\n";
    
    // Get structure from MySQL
    $mysqlColumns = DB::connection('mysql')->select("SHOW COLUMNS FROM model_has_roles");
    
    // Create table with correct structure
    $createTable = "CREATE TABLE model_has_roles (";
    $columnDefinitions = [];
    $primaryKeyColumns = [];
    
    foreach ($mysqlColumns as $column) {
        $name = $column->Field;
        $type = strtoupper($column->Type);
        
        // Convert MySQL types to SQLite
        if (strpos($type, 'INT') !== false || strpos($type, 'BIGINT') !== false) {
            $sqliteType = 'INTEGER';
        } elseif (strpos($type, 'VARCHAR') !== false || strpos($type, 'TEXT') !== false || strpos($type, 'CHAR') !== false) {
            $sqliteType = 'TEXT';
        } else {
            $sqliteType = 'TEXT';
        }
        
        $null = $column->Null === 'YES' ? '' : 'NOT NULL';
        $default = '';
        
        if ($column->Default !== null && $column->Default !== '') {
            if (is_numeric($column->Default)) {
                $default = "DEFAULT {$column->Default}";
            } else {
                $default = "DEFAULT '{$column->Default}'";
            }
        }
        
        if ($column->Key === 'PRI') {
            $primaryKeyColumns[] = "`{$name}`";
            $columnDefinitions[] = "`{$name}` {$sqliteType} {$null} {$default}";
        } else {
            $columnDefinitions[] = "`{$name}` {$sqliteType} {$null} {$default}";
        }
    }
    
    $createTable .= implode(', ', $columnDefinitions);
    
    // Add composite primary key if exists
    if (count($primaryKeyColumns) > 0) {
        $createTable .= ', PRIMARY KEY (' . implode(', ', $primaryKeyColumns) . ')';
    }
    
    $createTable .= ')';
    
    DB::connection('sync_sqlite')->statement($createTable);
    echo "✅ Created table with correct structure\n";
    
    // Copy data from MySQL
    $rows = DB::connection('mysql')->table('model_has_roles')->get();
    $synced = 0;
    
    foreach ($rows as $row) {
        try {
            $rowArray = (array) $row;
            DB::connection('sync_sqlite')->table('model_has_roles')->insert($rowArray);
            $synced++;
        } catch (\Exception $e) {
            echo "Warning: Failed to insert record: " . $e->getMessage() . "\n";
        }
    }
    
    echo "✅ Copied {$synced} records\n";
    
    // Verify
    $structure = DB::connection('sync_sqlite')->select("PRAGMA table_info(model_has_roles)");
    echo "\n✅ Table structure:\n";
    foreach ($structure as $col) {
        echo "  - {$col->name} ({$col->type})\n";
    }
    
    $count = DB::connection('sync_sqlite')->table('model_has_roles')->count();
    echo "\n✅ Total records: {$count}\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

