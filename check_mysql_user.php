<?php
/**
 * Check MySQL user privileges
 */

echo "\n========================================\n";
echo "   MySQL User Privileges Check\n";
echo "========================================\n\n";

// Load .env
if (file_exists(__DIR__ . '/.env')) {
    $envContent = file_get_contents(__DIR__ . '/.env');
    $lines = explode("\n", $envContent);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            putenv("$key=$value");
        }
    }
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$username = getenv('DB_USERNAME') ?: '';
$password = getenv('DB_PASSWORD') ?: '';

echo "Checking privileges for user: $username\n";
echo "On server: $host:$port\n\n";

try {
    // Connect to MySQL server without specifying database
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "✓ Successfully connected to MySQL server\n\n";
    
    // Show grants for this user
    echo "User Privileges:\n";
    echo "================\n";
    try {
        $grants = $pdo->query("SHOW GRANTS")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($grants as $grant) {
            echo "$grant\n";
        }
    } catch (Exception $e) {
        echo "✗ Cannot show grants: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Try to list databases
    echo "Accessible Databases:\n";
    echo "=====================\n";
    try {
        $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($databases as $db) {
            echo "  - $db\n";
        }
    } catch (Exception $e) {
        echo "✗ Cannot list databases: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Check specific database access
    $targetDb = getenv('DB_DATABASE');
    if ($targetDb) {
        echo "Checking access to '$targetDb':\n";
        echo "================================\n";
        try {
            $pdo->exec("USE `$targetDb`");
            echo "✓ Can access database '$targetDb'\n";
            
            // Try to list tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "✓ Number of tables: " . count($tables) . "\n";
            
        } catch (Exception $e) {
            echo "✗ Cannot access database '$targetDb'\n";
            echo "Error: " . $e->getMessage() . "\n";
            echo "\n";
            echo "SOLUTION:\n";
            echo "Ask your database administrator to run:\n";
            echo "  GRANT ALL PRIVILEGES ON `$targetDb`.* TO '$username'@'%';\n";
            echo "  FLUSH PRIVILEGES;\n";
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
