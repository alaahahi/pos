<?php
/**
 * عرض الفرق التفصيلي بين جداول MySQL و SQLite
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n═══════════════════════════════════════════════════════════\n";
echo "        مقارنة تفصيلية: MySQL ↔ SQLite المحلي\n";
echo "═══════════════════════════════════════════════════════════\n\n";

try {
    // 1. جداول MySQL
    echo "1️⃣ جلب جداول MySQL...\n";
    $mysqlTables = collect(DB::connection('mysql')->select('SHOW TABLES'))
        ->map(function($table) {
            return array_values((array)$table)[0];
        })
        ->sort()
        ->values();
    
    echo "   ✓ MySQL: " . $mysqlTables->count() . " جدول\n\n";
    
    // 2. جداول SQLite المحلي
    echo "2️⃣ جلب جداول SQLite المحلي...\n";
    
    $sqlitePath = config('database.connections.sync_sqlite.database');
    if (!file_exists($sqlitePath)) {
        echo "   ⚠️ ملف SQLite غير موجود: $sqlitePath\n";
        echo "   💡 شغّل: php artisan migrate --database=sync_sqlite\n\n";
        exit(1);
    }
    
    $sqliteTables = collect(DB::connection('sync_sqlite')
        ->select("SELECT name FROM sqlite_master WHERE type='table'"))
        ->pluck('name')
        ->sort()
        ->values();
    
    echo "   ✓ SQLite: " . $sqliteTables->count() . " جدول\n\n";
    
    // 3. الجداول المستثناة (نتجاهلها)
    $excludedTables = [
        'migrations',
        'password_resets',
        'password_reset_tokens',
        'personal_access_tokens',
        'failed_jobs',
        'sqlite_sequence',
        'sqlite_master'
    ];
    
    $mysqlFiltered = $mysqlTables->diff($excludedTables);
    $sqliteFiltered = $sqliteTables->diff($excludedTables);
    
    echo "3️⃣ بعد استثناء الجداول الخاصة:\n";
    echo "   MySQL:  " . $mysqlFiltered->count() . " جدول\n";
    echo "   SQLite: " . $sqliteFiltered->count() . " جدول\n\n";
    
    // 4. الجداول الناقصة في SQLite
    $missingInSqlite = $mysqlFiltered->diff($sqliteFiltered);
    
    echo "═══════════════════════════════════════════════════════════\n";
    echo "📋 الجداول الناقصة في SQLite المحلي:\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    if ($missingInSqlite->count() > 0) {
        echo "⚠️ عدد الجداول الناقصة: " . $missingInSqlite->count() . "\n\n";
        
        $counter = 1;
        foreach ($missingInSqlite as $table) {
            // فحص عدد السجلات في MySQL
            try {
                $count = DB::connection('mysql')->table($table)->count();
                echo sprintf("%2d. %-30s → %s سجل\n", 
                    $counter++, 
                    $table, 
                    number_format($count)
                );
            } catch (\Exception $e) {
                echo sprintf("%2d. %-30s → (خطأ في العد)\n", 
                    $counter++, 
                    $table
                );
            }
        }
        echo "\n";
    } else {
        echo "✅ لا توجد جداول ناقصة!\n";
        echo "   جميع جداول MySQL موجودة في SQLite المحلي\n\n";
    }
    
    // 5. الجداول الزائدة في SQLite (لا ينبغي وجودها)
    $extraInSqlite = $sqliteFiltered->diff($mysqlFiltered);
    
    if ($extraInSqlite->count() > 0) {
        echo "═══════════════════════════════════════════════════════════\n";
        echo "⚠️ جداول زائدة في SQLite (غير موجودة في MySQL):\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        
        $counter = 1;
        foreach ($extraInSqlite as $table) {
            try {
                $count = DB::connection('sync_sqlite')->table($table)->count();
                echo sprintf("%2d. %-30s → %s سجل\n", 
                    $counter++, 
                    $table, 
                    number_format($count)
                );
            } catch (\Exception $e) {
                echo sprintf("%2d. %-30s → (خطأ في العد)\n", 
                    $counter++, 
                    $table
                );
            }
        }
        echo "\n";
    }
    
    // 6. مقارنة عدد السجلات للجداول المشتركة
    echo "═══════════════════════════════════════════════════════════\n";
    echo "📊 مقارنة عدد السجلات (الجداول المشتركة):\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    $commonTables = $mysqlFiltered->intersect($sqliteFiltered)->take(10); // أول 10 جداول
    
    if ($commonTables->count() > 0) {
        echo sprintf("%-30s %15s %15s %10s\n", 
            "الجدول", 
            "MySQL", 
            "SQLite", 
            "الفرق"
        );
        echo str_repeat("─", 75) . "\n";
        
        foreach ($commonTables as $table) {
            try {
                $mysqlCount = DB::connection('mysql')->table($table)->count();
                $sqliteCount = DB::connection('sync_sqlite')->table($table)->count();
                $diff = $mysqlCount - $sqliteCount;
                $diffIcon = $diff == 0 ? '✓' : ($diff > 0 ? '↓' : '↑');
                
                echo sprintf("%-30s %15s %15s %9s %s\n", 
                    $table,
                    number_format($mysqlCount),
                    number_format($sqliteCount),
                    number_format(abs($diff)),
                    $diffIcon
                );
            } catch (\Exception $e) {
                echo sprintf("%-30s %15s %15s %10s\n", 
                    $table,
                    "خطأ",
                    "خطأ",
                    "-"
                );
            }
        }
        
        echo "\n";
        echo "💡 الرموز:\n";
        echo "   ✓ = متطابق\n";
        echo "   ↓ = MySQL أكثر (يحتاج مزامنة من السيرفر)\n";
        echo "   ↑ = SQLite أكثر (يحتاج مزامنة للسيرفر)\n\n";
    }
    
    // 7. الخلاصة والإجراءات المقترحة
    echo "═══════════════════════════════════════════════════════════\n";
    echo "📝 الخلاصة:\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    echo "MySQL:  " . $mysqlFiltered->count() . " جدول\n";
    echo "SQLite: " . $sqliteFiltered->count() . " جدول\n";
    echo "الناقص: " . $missingInSqlite->count() . " جدول\n";
    if ($extraInSqlite->count() > 0) {
        echo "الزائد:  " . $extraInSqlite->count() . " جدول\n";
    }
    echo "\n";
    
    if ($missingInSqlite->count() > 0) {
        echo "═══════════════════════════════════════════════════════════\n";
        echo "🔧 الإجراءات المقترحة:\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        
        echo "لمزامنة الجداول الناقصة:\n\n";
        
        echo "الطريقة 1️⃣: مزامنة تلقائية (يُنشئ الجداول الناقصة):\n";
        echo "──────────────────────────────────────────────\n";
        echo "php auto_sync_missing_tables.php\n\n";
        
        echo "الطريقة 2️⃣: من الواجهة:\n";
        echo "──────────────────────────────────────────────\n";
        echo "1. افتح: http://127.0.0.1:8000/sync-monitor\n";
        echo "2. اضغط: 📥 مزامنة من السيرفر\n";
        echo "3. اختر جدول أو كل الجداول\n\n";
        
        echo "الطريقة 3️⃣: مزامنة جدول واحد:\n";
        echo "──────────────────────────────────────────────\n";
        echo "curl -X POST http://127.0.0.1:8000/api/sync-monitor/sync \\\n";
        echo "  -H 'Content-Type: application/json' \\\n";
        echo "  -d '{\"direction\":\"down\",\"tables\":\"" . $missingInSqlite->first() . "\"}'\n\n";
    } else {
        echo "🎉 جميع الجداول متطابقة!\n\n";
    }
    
    echo "═══════════════════════════════════════════════════════════\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ خطأ: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
