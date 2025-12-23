<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\SyncQueueService;
use Illuminate\Support\Facades\DB;

$syncQueueService = new SyncQueueService();
$unsyncedIds = [1, 2, 3, 4, 5, 7, 8, 9, 10, 11, 12, 13, 14];

echo 'إضافة الفواتير إلى sync_queue...\n';
$added = 0;
foreach ($unsyncedIds as $orderId) {
    try {
        $order = DB::connection('sync_sqlite')->table('orders')->where('id', $orderId)->first();
        if ($order) {
            $orderData = (array) $order;
            if ($syncQueueService->queueInsert('orders', $orderId, $orderData)) {
                $added++;
                echo "✅ تمت إضافة فاتورة ID: {$orderId}\n";
            }
        }
    } catch (\Exception $e) {
        echo "❌ فشل إضافة فاتورة ID: {$orderId} - {$e->getMessage()}\n";
    }
}

echo "\n✅ تمت إضافة {$added} فاتورة إلى sync_queue\n";
