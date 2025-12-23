<?php

/**
 * فحص حالة المزامنة بالتفصيل
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\SyncQueueService;
use App\Services\DatabaseSyncService;

echo "========================================\n";
echo "   فحص حالة المزامنة\n";
echo "========================================\n\n";

// 1. إحصائيات sync_queue
echo "1. إحصائيات sync_queue:\n";
try {
    $stats = DB::table('sync_queue')
        ->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = "synced" THEN 1 ELSE 0 END) as synced,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
        ')
        ->first();
    
    echo "   الإجمالي: {$stats->total}\n";
    echo "   المعلقة: {$stats->pending}\n";
    echo "   المزامنة: {$stats->synced}\n";
    echo "   الفاشلة: {$stats->failed}\n\n";
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

// 2. عرض السجلات المعلقة
echo "2. السجلات المعلقة:\n";
try {
    $pending = DB::table('sync_queue')
        ->where('status', 'pending')
        ->orderBy('created_at', 'asc')
        ->limit(10)
        ->get();
    
    if ($pending->isEmpty()) {
        echo "   لا توجد سجلات معلقة\n\n";
    } else {
        foreach ($pending as $record) {
            echo "   - ID: {$record->id}, Table: {$record->table_name}, Record ID: {$record->record_id}, Action: {$record->action}, Created: {$record->created_at}\n";
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

// 3. عرض السجلات الفاشلة
echo "3. السجلات الفاشلة:\n";
try {
    $failed = DB::table('sync_queue')
        ->where('status', 'failed')
        ->orderBy('updated_at', 'desc')
        ->limit(5)
        ->get();
    
    if ($failed->isEmpty()) {
        echo "   لا توجد سجلات فاشلة\n\n";
    } else {
        foreach ($failed as $record) {
            echo "   - ID: {$record->id}, Table: {$record->table_name}, Record ID: {$record->record_id}, Error: " . substr($record->error_message ?? 'N/A', 0, 100) . "\n";
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

// 4. التحقق من MySQL
echo "4. التحقق من MySQL:\n";
try {
    DB::connection('mysql')->getPdo();
    echo "   ✅ MySQL متاح\n\n";
} catch (\Exception $e) {
    echo "   ❌ MySQL غير متاح: " . $e->getMessage() . "\n";
    echo "   ⚠️  لا يمكن المزامنة بدون MySQL\n\n";
}

// 5. التحقق من البيانات في SQLite
echo "5. البيانات في SQLite:\n";
try {
    $ordersCount = DB::table('orders')->count();
    echo "   عدد الطلبات في SQLite: {$ordersCount}\n";
    
    $lastOrder = DB::table('orders')
        ->orderBy('id', 'desc')
        ->first();
    
    if ($lastOrder) {
        echo "   آخر طلب - ID: {$lastOrder->id}, Total: {$lastOrder->total_amount}, Date: {$lastOrder->created_at}\n";
    }
    
    $orderProductCount = DB::table('order_product')->count();
    echo "   عدد سجلات order_product في SQLite: {$orderProductCount}\n\n";
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

// 6. ملخص
echo "========================================\n";
echo "   الملخص:\n";
echo "========================================\n";

$syncService = new DatabaseSyncService();
$queueStats = $syncService->getQueueStats();

echo "السجلات المعلقة: {$queueStats['pending']}\n";
echo "السجلات المزامنة: {$queueStats['synced']}\n";
echo "السجلات الفاشلة: {$queueStats['failed']}\n\n";

if ($queueStats['pending'] > 0) {
    echo "⚠️  يوجد {$queueStats['pending']} سجل(ات) في انتظار المزامنة\n";
    echo "   - تأكد من أن MySQL متاح\n";
    echo "   - قم بتشغيل المزامنة من واجهة المستخدم\n";
} else {
    echo "✅ لا توجد سجلات معلقة\n";
}

echo "\n";

