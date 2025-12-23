<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Testing SQLite Connection ===\n\n";

$sqlitePath = config('database.connections.sync_sqlite.database');
echo "Path from config: {$sqlitePath}\n";
echo "Real path: " . realpath($sqlitePath) . "\n";
echo "File exists: " . (file_exists($sqlitePath) ? 'Yes' : 'No') . "\n";
echo "File size: " . filesize($sqlitePath) . " bytes\n\n";

try {
    $pdo = DB::connection('sync_sqlite')->getPdo();
    echo "✅ Connection successful\n\n";
    
    // Get tables
    $tables = DB::connection('sync_sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
    echo "Tables count: " . count($tables) . "\n";
    
    if (count($tables) > 0) {
        echo "\nFirst 10 tables:\n";
        foreach (array_slice($tables, 0, 10) as $table) {
            $count = DB::connection('sync_sqlite')->table($table->name)->count();
            echo "  - {$table->name}: {$count} records\n";
        }
    }
    
    // Check users table
    $usersExists = DB::connection('sync_sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    if (count($usersExists) > 0) {
        $usersCount = DB::connection('sync_sqlite')->table('users')->count();
        echo "\n✅ Users table exists: {$usersCount} users\n";
        
        $firstUser = DB::connection('sync_sqlite')->table('users')->first();
        if ($firstUser) {
            echo "First user: {$firstUser->name} ({$firstUser->email})\n";
        }
    } else {
        echo "\n❌ Users table does NOT exist!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}


