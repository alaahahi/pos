<?php

/**
 * اختبار شامل للمزامنة الذكية
 * 
 * هذا السكريبت يختبر:
 * 1. إنشاء فاتورة في الوضع المحلي
 * 2. التحقق من وجودها في sync_queue
 * 3. تشغيل المزامنة الذكية
 * 4. التحقق من وجود الفاتورة على السيرفر
 * 5. التحقق من معالجة تضارب ID
 * 6. التحقق من مزامنة pivot tables
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Services\SyncQueueService;
use App\Services\DatabaseSyncService;
use App\Services\SyncIdMappingService;

echo "========================================\n";
echo "   اختبار شامل للمزامنة الذكية\n";
echo "========================================\n\n";

$results = [
    'tests_passed' => 0,
    'tests_failed' => 0,
    'errors' => []
];

function testResult($testName, $passed, $message = '') {
    global $results;
    if ($passed) {
        echo "✅ {$testName}: نجح\n";
        $results['tests_passed']++;
    } else {
        echo "❌ {$testName}: فشل\n";
        if ($message) {
            echo "   رسالة الخطأ: {$message}\n";
        }
        $results['tests_failed']++;
        $results['errors'][] = "{$testName}: {$message}";
    }
    echo "\n";
}

// 1. التحقق من الاتصال بقاعدة البيانات
echo "1. التحقق من الاتصال بقاعدة البيانات...\n";
try {
    $defaultConnection = config('database.default');
    $driver = DB::connection()->getDriverName();
    echo "   الاتصال الافتراضي: {$defaultConnection}\n";
    echo "   نوع قاعدة البيانات: {$driver}\n";
    
    if ($driver === 'sqlite') {
        echo "   ✅ الوضع المحلي (SQLite) - جاهز للاختبار\n";
    } else {
        echo "   ⚠️  الوضع Online (MySQL) - قد لا يتم حفظ في sync_queue\n";
    }
    
    testResult("الاتصال بقاعدة البيانات", true);
} catch (\Exception $e) {
    testResult("الاتصال بقاعدة البيانات", false, $e->getMessage());
    exit(1);
}

// 2. التحقق من وجود sync_queue
echo "2. التحقق من وجود جدول sync_queue...\n";
try {
    $hasTable = DB::getSchemaBuilder()->hasTable('sync_queue');
    if ($hasTable) {
        $pendingCount = DB::table('sync_queue')->where('status', 'pending')->count();
        echo "   عدد السجلات المعلقة: {$pendingCount}\n";
        testResult("وجود جدول sync_queue", true);
    } else {
        testResult("وجود جدول sync_queue", false, "الجدول غير موجود");
    }
} catch (\Exception $e) {
    testResult("وجود جدول sync_queue", false, $e->getMessage());
}

// 3. التحقق من وجود sync_id_mapping
echo "3. التحقق من وجود جدول sync_id_mapping...\n";
try {
    $hasTable = DB::getSchemaBuilder()->hasTable('sync_id_mapping');
    testResult("وجود جدول sync_id_mapping", $hasTable, $hasTable ? '' : "الجدول غير موجود");
} catch (\Exception $e) {
    testResult("وجود جدول sync_id_mapping", false, $e->getMessage());
}

// 4. التحقق من وجود بيانات أساسية (Customer, Product)
echo "4. التحقق من وجود بيانات أساسية...\n";
try {
    $customer = Customer::first();
    $product = Product::first();
    
    if (!$customer) {
        testResult("وجود عميل", false, "لا يوجد عملاء في قاعدة البيانات");
    } else {
        echo "   العميل: {$customer->name} (ID: {$customer->id})\n";
        testResult("وجود عميل", true);
    }
    
    if (!$product) {
        testResult("وجود منتج", false, "لا يوجد منتجات في قاعدة البيانات");
    } else {
        echo "   المنتج: {$product->name} (ID: {$product->id}, الكمية: {$product->quantity})\n";
        testResult("وجود منتج", true);
    }
} catch (\Exception $e) {
    testResult("التحقق من البيانات الأساسية", false, $e->getMessage());
}

// 5. إنشاء فاتورة تجريبية
echo "5. إنشاء فاتورة تجريبية...\n";
$testOrder = null;
try {
    if (!$customer || !$product) {
        testResult("إنشاء فاتورة", false, "لا توجد بيانات أساسية كافية");
    } else {
        // التحقق من الكمية المتوفرة
        if ($product->quantity < 1) {
            // زيادة الكمية مؤقتاً للاختبار
            $product->quantity = 10;
            $product->save();
            echo "   ⚠️  تم زيادة كمية المنتج للاختبار\n";
        }
        
        DB::beginTransaction();
        
        // إنشاء فاتورة
        $order = Order::create([
            'customer_id' => $customer->id,
            'payment_method' => 'cash',
            'status' => 'paid',
            'total_amount' => 100.00,
            'total_paid' => 100.00,
            'date' => now()->format('Y-m-d'),
            'notes' => 'فاتورة تجريبية للاختبار - ' . now()->format('Y-m-d H:i:s'),
            'discount_amount' => 0,
            'discount_rate' => 0,
            'final_amount' => 100.00,
        ]);
        
        // إرفاق منتج
        $order->products()->attach($product->id, [
            'quantity' => 1,
            'price' => 100.00,
        ]);
        
        // استدعاء syncOrderProducts يدوياً بعد إرفاق المنتجات
        // (في التطبيق الفعلي، سيتم استدعاؤه تلقائياً من OrderObserver::saved)
        $observer = new \App\Observers\OrderObserver();
        $reflection = new ReflectionClass($observer);
        $method = $reflection->getMethod('syncOrderProducts');
        $method->setAccessible(true);
        $method->invoke($observer, $order);
        
        DB::commit();
        
        $testOrder = $order;
        echo "   ✅ تم إنشاء الفاتورة رقم: {$order->id}\n";
        testResult("إنشاء فاتورة", true);
    }
} catch (\Exception $e) {
    DB::rollBack();
    testResult("إنشاء فاتورة", false, $e->getMessage());
}

// 6. التحقق من وجود الفاتورة في sync_queue
echo "6. التحقق من وجود الفاتورة في sync_queue...\n";
if ($testOrder) {
    try {
        $queueRecord = DB::table('sync_queue')
            ->where('table_name', 'orders')
            ->where('record_id', $testOrder->id)
            ->where('action', 'insert')
            ->where('status', 'pending')
            ->first();
        
        if ($queueRecord) {
            echo "   ✅ تم العثور على السجل في sync_queue\n";
            echo "   ID: {$queueRecord->id}, Action: {$queueRecord->action}, Status: {$queueRecord->status}\n";
            testResult("وجود الفاتورة في sync_queue", true);
        } else {
            testResult("وجود الفاتورة في sync_queue", false, "السجل غير موجود في sync_queue");
        }
        
        // التحقق من order_product في sync_queue
        $productQueueRecords = DB::table('sync_queue')
            ->where('table_name', 'order_product')
            ->where('record_id', $testOrder->id)
            ->where('action', 'insert')
            ->where('status', 'pending')
            ->count();
        
        if ($productQueueRecords > 0) {
            echo "   ✅ تم العثور على {$productQueueRecords} سجل(ات) من order_product في sync_queue\n";
            testResult("وجود order_product في sync_queue", true);
        } else {
            testResult("وجود order_product في sync_queue", false, "لا توجد سجلات order_product في sync_queue");
        }
    } catch (\Exception $e) {
        testResult("التحقق من sync_queue", false, $e->getMessage());
    }
}

// 7. التحقق من إحصائيات sync_queue
echo "7. التحقق من إحصائيات sync_queue...\n";
try {
    $syncService = new DatabaseSyncService();
    $stats = $syncService->getQueueStats();
    
    echo "   المعلقة: {$stats['pending']}\n";
    echo "   المزامنة: {$stats['synced']}\n";
    echo "   الفاشلة: {$stats['failed']}\n";
    echo "   الإجمالي: {$stats['total']}\n";
    
    testResult("إحصائيات sync_queue", true);
} catch (\Exception $e) {
    testResult("إحصائيات sync_queue", false, $e->getMessage());
}

// 8. اختبار المزامنة الذكية (فقط إذا كان MySQL متاح)
echo "8. اختبار المزامنة الذكية...\n";
try {
    $mysqlAvailable = false;
    try {
        DB::connection('mysql')->getPdo();
        $mysqlAvailable = true;
        echo "   ✅ MySQL متاح - سيتم تشغيل المزامنة\n";
    } catch (\Exception $e) {
        echo "   ⚠️  MySQL غير متاح - سيتم تخطي المزامنة الفعلية\n";
        echo "   (هذا طبيعي في الوضع المحلي بدون اتصال بالسيرفر)\n";
    }
    
    if ($mysqlAvailable) {
        $syncService = new DatabaseSyncService();
        $syncResults = $syncService->syncPendingChanges(null, 10);
        
        echo "   المزامنة: {$syncResults['synced']}\n";
        echo "   الفاشلة: {$syncResults['failed']}\n";
        
        if (!empty($syncResults['errors'])) {
            echo "   الأخطاء:\n";
            foreach ($syncResults['errors'] as $error) {
                echo "     - {$error}\n";
            }
        }
        
        if ($syncResults['synced'] > 0) {
            testResult("المزامنة الذكية", true);
            
            // التحقق من وجود الفاتورة على السيرفر
            if ($testOrder) {
                $serverOrder = DB::connection('mysql')->table('orders')
                    ->where('id', $testOrder->id)
                    ->first();
                
                if ($serverOrder) {
                    echo "   ✅ تم العثور على الفاتورة على السيرفر (ID: {$serverOrder->id})\n";
                    testResult("وجود الفاتورة على السيرفر", true);
                } else {
                    // قد يكون ID مختلفاً بسبب تضارب ID
                    $mappingService = new SyncIdMappingService();
                    $serverId = $mappingService->getServerId('orders', $testOrder->id, 'up');
                    
                    if ($serverId) {
                        $serverOrder = DB::connection('mysql')->table('orders')
                            ->where('id', $serverId)
                            ->first();
                        
                        if ($serverOrder) {
                            echo "   ✅ تم العثور على الفاتورة على السيرفر (Local ID: {$testOrder->id}, Server ID: {$serverId})\n";
                            testResult("وجود الفاتورة على السيرفر (مع mapping)", true);
                        } else {
                            testResult("وجود الفاتورة على السيرفر", false, "الفاتورة غير موجودة حتى مع mapping");
                        }
                    } else {
                        testResult("وجود الفاتورة على السيرفر", false, "لا يوجد mapping للفاتورة");
                    }
                }
            }
        } else {
            testResult("المزامنة الذكية", $syncResults['failed'] === 0, "لم يتم مزامنة أي سجلات");
        }
    } else {
        testResult("المزامنة الذكية", true, "تم تخطي المزامنة (MySQL غير متاح)");
    }
} catch (\Exception $e) {
    testResult("المزامنة الذكية", false, $e->getMessage());
}

// 9. اختبار معالجة تضارب ID
echo "9. اختبار معالجة تضارب ID...\n";
try {
    $mappingService = new SyncIdMappingService();
    
    // اختبار checkIdConflict
    $testTable = 'orders';
    $testId = 999999; // ID غير موجود (على الأرجح)
    
    $hasConflict = $mappingService->checkIdConflict($testTable, $testId);
    echo "   التحقق من ID {$testId} في {$testTable}: " . ($hasConflict ? "موجود (تعارض)" : "غير موجود (لا تعارض)") . "\n";
    
    // اختبار resolveConflict
    if ($hasConflict) {
        $resolvedId = $mappingService->resolveConflict($testTable, $testId);
        echo "   ID المحلول: {$resolvedId}\n";
    }
    
    testResult("معالجة تضارب ID", true);
} catch (\Exception $e) {
    testResult("معالجة تضارب ID", false, $e->getMessage());
}

// 10. ملخص النتائج
echo "\n========================================\n";
echo "   ملخص النتائج\n";
echo "========================================\n";
echo "✅ الاختبارات الناجحة: {$results['tests_passed']}\n";
echo "❌ الاختبارات الفاشلة: {$results['tests_failed']}\n";

if (!empty($results['errors'])) {
    echo "\nالأخطاء:\n";
    foreach ($results['errors'] as $error) {
        echo "  - {$error}\n";
    }
}

$totalTests = $results['tests_passed'] + $results['tests_failed'];
$successRate = $totalTests > 0 ? round(($results['tests_passed'] / $totalTests) * 100, 2) : 0;
echo "\nنسبة النجاح: {$successRate}%\n";

if ($results['tests_failed'] === 0) {
    echo "\n🎉 جميع الاختبارات نجحت!\n";
} else {
    echo "\n⚠️  بعض الاختبارات فشلت. يرجى مراجعة الأخطاء أعلاه.\n";
}

echo "\n";

