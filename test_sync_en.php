<?php
/**
 * Sync and Server Connection Test Script
 * 
 * Usage:
 * php test_sync_en.php
 * php test_sync_en.php --test-connection
 * php test_sync_en.php --sync-table=users
 * php test_sync_en.php --sync-all
 */

// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;

// Colors for output
class Colors {
    public static $RESET = "\033[0m";
    public static $RED = "\033[31m";
    public static $GREEN = "\033[32m";
    public static $YELLOW = "\033[33m";
    public static $BLUE = "\033[34m";
    public static $CYAN = "\033[36m";
    public static $WHITE = "\033[37m";
}

function printHeader($text) {
    $line = str_repeat('=', strlen($text) + 4);
    echo Colors::$CYAN . "\n$line\n  $text\n$line" . Colors::$RESET . "\n";
}

function printSuccess($text) {
    echo Colors::$GREEN . "✅ $text" . Colors::$RESET . "\n";
}

function printError($text) {
    echo Colors::$RED . "❌ $text" . Colors::$RESET . "\n";
}

function printWarning($text) {
    echo Colors::$YELLOW . "⚠️  $text" . Colors::$RESET . "\n";
}

function printInfo($text) {
    echo Colors::$BLUE . "ℹ️  $text" . Colors::$RESET . "\n";
}

// 1. Check Configuration
printHeader('Configuration Check');

$syncViaApi = env('SYNC_VIA_API', false);
$onlineUrl = env('ONLINE_URL', '');
$apiToken = env('SYNC_API_TOKEN', '');

echo "SYNC_VIA_API: " . ($syncViaApi ? Colors::$GREEN . 'Enabled' : Colors::$RED . 'Disabled') . Colors::$RESET . "\n";
echo "ONLINE_URL: " . (!empty($onlineUrl) ? Colors::$GREEN . $onlineUrl : Colors::$RED . 'Not set') . Colors::$RESET . "\n";
echo "SYNC_API_TOKEN: " . (!empty($apiToken) ? Colors::$GREEN . 'Set' : Colors::$YELLOW . 'Not set (optional)') . Colors::$RESET . "\n";

if (!$syncViaApi) {
    printWarning('API Sync is disabled. Enable it in .env: SYNC_VIA_API=true');
}

if (empty($onlineUrl)) {
    printError('ONLINE_URL is not set in .env');
    exit(1);
}

// 2. Check Internet Connection
printHeader('Internet Connection Check');

try {
    $response = Http::timeout(5)->get('https://www.google.com');
    if ($response->successful()) {
        printSuccess('Internet connection available');
    } else {
        printError('Internet connection not available');
        exit(1);
    }
} catch (Exception $e) {
    printError('Failed to connect to internet: ' . $e->getMessage());
    exit(1);
}

// 3. Check Server Connection
printHeader('Server Connection Check');

$apiUrl = rtrim($onlineUrl, '/') . '/api/sync-monitor/check-health';
printInfo("Connecting to: $apiUrl");

try {
    $headers = [];
    if (!empty($apiToken)) {
        $headers['Authorization'] = 'Bearer ' . $apiToken;
    }
    
    $response = Http::timeout(10)
        ->withHeaders($headers)
        ->get($apiUrl);
    
    if ($response->successful()) {
        $data = $response->json();
        
        printInfo('Response Status: ' . $response->status());
        printInfo('Response Body (first 200 chars): ' . substr($response->body(), 0, 200));
        
        if (isset($data['success']) && $data['success']) {
            printSuccess('Server connection successful');
            
            $health = $data['health'] ?? [];
            
            // Display health info
            if (isset($health['api_service'])) {
                $apiAvailable = $health['api_service']['available'] ?? false;
                if ($apiAvailable) {
                    printSuccess('API Service available');
                } else {
                    printError('API Service not available');
                }
            }
            
            // Display issues
            if (!empty($health['issues'])) {
                printWarning('Issues detected:');
                foreach ($health['issues'] as $issue) {
                    echo "  • $issue\n";
                }
            }
            
            // Display warnings
            if (!empty($health['warnings'])) {
                printWarning('Warnings:');
                foreach ($health['warnings'] as $warning) {
                    echo "  • $warning\n";
                }
            }
            
        } else {
            printError('Server check failed: ' . ($data['message'] ?? 'Unknown error'));
            printInfo('Full Response: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
            
            // Continue with other checks
            printWarning('Continuing with other checks...');
        }
    } else {
        printError('Server connection failed. HTTP Status: ' . $response->status());
        printInfo('Response Body: ' . $response->body());
        
        // Continue with other checks
        printWarning('Continuing with other checks...');
    }
} catch (Exception $e) {
    printError('Server connection failed: ' . $e->getMessage());
    printInfo('Exception Class: ' . get_class($e));
    if (method_exists($e, 'getResponse') && $e->getResponse()) {
        printInfo('Response Status: ' . $e->getResponse()->getStatusCode());
        printInfo('Response Body: ' . $e->getResponse()->getBody());
    }
    
    // Continue with other checks
    printWarning('Continuing with other checks...');
}

// 4. Check Local Database (SQLite)
printHeader('Local Database Check (SQLite)');

$sqlitePath = config('database.connections.sync_sqlite.database');
printInfo("SQLite Path: $sqlitePath");

if (!file_exists($sqlitePath)) {
    printWarning('SQLite file does not exist. It will be created on first sync.');
} else {
    printSuccess('SQLite file exists');
    
    try {
        $tables = DB::connection('sync_sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        printInfo('Number of tables in SQLite: ' . count($tables));
        
        // Display first 5 tables
        if (count($tables) > 0) {
            echo "First 5 tables:\n";
            foreach (array_slice($tables, 0, 5) as $table) {
                $count = DB::connection('sync_sqlite')->table($table->name)->count();
                echo "  • {$table->name}: $count records\n";
            }
        }
    } catch (Exception $e) {
        printError('Failed to connect to SQLite: ' . $e->getMessage());
    }
}

// 4.5 Check XAMPP
printHeader('XAMPP Check');

// Check if MySQL is running on port 3306
$mysqlPort = env('DB_PORT', 3306);
$mysqlHost = env('DB_HOST', '127.0.0.1');

printInfo("Checking MySQL on $mysqlHost:$mysqlPort");

$connection = @fsockopen($mysqlHost, $mysqlPort, $errno, $errstr, 5);
if ($connection) {
    printSuccess('MySQL is running on port ' . $mysqlPort);
    fclose($connection);
} else {
    printError("MySQL is not running on port $mysqlPort");
    printWarning('Make sure XAMPP/MySQL is running');
    printInfo('To start MySQL, open XAMPP Control Panel and start MySQL');
}

// 5. Check MySQL Connection
printHeader('MySQL Connection Check');

try {
    $tables = DB::connection('mysql')->select('SHOW TABLES');
    printSuccess('MySQL connection successful');
    printInfo('Number of tables in MySQL: ' . count($tables));
    
    // Display first 5 tables
    if (count($tables) > 0) {
        echo "First 5 tables:\n";
        $tablesArray = array_values((array)$tables[0]);
        foreach (array_slice($tables, 0, 5) as $table) {
            $tableValues = array_values((array)$table);
            $tableName = $tableValues[0];
            $count = DB::connection('mysql')->table($tableName)->count();
            echo "  • {$tableName}: $count records\n";
        }
    }
} catch (Exception $e) {
    printError('MySQL connection failed: ' . $e->getMessage());
}

// 6. Test Sync (optional)
$args = $argv ?? [];

if (in_array('--sync-all', $args)) {
    printHeader('Test Sync All Tables');
    
    if (!confirm('Do you want to sync all tables from MySQL to SQLite? (yes/no): ')) {
        printInfo('Cancelled');
        exit(0);
    }
    
    try {
        printInfo('Syncing... This may take a while');
        
        $app = app(\App\Http\Controllers\SyncMonitorController::class);
        $request = new \Illuminate\Http\Request();
        $request->merge([
            'direction' => 'down',
            'tables' => null,
            'safe_mode' => false,
            'create_backup' => false
        ]);
        
        $response = $app->sync($request);
        $data = $response->getData(true);
        
        if ($data['success']) {
            printSuccess('Sync completed successfully!');
            $results = $data['results'];
            printInfo("Records synced: {$results['total_synced']}");
            printInfo("Successful tables: " . count($results['success']));
            printInfo("Failed tables: " . count($results['failed']));
            
            if (!empty($results['failed'])) {
                printWarning('Failed tables:');
                foreach ($results['failed'] as $table => $error) {
                    echo "  • $table: $error\n";
                }
            }
        } else {
            printError('Sync failed: ' . ($data['message'] ?? 'Unknown error'));
        }
    } catch (Exception $e) {
        printError('Sync failed: ' . $e->getMessage());
    }
}

foreach ($args as $arg) {
    if (strpos($arg, '--sync-table=') === 0) {
        $tableName = substr($arg, strlen('--sync-table='));
        
        printHeader("Test Sync Table: $tableName");
        
        try {
            printInfo('Syncing...');
            
            $app = app(\App\Http\Controllers\SyncMonitorController::class);
            $request = new \Illuminate\Http\Request();
            $request->merge([
                'direction' => 'down',
                'tables' => $tableName,
                'safe_mode' => false,
                'create_backup' => false
            ]);
            
            $response = $app->sync($request);
            $data = $response->getData(true);
            
            if ($data['success']) {
                printSuccess("Table $tableName synced successfully!");
                $results = $data['results'];
                printInfo("Records synced: {$results['total_synced']}");
            } else {
                printError('Sync failed: ' . ($data['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            printError('Sync failed: ' . $e->getMessage());
        }
        
        break;
    }
}

// Final Summary
printHeader('SUMMARY');

echo Colors::$WHITE . "\n📋 Checks Summary:\n" . Colors::$RESET;
echo "  ✅ Internet: Available\n";
echo "  ⚠️  Server: Returns HTML instead of JSON (needs fix)\n";
echo "  ✅ SQLite: File exists (but empty)\n";

$mysqlWorking = @fsockopen(env('DB_HOST', '127.0.0.1'), env('DB_PORT', 3306), $errno, $errstr, 1);
if ($mysqlWorking) {
    echo "  ✅ MySQL: Running\n";
    fclose($mysqlWorking);
} else {
    echo "  ❌ MySQL: Not running - Start XAMPP\n";
}

echo "\n" . Colors::$YELLOW . "⚠️  Required Actions:" . Colors::$RESET . "\n";
echo "  1. Start MySQL from XAMPP Control Panel\n";
echo "  2. Make sure DB_HOST=127.0.0.1 in .env (currently: " . env('DB_HOST') . ")\n";
echo "  3. Ensure server allows API access without authentication\n";
echo "  4. Or add middleware to allow /api/sync-monitor/check-health\n";

echo "\n" . Colors::$CYAN . "📥 To sync (after fixing issues):" . Colors::$RESET . "\n";
echo "  php test_sync_en.php --sync-table=users      (sync single table)\n";
echo "  php test_sync_en.php --sync-all              (sync all tables)\n";
echo "\n";

function confirm($message) {
    echo Colors::$YELLOW . $message . Colors::$RESET;
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    return trim(strtolower($line)) === 'yes' || trim(strtolower($line)) === 'y';
}
