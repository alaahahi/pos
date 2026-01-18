<?php
/**
 * Simple script to test MySQL connection directly
 * This bypasses Laravel to test raw database connectivity
 */

echo "\n========================================\n";
echo "   MySQL Direct Connection Test\n";
echo "========================================\n\n";

// Load environment variables from .env file
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
            $value = trim($value);
            // Remove quotes if present
            $value = trim($value, '"\'');
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
    echo "✓ .env file loaded\n\n";
} else {
    echo "✗ .env file not found!\n";
    exit(1);
}

// Get database credentials
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE') ?: '';
$username = getenv('DB_USERNAME') ?: '';
$password = getenv('DB_PASSWORD') ?: '';

echo "Database Configuration:\n";
echo "----------------------\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Database: $database\n";
echo "Username: $username\n";
echo "Password: " . (empty($password) ? '(empty)' : str_repeat('*', strlen($password))) . "\n\n";

// Test 1: Check if MySQL extension is loaded
echo "Test 1: PHP Extensions\n";
echo "----------------------\n";
if (extension_loaded('pdo_mysql')) {
    echo "✓ PDO MySQL extension is loaded\n";
} else {
    echo "✗ PDO MySQL extension is NOT loaded!\n";
    echo "  Please install php-mysql extension\n";
    exit(1);
}

if (extension_loaded('mysqli')) {
    echo "✓ MySQLi extension is loaded\n";
} else {
    echo "⚠ MySQLi extension is NOT loaded (not critical)\n";
}
echo "\n";

// Test 2: Try to connect using PDO
echo "Test 2: PDO Connection\n";
echo "----------------------\n";
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    echo "DSN: $dsn\n";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ];
    
    echo "Attempting to connect...\n";
    $startTime = microtime(true);
    $pdo = new PDO($dsn, $username, $password, $options);
    $endTime = microtime(true);
    $connectionTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "✓ PDO Connection successful! (took {$connectionTime}ms)\n";
    
    // Get MySQL version
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "✓ MySQL Version: $version\n";
    
    // Get server status
    $status = $pdo->query('SHOW STATUS LIKE "Uptime"')->fetch();
    $uptime = $status['Value'];
    $uptimeHours = floor($uptime / 3600);
    echo "✓ Server Uptime: {$uptimeHours} hours\n";
    
    // Count tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $tableCount = count($tables);
    echo "✓ Number of tables: $tableCount\n";
    
    if ($tableCount > 0) {
        echo "\nFirst 10 tables:\n";
        foreach (array_slice($tables, 0, 10) as $table) {
            echo "  - $table\n";
        }
    }
    
    echo "\n";
    
} catch (PDOException $e) {
    echo "✗ PDO Connection FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n\n";
    
    // Provide specific troubleshooting
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "This is an authentication error.\n";
        echo "Solutions:\n";
        echo "  1. Check DB_USERNAME and DB_PASSWORD in .env\n";
        echo "  2. Make sure the user has permission to access the database\n";
        echo "  3. Try running: GRANT ALL PRIVILEGES ON $database.* TO '$username'@'$host';\n";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "The database '$database' does not exist.\n";
        echo "Solutions:\n";
        echo "  1. Create the database: CREATE DATABASE $database;\n";
        echo "  2. Or check DB_DATABASE in .env\n";
    } elseif (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "MySQL server is not responding on $host:$port.\n";
        echo "Solutions:\n";
        echo "  1. Check if MySQL is running\n";
        echo "  2. Check if DB_HOST and DB_PORT are correct in .env\n";
        echo "  3. Make sure MySQL is listening on $host:$port\n";
    } elseif (strpos($e->getMessage(), 'timed out') !== false) {
        echo "Connection timed out.\n";
        echo "Solutions:\n";
        echo "  1. Check firewall settings\n";
        echo "  2. Check if MySQL is running\n";
        echo "  3. Verify DB_HOST is correct\n";
    }
    
    echo "\n";
}

// Test 3: Try MySQLi if available
if (extension_loaded('mysqli')) {
    echo "Test 3: MySQLi Connection\n";
    echo "----------------------\n";
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $mysqli = new mysqli($host, $username, $password, $database, $port);
        
        echo "✓ MySQLi Connection successful!\n";
        echo "✓ Host info: " . $mysqli->host_info . "\n";
        echo "✓ Protocol version: " . $mysqli->protocol_version . "\n";
        
        $mysqli->close();
        echo "\n";
    } catch (Exception $e) {
        echo "✗ MySQLi Connection FAILED!\n";
        echo "Error: " . $e->getMessage() . "\n\n";
    }
}

// Test 4: Check Laravel config cache
echo "Test 4: Laravel Configuration\n";
echo "----------------------\n";
if (file_exists(__DIR__ . '/bootstrap/cache/config.php')) {
    $cacheTime = filemtime(__DIR__ . '/bootstrap/cache/config.php');
    $cacheAge = time() - $cacheTime;
    echo "⚠ Laravel config is cached!\n";
    echo "  Cache file: bootstrap/cache/config.php\n";
    echo "  Cache age: " . round($cacheAge / 60) . " minutes\n";
    echo "  \n";
    echo "  This might be using OLD database settings!\n";
    echo "  \n";
    echo "  To fix this, run:\n";
    echo "    php artisan config:clear\n";
    echo "    php artisan config:cache\n";
    echo "\n";
} else {
    echo "✓ No config cache found (this is good)\n\n";
}

// Test 5: Check for common issues
echo "Test 5: Common Issues\n";
echo "----------------------\n";

// Check if XAMPP MySQL is running
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    exec('tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL', $output, $return);
    if (count($output) > 2) {
        echo "✓ MySQL process (mysqld.exe) is running\n";
    } else {
        echo "✗ MySQL process (mysqld.exe) is NOT running\n";
        echo "  Start MySQL from XAMPP Control Panel\n";
    }
}

// Check if port is in use
if (function_exists('fsockopen')) {
    $connection = @fsockopen($host, $port, $errno, $errstr, 2);
    if ($connection) {
        echo "✓ Port $port is open on $host\n";
        fclose($connection);
    } else {
        echo "✗ Cannot connect to port $port on $host\n";
        echo "  Error: $errstr ($errno)\n";
    }
}

echo "\n========================================\n";
echo "   Test Complete\n";
echo "========================================\n\n";

// Final recommendation
if (!isset($pdo)) {
    echo "\n";
    echo "RECOMMENDATION:\n";
    echo "---------------\n";
    echo "Since the database connection failed, try these steps:\n";
    echo "1. php artisan config:clear\n";
    echo "2. php artisan cache:clear\n";
    echo "3. Restart your web server\n";
    echo "4. Try again\n";
    echo "\n";
}
