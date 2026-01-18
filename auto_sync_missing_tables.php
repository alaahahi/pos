<?php
/**
 * مزامنة الجداول الناقصة تلقائياً - بدون تأكيد
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n========================================\n";
echo "   مزامنة تلقائية للجداول الناقصة\n";
echo "========================================\n\n";

try {
    // الجداول الناقصة (من الفحص السابق)
    $missingTables = ['jobs', 'sync_metadata', 'sync_queue'];
    
    echo "📋 الجداول التي سيتم مزامنتها:\n";
    foreach ($missingTables as $table) {
        echo "   - $table\n";
    }
    echo "\n";
    
    echo "🔄 بدء المزامنة...\n";
    echo "════════════════════════════════\n\n";
    
    $synced = [];
    $failed = [];
    
    foreach ($missingTables as $tableName) {
        echo "🔄 $tableName: ";
        
        try {
            // استخدام API endpoint للمزامنة
            $response = \Illuminate\Support\Facades\Http::timeout(30)->post('http://127.0.0.1:8000/api/sync-monitor/sync', [
                'direction' => 'down',
                'tables' => $tableName,
                'safe_mode' => false,
                'create_backup' => false
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['success']) && $data['success']) {
                    $result = $data['results'] ?? [];
                    $syncedCount = $result['total_synced'] ?? 0;
                    echo "✓ نجح ($syncedCount سجل)\n";
                    $synced[] = $tableName;
                } else {
                    $message = $data['message'] ?? 'خطأ غير معروف';
                    echo "✗ فشل: $message\n";
                    $failed[] = $tableName;
                }
            } else {
                echo "✗ فشل: HTTP " . $response->status() . "\n";
                $failed[] = $tableName;
            }
        } catch (\Exception $e) {
            echo "✗ خطأ: " . $e->getMessage() . "\n";
            $failed[] = $tableName;
        }
        
        usleep(500000); // نصف ثانية
    }
    
    echo "\n════════════════════════════════\n";
    echo "📊 الخلاصة:\n";
    echo "════════════════════════════════\n\n";
    
    echo "✓ نجح: " . count($synced) . "/" . count($missingTables) . " جدول\n";
    if (!empty($failed)) {
        echo "✗ فشل: " . count($failed) . " جدول\n";
        foreach ($failed as $table) {
            echo "  - $table\n";
        }
    }
    echo "\n";
    
    // التحقق النهائي
    $sqliteTablesAfter = collect(DB::connection('sync_sqlite')
        ->select("SELECT name FROM sqlite_master WHERE type='table'"))
        ->pluck('name')
        ->filter(function($table) {
            return !in_array($table, ['sqlite_sequence', 'migrations']);
        })
        ->count();
    
    echo "✅ SQLite الآن: $sqliteTablesAfter جدول\n\n";
    
    if (count($failed) === 0) {
        echo "🎉 تم! جميع الجداول الناقصة تمت مزامنتها!\n\n";
    } else {
        echo "⚠️ بعض الجداول لم تتم مزامنتها - راجع الأخطاء أعلاه\n\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ خطأ: " . $e->getMessage() . "\n\n";
    exit(1);
}
