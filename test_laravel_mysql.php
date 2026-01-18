<?php

/**
 * Test Laravel MySQL connection directly (bypass AppServiceProvider logic)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n========================================\n";
echo "   Laravel MySQL Connection Test\n";
echo "========================================\n\n";

try {
    // Force MySQL connection
    echo "Testing MySQL connection (bypassing default)...\n\n";
    
    // Get MySQL connection explicitly
    $db = DB::connection('mysql');
    
    echo "✓ Laravel MySQL connection established\n";
    
    // Get connection name
    $connectionName = $db->getName();
    echo "✓ Connection name: $connectionName\n";
    
    // Get database name
    $databaseName = $db->getDatabaseName();
    echo "✓ Database name: $databaseName\n";
    
    // Test query
    $result = $db->select('SELECT DATABASE() as db, VERSION() as version, NOW() as now');
    echo "✓ Query executed successfully\n";
    echo "  Database: " . $result[0]->db . "\n";
    echo "  Version: " . $result[0]->version . "\n";
    echo "  Server time: " . $result[0]->now . "\n";
    
    echo "\n";
    
    // Count tables
    $tables = $db->select('SHOW TABLES');
    echo "✓ Number of tables: " . count($tables) . "\n";
    
    // List first 10 tables
    if (count($tables) > 0) {
        echo "\nFirst 10 tables:\n";
        $tableKey = 'Tables_in_' . $databaseName;
        foreach (array_slice($tables, 0, 10) as $table) {
            echo "  - " . $table->$tableKey . "\n";
        }
    }
    
    echo "\n";
    
    // Test common models with MySQL
    echo "Testing common models on MySQL:\n";
    echo "===============================\n";
    
    try {
        $userCount = $db->table('users')->count();
        echo "✓ Users table: $userCount records\n";
    } catch (Exception $e) {
        echo "⚠️ Users table: " . $e->getMessage() . "\n";
    }
    
    try {
        $customerCount = $db->table('customers')->count();
        echo "✓ Customers table: $customerCount records\n";
    } catch (Exception $e) {
        echo "⚠️ Customers table: " . $e->getMessage() . "\n";
    }
    
    try {
        $orderCount = $db->table('orders')->count();
        echo "✓ Orders table: $orderCount records\n";
    } catch (Exception $e) {
        echo "⚠️ Orders table: " . $e->getMessage() . "\n";
    }
    
    try {
        $decorationCount = $db->table('decorations')->count();
        echo "✓ Decorations table: $decorationCount records\n";
    } catch (Exception $e) {
        echo "⚠️ Decorations table: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Show current default connection
    echo "Connection Info:\n";
    echo "================\n";
    $defaultConnection = DB::connection()->getName();
    echo "Default connection: $defaultConnection\n";
    echo "MySQL connection: mysql\n";
    echo "\n";
    
    if ($defaultConnection === 'sync_sqlite') {
        echo "⚠️ NOTICE:\n";
        echo "  Your AppServiceProvider is configured to use SQLite in Local mode.\n";
        echo "  This is by design for better performance.\n";
        echo "  MySQL is available but only used when explicitly requested or in Online mode.\n";
        echo "\n";
        echo "  To force MySQL as default:\n";
        echo "  1. Set APP_ENV=production in .env\n";
        echo "  2. Or set APP_URL to a non-localhost domain\n";
        echo "  3. Or modify AppServiceProvider->isLocalMode() logic\n";
    }
    
    echo "\n";
    echo "========================================\n";
    echo "   SUCCESS! MySQL is working!\n";
    echo "========================================\n\n";
    
} catch (Exception $e) {
    echo "✗ Laravel MySQL connection FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    echo "\n";
    echo "Troubleshooting:\n";
    echo "1. Check .env file\n";
    echo "2. Run: php artisan config:clear\n";
    echo "3. Run: php artisan cache:clear\n";
    echo "4. Check database credentials\n";
}

echo "\n";
