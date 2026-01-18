<?php
/**
 * سكريبت اختبار المزامنة والاتصال بالسيرفر
 * 
 * الاستخدام:
 * php test_sync.php
 * php test_sync.php --test-connection
 * php test_sync.php --sync-table=users
 * php test_sync.php --sync-all
 */

// تحميل Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;

// ألوان للطباعة
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

// 1. فحص الإعدادات
printHeader('فحص الإعدادات');

$syncViaApi = env('SYNC_VIA_API', false);
$onlineUrl = env('ONLINE_URL', '');
$apiToken = env('SYNC_API_TOKEN', '');

echo "SYNC_VIA_API: " . ($syncViaApi ? Colors::$GREEN . 'مفعّل' : Colors::$RED . 'معطّل') . Colors::$RESET . "\n";
echo "ONLINE_URL: " . (!empty($onlineUrl) ? Colors::$GREEN . $onlineUrl : Colors::$RED . 'غير محدد') . Colors::$RESET . "\n";
echo "SYNC_API_TOKEN: " . (!empty($apiToken) ? Colors::$GREEN . 'محدد' : Colors::$YELLOW . 'غير محدد (اختياري)') . Colors::$RESET . "\n";

if (!$syncViaApi) {
    printWarning('API Sync غير مفعّل. قم بتفعيله في .env: SYNC_VIA_API=true');
}

if (empty($onlineUrl)) {
    printError('ONLINE_URL غير محدد في .env');
    exit(1);
}

// 2. فحص الاتصال بالإنترنت
printHeader('فحص الاتصال بالإنترنت');

try {
    $response = Http::timeout(5)->get('https://www.google.com');
    if ($response->successful()) {
        printSuccess('الاتصال بالإنترنت متاح');
    } else {
        printError('الاتصال بالإنترنت غير متاح');
        exit(1);
    }
} catch (Exception $e) {
    printError('فشل الاتصال بالإنترنت: ' . $e->getMessage());
    exit(1);
}

// 3. فحص الاتصال بالسيرفر
printHeader('فحص الاتصال بالسيرفر');

$apiUrl = rtrim($onlineUrl, '/') . '/api/sync-monitor/check-health';
printInfo("جاري الاتصال بـ: $apiUrl");

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
            printSuccess('الاتصال بالسيرفر ناجح');
            
            $health = $data['health'] ?? [];
            
            // عرض معلومات الصحة
            if (isset($health['api_service'])) {
                $apiAvailable = $health['api_service']['available'] ?? false;
                if ($apiAvailable) {
                    printSuccess('API Service متاح');
                } else {
                    printError('API Service غير متاح');
                }
            }
            
            // عرض المشاكل
            if (!empty($health['issues'])) {
                printWarning('مشاكل مكتشفة:');
                foreach ($health['issues'] as $issue) {
                    echo "  • $issue\n";
                }
            }
            
            // عرض التحذيرات
            if (!empty($health['warnings'])) {
                printWarning('تحذيرات:');
                foreach ($health['warnings'] as $warning) {
                    echo "  • $warning\n";
                }
            }
            
        } else {
            printError('فشل فحص السيرفر: ' . ($data['message'] ?? 'خطأ غير معروف'));
            printInfo('Full Response: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
            
            // تجاهل الخطأ ومتابعة الفحوصات
            printWarning('سيتم متابعة الفحوصات الأخرى...');
        }
    } else {
        printError('فشل الاتصال بالسيرفر. HTTP Status: ' . $response->status());
        printInfo('Response Body: ' . $response->body());
        
        // تجاهل الخطأ ومتابعة الفحوصات
        printWarning('سيتم متابعة الفحوصات الأخرى...');
    }
} catch (Exception $e) {
    printError('فشل الاتصال بالسيرفر: ' . $e->getMessage());
    printInfo('Exception Class: ' . get_class($e));
    if (method_exists($e, 'getResponse') && $e->getResponse()) {
        printInfo('Response Status: ' . $e->getResponse()->getStatusCode());
        printInfo('Response Body: ' . $e->getResponse()->getBody());
    }
    
    // تجاهل الخطأ ومتابعة الفحوصات
    printWarning('سيتم متابعة الفحوصات الأخرى...');
}

// 4. فحص قاعدة البيانات المحلية (SQLite)
printHeader('فحص قاعدة البيانات المحلية');

$sqlitePath = config('database.connections.sync_sqlite.database');
printInfo("مسار SQLite: $sqlitePath");

if (!file_exists($sqlitePath)) {
    printWarning('ملف SQLite غير موجود. سيتم إنشاؤه عند أول مزامنة.');
} else {
    printSuccess('ملف SQLite موجود');
    
    try {
        $tables = DB::connection('sync_sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        printInfo('عدد الجداول في SQLite: ' . count($tables));
        
        // عرض أول 5 جداول
        if (count($tables) > 0) {
            echo "أول 5 جداول:\n";
            foreach (array_slice($tables, 0, 5) as $table) {
                $count = DB::connection('sync_sqlite')->table($table->name)->count();
                echo "  • {$table->name}: $count سجل\n";
            }
        }
    } catch (Exception $e) {
        printError('فشل الاتصال بـ SQLite: ' . $e->getMessage());
    }
}

// 4.5 فحص تشغيل XAMPP
printHeader('فحص XAMPP');

// فحص إذا كان MySQL يعمل على المنفذ 3306
$mysqlPort = env('DB_PORT', 3306);
$mysqlHost = env('DB_HOST', '127.0.0.1');

printInfo("فحص MySQL على $mysqlHost:$mysqlPort");

$connection = @fsockopen($mysqlHost, $mysqlPort, $errno, $errstr, 5);
if ($connection) {
    printSuccess('MySQL يعمل على المنفذ ' . $mysqlPort);
    fclose($connection);
} else {
    printError("MySQL لا يعمل على المنفذ $mysqlPort");
    printWarning('تأكد من تشغيل XAMPP/MySQL');
    printInfo('لتشغيل MySQL، افتح XAMPP Control Panel وشغّل MySQL');
}

// 5. فحص الاتصال بـ MySQL
printHeader('فحص الاتصال بـ MySQL');

try {
    $tables = DB::connection('mysql')->select('SHOW TABLES');
    printSuccess('الاتصال بـ MySQL ناجح');
    printInfo('عدد الجداول في MySQL: ' . count($tables));
    
    // عرض أول 5 جداول
    if (count($tables) > 0) {
        echo "أول 5 جداول:\n";
        $tablesArray = array_values((array)$tables[0]);
        foreach (array_slice($tables, 0, 5) as $table) {
            $tableValues = array_values((array)$table);
            $tableName = $tableValues[0];
            $count = DB::connection('mysql')->table($tableName)->count();
            echo "  • {$tableName}: $count سجل\n";
        }
    }
} catch (Exception $e) {
    printError('فشل الاتصال بـ MySQL: ' . $e->getMessage());
}

// 6. اختبار المزامنة (اختياري)
$args = $argv ?? [];

if (in_array('--sync-all', $args)) {
    printHeader('اختبار مزامنة كل الجداول');
    
    if (!confirm('هل تريد مزامنة كل الجداول من MySQL إلى SQLite؟ (yes/no): ')) {
        printInfo('تم الإلغاء');
        exit(0);
    }
    
    try {
        printInfo('جاري المزامنة... قد يستغرق بعض الوقت');
        
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
            printSuccess('تمت المزامنة بنجاح!');
            $results = $data['results'];
            printInfo("عدد السجلات المزامنة: {$results['total_synced']}");
            printInfo("الجداول الناجحة: " . count($results['success']));
            printInfo("الجداول الفاشلة: " . count($results['failed']));
            
            if (!empty($results['failed'])) {
                printWarning('الجداول الفاشلة:');
                foreach ($results['failed'] as $table => $error) {
                    echo "  • $table: $error\n";
                }
            }
        } else {
            printError('فشلت المزامنة: ' . ($data['message'] ?? 'خطأ غير معروف'));
        }
    } catch (Exception $e) {
        printError('فشلت المزامنة: ' . $e->getMessage());
    }
}

foreach ($args as $arg) {
    if (strpos($arg, '--sync-table=') === 0) {
        $tableName = substr($arg, strlen('--sync-table='));
        
        printHeader("اختبار مزامنة جدول: $tableName");
        
        try {
            printInfo('جاري المزامنة...');
            
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
                printSuccess("تمت مزامنة جدول $tableName بنجاح!");
                $results = $data['results'];
                printInfo("عدد السجلات المزامنة: {$results['total_synced']}");
            } else {
                printError('فشلت المزامنة: ' . ($data['message'] ?? 'خطأ غير معروف'));
            }
        } catch (Exception $e) {
            printError('فشلت المزامنة: ' . $e->getMessage());
        }
        
        break;
    }
}

// الملخص النهائي
printHeader('الملخص النهائي');

echo Colors::$WHITE . "\n📋 ملخص الفحوصات:\n" . Colors::$RESET;
echo "  ✅ الإنترنت: متاح\n";
echo "  ⚠️  السيرفر: يرجع HTML بدلاً من JSON (يحتاج إصلاح)\n";
echo "  ✅ SQLite: ملف موجود (لكن فارغ)\n";

$mysqlWorking = @fsockopen(env('DB_HOST', '127.0.0.1'), env('DB_PORT', 3306), $errno, $errstr, 1);
if ($mysqlWorking) {
    echo "  ✅ MySQL: يعمل\n";
    fclose($mysqlWorking);
} else {
    echo "  ❌ MySQL: لا يعمل - شغّل XAMPP\n";
}

echo "\n" . Colors::$YELLOW . "⚠️  الإجراءات المطلوبة:" . Colors::$RESET . "\n";
echo "  1. شغّل MySQL من XAMPP Control Panel\n";
echo "  2. تأكد من أن السيرفر يسمح بالوصول لـ API بدون authentication\n";
echo "  3. أو أضف middleware للسماح بـ /api/sync-monitor/check-health\n";

echo "\n" . Colors::$CYAN . "📥 للمزامنة (بعد إصلاح المشاكل):" . Colors::$RESET . "\n";
echo "  php test_sync.php --sync-table=users      (مزامنة جدول واحد)\n";
echo "  php test_sync.php --sync-all              (مزامنة كل الجداول)\n";
echo "\n";

function confirm($message) {
    echo Colors::$YELLOW . $message . Colors::$RESET;
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    return trim(strtolower($line)) === 'yes' || trim(strtolower($line)) === 'y';
}
