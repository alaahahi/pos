<?php
/**
 * تنفيذ المزامنة مباشرة
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\DatabaseSyncService;
use Illuminate\Support\Facades\Log;

echo "========================================\n";
echo "   بدء المزامنة\n";
echo "========================================\n\n";

try {
    $syncService = new DatabaseSyncService();
    
    // الحصول على الإحصائيات قبل المزامنة
    $statsBefore = $syncService->getQueueStats();
    echo "📊 الإحصائيات قبل المزامنة:\n";
    echo "   Pending: {$statsBefore['pending']}\n";
    echo "   Synced: {$statsBefore['synced']}\n";
    echo "   Failed: {$statsBefore['failed']}\n\n";
    
    if ($statsBefore['pending'] === 0) {
        echo "✅ لا توجد سجلات في انتظار المزامنة\n\n";
        exit(0);
    }
    
    echo "🔄 بدء المزامنة...\n\n";
    
    // تنفيذ المزامنة
    $results = $syncService->syncPendingChanges(null, 100, 300);
    
    echo "📊 نتائج المزامنة:\n";
    echo "   ✅ Synced: {$results['synced']}\n";
    echo "   ❌ Failed: {$results['failed']}\n";
    echo "   ⏱️  Elapsed Time: " . ($results['elapsed_time'] ?? 0) . " seconds\n\n";
    
    if (!empty($results['errors'])) {
        echo "⚠️  الأخطاء:\n";
        foreach (array_slice($results['errors'], 0, 10) as $error) {
            echo "   - {$error}\n";
        }
        if (count($results['errors']) > 10) {
            echo "   ... و " . (count($results['errors']) - 10) . " خطأ آخر\n";
        }
        echo "\n";
    }
    
    // الحصول على الإحصائيات بعد المزامنة
    $statsAfter = $syncService->getQueueStats();
    echo "📊 الإحصائيات بعد المزامنة:\n";
    echo "   Pending: {$statsAfter['pending']}\n";
    echo "   Synced: {$statsAfter['synced']}\n";
    echo "   Failed: {$statsAfter['failed']}\n\n";
    
    if ($results['synced'] > 0 && $results['failed'] === 0) {
        echo "========================================\n";
        echo "✅ تمت المزامنة بنجاح!\n";
        echo "========================================\n\n";
    } else if ($results['synced'] > 0) {
        echo "========================================\n";
        echo "⚠️  تمت مزامنة بعض السجلات، لكن بعضها فشل\n";
        echo "========================================\n\n";
    } else {
        echo "========================================\n";
        echo "❌ فشلت المزامنة\n";
        echo "========================================\n\n";
    }
    
} catch (\Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}

