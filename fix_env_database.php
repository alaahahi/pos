<?php
/**
 * Automatically fix the DB_DATABASE value in .env
 */

echo "\n========================================\n";
echo "   Fix .env Database Name\n";
echo "========================================\n\n";

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    echo "✗ .env file not found!\n";
    exit(1);
}

echo "✓ Reading .env file...\n";
$content = file_get_contents($envFile);
$lines = explode("\n", $content);

$updated = false;
$newLines = [];

foreach ($lines as $line) {
    // Check if this is the DB_DATABASE line
    if (preg_match('/^DB_DATABASE\s*=\s*nissanintellijap_main/', $line)) {
        echo "✓ Found incorrect database name: nissanintellijap_main\n";
        $newLines[] = 'DB_DATABASE=aindubai_wedoo';
        echo "✓ Changed to: aindubai_wedoo\n";
        $updated = true;
    } else {
        $newLines[] = $line;
    }
}

if ($updated) {
    // Backup original file
    $backupFile = $envFile . '.backup.' . date('Y-m-d_H-i-s');
    copy($envFile, $backupFile);
    echo "✓ Backup created: " . basename($backupFile) . "\n";
    
    // Write updated content
    file_put_contents($envFile, implode("\n", $newLines));
    echo "✓ .env file updated successfully!\n\n";
    
    echo "Testing connection with new database name...\n";
    echo "============================================\n\n";
    
    // Test connection
    $host = '66.45.23.10';
    $port = '3306';
    $database = 'aindubai_wedoo';
    $username = 'aindubai_wedoo';
    $password = 'NoNN7MzKn6Ka';
    
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        echo "✓ Connection successful!\n";
        
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "✓ MySQL Version: $version\n";
        
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "✓ Number of tables: " . count($tables) . "\n";
        
        echo "\n";
        echo "========================================\n";
        echo "   SUCCESS! Database is now working!\n";
        echo "========================================\n\n";
        
        echo "Next steps:\n";
        echo "1. Run: php artisan config:clear\n";
        echo "2. Run: php artisan cache:clear\n";
        echo "3. Test your application\n";
        
    } catch (PDOException $e) {
        echo "✗ Connection failed: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "⚠️ Database name is already correct or not found\n";
    echo "\nCurrent DB_DATABASE value:\n";
    foreach ($lines as $line) {
        if (strpos($line, 'DB_DATABASE=') === 0) {
            echo "  $line\n";
        }
    }
}

echo "\n";
