<?php
/**
 * فحص الفواتير التي لم تتم مزامنتها
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "========================================\n";
echo "   فحص الفواتير غير المزامنة\n";
echo "========================================\n\n";

try {
    $connection = config('database.default');
    
    // 1. جلب جميع IDs الفواتير من SQLite
    $localOrders = DB::connection('sync_sqlite')
        ->table('orders')
        ->orderBy('id')
        ->get(['id', 'created_at', 'total_amount', 'status']);
    
    echo "📊 الفواتير في SQLite (Local):\n";
    echo "   العدد الإجمالي: {$localOrders->count()}\n\n";
    
    // 2. جلب IDs الفواتير التي تمت مزامنتها من sync_queue
    $syncedOrderIds = [];
    if (Schema::connection($connection)->hasTable('sync_queue')) {
        $syncedOrders = DB::connection($connection)
            ->table('sync_queue')
            ->where('table_name', 'orders')
            ->where('status', 'synced')
            ->get(['record_id']);
        
        $syncedOrderIds = $syncedOrders->pluck('record_id')->toArray();
        
        echo "✅ الفواتير المزامنة:\n";
        echo "   العدد: " . count($syncedOrderIds) . "\n";
        if (count($syncedOrderIds) > 0) {
            echo "   IDs: " . implode(', ', array_slice($syncedOrderIds, 0, 20));
            if (count($syncedOrderIds) > 20) {
                echo " ... و " . (count($syncedOrderIds) - 20) . " أخرى";
            }
            echo "\n\n";
        }
    }
    
    // 3. جلب IDs الفواتير في انتظار المزامنة
    $pendingOrderIds = [];
    if (Schema::connection($connection)->hasTable('sync_queue')) {
        $pendingOrders = DB::connection($connection)
            ->table('sync_queue')
            ->where('table_name', 'orders')
            ->where('status', 'pending')
            ->get(['record_id', 'action', 'created_at']);
        
        $pendingOrderIds = $pendingOrders->pluck('record_id')->toArray();
        
        echo "⏳ الفواتير في انتظار المزامنة:\n";
        echo "   العدد: " . count($pendingOrderIds) . "\n";
        if (count($pendingOrderIds) > 0) {
            echo "   IDs: " . implode(', ', array_slice($pendingOrderIds, 0, 20));
            if (count($pendingOrderIds) > 20) {
                echo " ... و " . (count($pendingOrderIds) - 20) . " أخرى";
            }
            echo "\n\n";
            
            // عرض تفاصيل
            echo "   تفاصيل:\n";
            foreach ($pendingOrders->take(10) as $pending) {
                echo "   - ID: {$pending->record_id}, Action: {$pending->action}, Created: {$pending->created_at}\n";
            }
            echo "\n";
        }
    }
    
    // 4. تحديد الفواتير التي لم تتم مزامنتها
    $allLocalIds = $localOrders->pluck('id')->toArray();
    $syncedOrPendingIds = array_unique(array_merge($syncedOrderIds, $pendingOrderIds));
    $unsyncedIds = array_diff($allLocalIds, $syncedOrPendingIds);
    
    echo "❌ الفواتير التي لم تتم مزامنتها (ليست في sync_queue):\n";
    echo "   العدد: " . count($unsyncedIds) . "\n";
    
    if (count($unsyncedIds) > 0) {
        echo "   IDs: " . implode(', ', array_slice($unsyncedIds, 0, 20));
        if (count($unsyncedIds) > 20) {
            echo " ... و " . (count($unsyncedIds) - 20) . " أخرى";
        }
        echo "\n\n";
        
        // عرض تفاصيل الفواتير غير المزامنة
        echo "   تفاصيل الفواتير غير المزامنة:\n";
        $unsyncedOrders = $localOrders->whereIn('id', array_slice($unsyncedIds, 0, 10));
        foreach ($unsyncedOrders as $order) {
            echo "   - ID: {$order->id}, Amount: {$order->total_amount}, Status: {$order->status}, Created: {$order->created_at}\n";
        }
        echo "\n";
        
        // 5. التحقق من OrderObserver
        echo "🔍 تحليل المشكلة:\n";
        echo "   ⚠️  هذه الفواتير لم تُضاف إلى sync_queue\n";
        echo "   السبب المحتمل:\n";
        echo "   1. OrderObserver لم يتم استدعاؤه عند إنشاء الفواتير\n";
        echo "   2. الفواتير تم إنشاؤها قبل تفعيل OrderObserver\n";
        echo "   3. خطأ في OrderObserver::created()\n";
        echo "\n";
        
        // 6. الحل المقترح
        echo "💡 الحل:\n";
        echo "   1. إضافة هذه الفواتير يدوياً إلى sync_queue\n";
        echo "   2. أو إعادة إنشاء الفواتير (إذا أمكن)\n";
        echo "   3. أو مزامنة يدوية من SQLite إلى MySQL\n";
        echo "\n";
        
        // 7. إنشاء script لإضافة الفواتير إلى sync_queue
        echo "📝 هل تريد إنشاء script لإضافة هذه الفواتير إلى sync_queue؟ (y/n)\n";
        echo "   (يمكنك تشغيله لاحقاً)\n\n";
    } else {
        echo "   ✅ جميع الفواتير موجودة في sync_queue\n\n";
    }
    
    // 8. ملخص
    echo "📊 الملخص:\n";
    echo "   إجمالي الفواتير في Local: " . count($allLocalIds) . "\n";
    echo "   مزامنة: " . count($syncedOrderIds) . "\n";
    echo "   في الانتظار: " . count($pendingOrderIds) . "\n";
    echo "   غير مزامنة: " . count($unsyncedIds) . "\n";
    echo "\n";
    
    // 9. إنشاء script لإضافة الفواتير غير المزامنة إلى sync_queue
    if (count($unsyncedIds) > 0) {
        $scriptContent = "<?php\n";
        $scriptContent .= "require __DIR__.'/vendor/autoload.php';\n";
        $scriptContent .= "\$app = require_once __DIR__.'/bootstrap/app.php';\n";
        $scriptContent .= "\$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();\n\n";
        $scriptContent .= "use App\\Services\\SyncQueueService;\n";
        $scriptContent .= "use Illuminate\\Support\\Facades\\DB;\n\n";
        $scriptContent .= "\$syncQueueService = new SyncQueueService();\n";
        $scriptContent .= "\$unsyncedIds = [" . implode(', ', $unsyncedIds) . "];\n\n";
        $scriptContent .= "echo 'إضافة الفواتير إلى sync_queue...\\n';\n";
        $scriptContent .= "\$added = 0;\n";
        $scriptContent .= "foreach (\$unsyncedIds as \$orderId) {\n";
        $scriptContent .= "    try {\n";
        $scriptContent .= "        \$order = DB::connection('sync_sqlite')->table('orders')->where('id', \$orderId)->first();\n";
        $scriptContent .= "        if (\$order) {\n";
        $scriptContent .= "            \$orderData = (array) \$order;\n";
        $scriptContent .= "            if (\$syncQueueService->queueInsert('orders', \$orderId, \$orderData)) {\n";
        $scriptContent .= "                \$added++;\n";
        $scriptContent .= "                echo \"✅ تمت إضافة فاتورة ID: {\$orderId}\\n\";\n";
        $scriptContent .= "            }\n";
        $scriptContent .= "        }\n";
        $scriptContent .= "    } catch (\\Exception \$e) {\n";
        $scriptContent .= "        echo \"❌ فشل إضافة فاتورة ID: {\$orderId} - {\$e->getMessage()}\\n\";\n";
        $scriptContent .= "    }\n";
        $scriptContent .= "}\n\n";
        $scriptContent .= "echo \"\\n✅ تمت إضافة {\$added} فاتورة إلى sync_queue\\n\";\n";
        
        file_put_contents('add-unsynced-orders-to-queue.php', $scriptContent);
        echo "✅ تم إنشاء script: add-unsynced-orders-to-queue.php\n";
        echo "   يمكنك تشغيله لإضافة الفواتير غير المزامنة إلى sync_queue\n\n";
    }
    
    echo "========================================\n\n";
    
} catch (\Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}

