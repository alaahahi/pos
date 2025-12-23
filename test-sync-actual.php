<?php

/**
 * اختبار المزامنة الفعلية مع MySQL
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DatabaseSyncService;
use App\Services\SyncQueueService;

echo "========================================\n";
echo "   اختبار المزامنة الفعلية مع MySQL\n";
echo "========================================\n\n";

// 1. التحقق من sync_queue
echo "1. التحقق من sync_queue...\n";
try {
    $pendingCount = DB::table('sync_queue')->where('status', 'pending')->count();
    echo "   عدد السجلات المعلقة: {$pendingCount}\n\n";
    
    if ($pendingCount === 0) {
        echo "❌ لا توجد سجلات معلقة للمزامنة!\n";
        exit(1);
    }
    
    // عرض بعض السجلات المعلقة
    $pendingRecords = DB::table('sync_queue')
        ->where('status', 'pending')
        ->limit(5)
        ->get();
    
    echo "   أمثلة على السجلات المعلقة:\n";
    foreach ($pendingRecords as $record) {
        echo "     - ID: {$record->id}, Table: {$record->table_name}, Record ID: {$record->record_id}, Action: {$record->action}\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "❌ خطأ في sync_queue: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. التحقق من MySQL
echo "2. التحقق من توفر MySQL...\n";
try {
    DB::connection('mysql')->getPdo();
    echo "   ✅ MySQL متاح\n\n";
} catch (\Exception $e) {
    echo "   ❌ MySQL غير متاح: " . $e->getMessage() . "\n";
    echo "   ⚠️  لا يمكن المزامنة بدون MySQL\n";
    exit(1);
}

// 3. اختبار المزامنة
echo "3. تشغيل المزامنة...\n";
try {
    $syncService = new DatabaseSyncService();
    $results = $syncService->syncPendingChanges(null, 10);
    
    echo "   المزامنة: {$results['synced']}\n";
    echo "   الفاشلة: {$results['failed']}\n";
    
    if (!empty($results['errors'])) {
        echo "   الأخطاء:\n";
        foreach ($results['errors'] as $error) {
            echo "     - {$error}\n";
        }
    }
    echo "\n";
    
    if ($results['synced'] > 0) {
        echo "✅ تمت مزامنة {$results['synced']} سجل(ات) بنجاح!\n\n";
    } else {
        echo "❌ لم يتم مزامنة أي سجلات\n\n";
    }
} catch (\Exception $e) {
    echo "❌ خطأ في المزامنة: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

// 4. التحقق من sync_queue بعد المزامنة
echo "4. التحقق من sync_queue بعد المزامنة...\n";
try {
    $pendingCount = DB::table('sync_queue')->where('status', 'pending')->count();
    $syncedCount = DB::table('sync_queue')->where('status', 'synced')->count();
    $failedCount = DB::table('sync_queue')->where('status', 'failed')->count();
    
    echo "   المعلقة: {$pendingCount}\n";
    echo "   المزامنة: {$syncedCount}\n";
    echo "   الفاشلة: {$failedCount}\n\n";
} catch (\Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
}

// 5. التحقق من البيانات على MySQL
echo "5. التحقق من البيانات على MySQL...\n";
try {
    // التحقق من orders
    $ordersCount = DB::connection('mysql')->table('orders')->count();
    echo "   عدد الطلبات على MySQL: {$ordersCount}\n";
    
    // عرض آخر 3 طلبات
    $lastOrders = DB::connection('mysql')->table('orders')
        ->orderBy('id', 'desc')
        ->limit(3)
        ->get(['id', 'customer_id', 'total_amount', 'created_at']);
    
    echo "   آخر 3 طلبات:\n";
    foreach ($lastOrders as $order) {
        echo "     - ID: {$order->id}, Customer: {$order->customer_id}, Total: {$order->total_amount}, Date: {$order->created_at}\n";
    }
    echo "\n";
    
    // التحقق من order_product
    $orderProductCount = DB::connection('mysql')->table('order_product')->count();
    echo "   عدد سجلات order_product على MySQL: {$orderProductCount}\n\n";
} catch (\Exception $e) {
    echo "❌ خطأ في التحقق من MySQL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "========================================\n";
echo "   انتهى الاختبار\n";
echo "========================================\n";

