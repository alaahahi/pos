<?php
/**
 * التحقق من تطابق الجداول بين MySQL و SQLite
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n========================================\n";
echo "   التحقق من تطابق الجداول\n";
echo "========================================\n\n";

try {
    // 1. جداول MySQL
    $mysqlTables = collect(DB::connection('mysql')->select('SHOW TABLES'))
        ->map(function($table) {
            return array_values((array)$table)[0];
        })
        ->filter(function($table) {
            return !in_array($table, ['migrations', 'password_resets', 'password_reset_tokens', 'personal_access_tokens', 'failed_jobs']);
        })
        ->sort()
        ->values();
    
    // 2. جداول SQLite
    $sqliteTables = collect(DB::connection('sync_sqlite')
        ->select("SELECT name FROM sqlite_master WHERE type='table'"))
        ->pluck('name')
        ->filter(function($table) {
            return !in_array($table, ['sqlite_sequence', 'migrations']);
        })
        ->sort()
        ->values();
    
    // 3. المقارنة
    echo "📊 الإحصائيات:\n";
    echo "══════════════\n";
    echo "MySQL:  " . $mysqlTables->count() . " جدول\n";
    echo "SQLite: " . $sqliteTables->count() . " جدول\n\n";
    
    // 4. الجداول الناقصة في SQLite
    $missingInSqlite = $mysqlTables->diff($sqliteTables);
    if ($missingInSqlite->count() > 0) {
        echo "⚠️ ناقص في SQLite (" . $missingInSqlite->count() . "):\n";
        foreach ($missingInSqlite as $table) {
            echo "   ✗ $table\n";
        }
        echo "\n";
    } else {
        echo "✅ SQLite: جميع الجداول موجودة!\n\n";
    }
    
    // 5. الجداول الزائدة في SQLite (لا ينبغي أن يكون هناك)
    $extraInSqlite = $sqliteTables->diff($mysqlTables);
    if ($extraInSqlite->count() > 0) {
        echo "⚠️ زائد في SQLite (" . $extraInSqlite->count() . "):\n";
        foreach ($extraInSqlite as $table) {
            echo "   + $table\n";
        }
        echo "\n";
    }
    
    // 6. الخلاصة
    echo "══════════════════════════════════\n";
    if ($missingInSqlite->count() === 0 && $extraInSqlite->count() === 0) {
        echo "🎉 تطابق كامل! MySQL و SQLite متطابقان!\n";
    } else {
        echo "⚠️ يوجد اختلاف - راجع أعلاه\n";
    }
    echo "══════════════════════════════════\n\n";
    
    // 7. قائمة كاملة (اختياري)
    $showFullList = false;
    if ($showFullList) {
        echo "\n📋 قائمة كاملة بالجداول:\n";
        echo "════════════════════════════\n";
        $allTables = $mysqlTables->merge($sqliteTables)->unique()->sort()->values();
        foreach ($allTables as $table) {
            $inMysql = $mysqlTables->contains($table) ? '✓' : '✗';
            $inSqlite = $sqliteTables->contains($table) ? '✓' : '✗';
            echo sprintf("%-30s MySQL[%s]  SQLite[%s]\n", $table, $inMysql, $inSqlite);
        }
        echo "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n\n";
    exit(1);
}
