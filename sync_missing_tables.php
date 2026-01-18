<?php
/**
 * مزامنة الجداول الناقصة من MySQL إلى SQLite
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

echo "\n========================================\n";
echo "   مزامنة الجداول الناقصة\n";
echo "========================================\n\n";

try {
    // 1. جلب جداول MySQL
    echo "1️⃣ جلب جداول MySQL...\n";
    $mysqlTables = collect(DB::connection('mysql')->select('SHOW TABLES'))
        ->map(function($table) {
            $tableName = array_values((array)$table)[0];
            return $tableName;
        })
        ->filter(function($table) {
            // استثناء الجداول الخاصة بـ Laravel
            return !in_array($table, ['migrations', 'password_resets', 'password_reset_tokens', 'personal_access_tokens', 'failed_jobs']);
        })
        ->sort()
        ->values()
        ->toArray();
    
    echo "   ✓ MySQL: " . count($mysqlTables) . " جدول\n\n";
    
    // 2. جلب جداول SQLite
    echo "2️⃣ جلب جداول SQLite...\n";
    $sqlitePath = config('database.connections.sync_sqlite.database');
    
    if (!file_exists($sqlitePath)) {
        echo "   ⚠️ ملف SQLite غير موجود: $sqlitePath\n";
        echo "   جاري الإنشاء...\n";
        touch($sqlitePath);
        chmod($sqlitePath, 0666);
        echo "   ✓ تم إنشاء ملف SQLite\n\n";
    }
    
    $sqliteTables = collect(DB::connection('sync_sqlite')
        ->select("SELECT name FROM sqlite_master WHERE type='table'"))
        ->pluck('name')
        ->filter(function($table) {
            // استثناء جداول SQLite الخاصة
            return !in_array($table, ['sqlite_sequence', 'migrations']);
        })
        ->sort()
        ->values()
        ->toArray();
    
    echo "   ✓ SQLite: " . count($sqliteTables) . " جدول\n\n";
    
    // 3. إيجاد الجداول الناقصة
    echo "3️⃣ الجداول الناقصة في SQLite:\n";
    $missingTables = array_diff($mysqlTables, $sqliteTables);
    
    if (empty($missingTables)) {
        echo "   ✓ لا توجد جداول ناقصة - كل الجداول موجودة!\n\n";
        exit(0);
    }
    
    echo "   ⚠️ عدد الجداول الناقصة: " . count($missingTables) . "\n";
    echo "   ────────────────────────────────\n";
    foreach ($missingTables as $table) {
        echo "   - $table\n";
    }
    echo "\n";
    
    // 4. تأكيد المزامنة
    echo "4️⃣ هل تريد مزامنة هذه الجداول؟ (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($line) !== 'y' && strtolower($line) !== 'yes') {
        echo "   ✗ تم الإلغاء\n\n";
        exit(0);
    }
    
    echo "\n5️⃣ بدء المزامنة...\n";
    echo "   ════════════════════════════════\n\n";
    
    $synced = [];
    $failed = [];
    
    foreach ($missingTables as $tableName) {
        echo "   🔄 مزامنة: $tableName\n";
        
        try {
            // استخدام API endpoint للمزامنة
            $response = \Illuminate\Support\Facades\Http::post('http://127.0.0.1:8000/api/sync-monitor/sync', [
                'direction' => 'down',
                'tables' => $tableName,
                'safe_mode' => false,
                'create_backup' => false
            ]);
            
            if ($response->successful() && $response->json('success')) {
                $result = $response->json('results');
                $syncedCount = $result['total_synced'] ?? 0;
                echo "      ✓ نجح: $syncedCount سجل\n";
                $synced[] = $tableName;
            } else {
                $message = $response->json('message') ?? 'خطأ غير معروف';
                echo "      ✗ فشل: $message\n";
                $failed[] = $tableName;
            }
        } catch (\Exception $e) {
            echo "      ✗ خطأ: " . $e->getMessage() . "\n";
            $failed[] = $tableName;
        }
        
        echo "\n";
        usleep(500000); // نصف ثانية بين كل جدول
    }
    
    // 6. الخلاصة
    echo "════════════════════════════════\n";
    echo "📊 الخلاصة:\n";
    echo "════════════════════════════════\n\n";
    
    echo "✓ نجح: " . count($synced) . " جدول\n";
    if (!empty($synced)) {
        foreach ($synced as $table) {
            echo "  - $table\n";
        }
    }
    echo "\n";
    
    if (!empty($failed)) {
        echo "✗ فشل: " . count($failed) . " جدول\n";
        foreach ($failed as $table) {
            echo "  - $table\n";
        }
        echo "\n";
    }
    
    // 7. التحقق النهائي
    echo "7️⃣ التحقق النهائي...\n";
    $sqliteTablesAfter = collect(DB::connection('sync_sqlite')
        ->select("SELECT name FROM sqlite_master WHERE type='table'"))
        ->pluck('name')
        ->filter(function($table) {
            return !in_array($table, ['sqlite_sequence', 'migrations']);
        })
        ->sort()
        ->values()
        ->toArray();
    
    echo "   MySQL: " . count($mysqlTables) . " جدول\n";
    echo "   SQLite: " . count($sqliteTablesAfter) . " جدول\n\n";
    
    if (count($mysqlTables) === count($sqliteTablesAfter)) {
        echo "🎉 تم! جميع الجداول متطابقة الآن!\n\n";
    } else {
        $remaining = count($mysqlTables) - count($sqliteTablesAfter);
        echo "⚠️ لا يزال هناك $remaining جدول ناقص\n\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ خطأ: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    exit(1);
}

echo "════════════════════════════════\n";
echo "✅ انتهى\n";
echo "════════════════════════════════\n\n";
