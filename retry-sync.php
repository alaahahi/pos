<?php

/**
 * إعادة محاولة المزامنة للسجلات الفاشلة
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\SyncQueueService;
use App\Services\DatabaseSyncService;

echo "========================================\n";
echo "   إعادة محاولة المزامنة\n";
echo "========================================\n\n";

// 1. التحقق من السجلات الفاشلة
echo "1. التحقق من السجلات الفاشلة...\n";
try {
    $failedCount = DB::table('sync_queue')->where('status', 'failed')->count();
    echo "   عدد السجلات الفاشلة: {$failedCount}\n\n";
    
    if ($failedCount === 0) {
        echo "✅ لا توجد سجلات فاشلة\n";
        exit(0);
    }
} catch (\Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. التحقق من MySQL
echo "2. التحقق من MySQL...\n";
try {
    DB::connection('mysql')->getPdo();
    echo "   ✅ MySQL متاح\n\n";
} catch (\Exception $e) {
    echo "   ❌ MySQL غير متاح: " . $e->getMessage() . "\n";
    echo "   ⚠️  لا يمكن المزامنة بدون MySQL\n\n";
    
    echo "3. إعادة تعيين السجلات الفاشلة إلى pending...\n";
    try {
        $syncQueueService = new SyncQueueService();
        $resetCount = $syncQueueService->resetFailedToPending();
        echo "   ✅ تم إعادة تعيين {$resetCount} سجل(ات) إلى pending\n";
        echo "   سيتم محاولة المزامنة تلقائياً عندما يكون MySQL متاحاً\n\n";
    } catch (\Exception $e2) {
        echo "   ❌ خطأ: " . $e2->getMessage() . "\n";
    }
    exit(0);
}

// 3. إعادة تعيين السجلات الفاشلة إلى pending
echo "3. إعادة تعيين السجلات الفاشلة إلى pending...\n";
try {
    $syncQueueService = new SyncQueueService();
    $resetCount = $syncQueueService->resetFailedToPending();
    echo "   ✅ تم إعادة تعيين {$resetCount} سجل(ات) إلى pending\n\n";
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. تشغيل المزامنة
echo "4. تشغيل المزامنة...\n";
try {
    $syncService = new DatabaseSyncService();
    $results = $syncService->syncPendingChanges(null, 100);
    
    echo "   المزامنة: {$results['synced']}\n";
    echo "   الفاشلة: {$results['failed']}\n";
    
    if (!empty($results['errors'])) {
        echo "   الأخطاء:\n";
        foreach (array_slice($results['errors'], 0, 5) as $error) {
            echo "     - " . substr($error, 0, 100) . "\n";
        }
        if (count($results['errors']) > 5) {
            echo "     ... و " . (count($results['errors']) - 5) . " خطأ آخر\n";
        }
    }
    echo "\n";
    
    if ($results['synced'] > 0) {
        echo "✅ تمت مزامنة {$results['synced']} سجل(ات) بنجاح!\n";
    } else {
        echo "⚠️  لم يتم مزامنة أي سجلات\n";
    }
} catch (\Exception $e) {
    echo "❌ خطأ في المزامنة: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n========================================\n";

