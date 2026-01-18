<?php

/**
 * Test Laravel database connection
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n========================================\n";
echo "   Laravel Database Connection Test\n";
echo "========================================\n\n";

try {
    // Get database connection
    $db = DB::connection();
    
    echo "✓ Laravel database connection established\n";
    
    // Get connection name
    $connectionName = $db->getName();
    echo "✓ Connection name: $connectionName\n";
    
    // Get PDO
    $pdo = $db->getPdo();
    echo "✓ PDO connection: " . get_class($pdo) . "\n";
    
    // Get database name
    $databaseName = $db->getDatabaseName();
    echo "✓ Database name: $databaseName\n";
    
    // Test query
    $result = DB::select('SELECT DATABASE() as db, VERSION() as version, NOW() as now');
    echo "✓ Query executed successfully\n";
    echo "  Database: " . $result[0]->db . "\n";
    echo "  Version: " . $result[0]->version . "\n";
    echo "  Server time: " . $result[0]->now . "\n";
    
    echo "\n";
    
    // Count tables
    $tables = DB::select('SHOW TABLES');
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
    
    // Test common models
    echo "Testing common models:\n";
    echo "======================\n";
    
    try {
        $userCount = DB::table('users')->count();
        echo "✓ Users table: $userCount records\n";
    } catch (Exception $e) {
        echo "⚠️ Users table: " . $e->getMessage() . "\n";
    }
    
    try {
        $customerCount = DB::table('customers')->count();
        echo "✓ Customers table: $customerCount records\n";
    } catch (Exception $e) {
        echo "⚠️ Customers table: " . $e->getMessage() . "\n";
    }
    
    try {
        $orderCount = DB::table('orders')->count();
        echo "✓ Orders table: $orderCount records\n";
    } catch (Exception $e) {
        echo "⚠️ Orders table: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    echo "========================================\n";
    echo "   SUCCESS! Laravel is connected!\n";
    echo "========================================\n\n";
    
    echo "Your Laravel application can now:\n";
    echo "✓ Connect to the database\n";
    echo "✓ Execute queries\n";
    echo "✓ Access models\n";
    echo "\nYou can now run your application!\n";
    
} catch (Exception $e) {
    echo "✗ Laravel database connection FAILED!\n";
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
