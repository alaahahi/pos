<?php

/**
 * اختبار شامل: التحقق من وصول البيانات إلى السيرفر وقراءتها
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
use App\Services\DatabaseSyncService;
use App\Services\SyncQueueService;

echo "========================================\n";
echo "   اختبار المزامنة إلى السيرفر\n";
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
            echo "   رسالة: {$message}\n";
        }
        $results['tests_failed']++;
        $results['errors'][] = "{$testName}: {$message}";
    }
    echo "\n";
}

// 1. التحقق من MySQL
echo "1. التحقق من توفر MySQL...\n";
$mysqlAvailable = false;
try {
    DB::connection('mysql')->getPdo();
    $mysqlAvailable = true;
    echo "   ✅ MySQL متاح\n\n";
    testResult("توفر MySQL", true);
} catch (\Exception $e) {
    echo "   ❌ MySQL غير متاح: " . $e->getMessage() . "\n";
    echo "   ⚠️  لا يمكن إكمال الاختبار بدون MySQL\n\n";
    testResult("توفر MySQL", false, $e->getMessage());
    exit(1);
}

// 2. التحقق من البيانات في SQLite (قبل المزامنة)
echo "2. التحقق من البيانات في SQLite (قبل المزامنة)...\n";
try {
    $sqliteOrders = DB::table('orders')
        ->orderBy('id', 'desc')
        ->limit(5)
        ->get();
    
    $sqliteOrdersCount = DB::table('orders')->count();
    echo "   عدد الطلبات في SQLite: {$sqliteOrdersCount}\n";
    
    if ($sqliteOrders->isEmpty()) {
        testResult("وجود بيانات في SQLite", false, "لا توجد طلبات في SQLite");
    } else {
        echo "   آخر 5 طلبات:\n";
        foreach ($sqliteOrders as $order) {
            echo "     - ID: {$order->id}, Customer: {$order->customer_id}, Total: {$order->total_amount}, Date: {$order->date}\n";
        }
        testResult("وجود بيانات في SQLite", true);
    }
} catch (\Exception $e) {
    testResult("وجود بيانات في SQLite", false, $e->getMessage());
}

// 3. التحقق من sync_queue
echo "3. التحقق من sync_queue...\n";
try {
    $pendingCount = DB::table('sync_queue')->where('status', 'pending')->count();
    echo "   عدد السجلات المعلقة: {$pendingCount}\n";
    
    if ($pendingCount === 0) {
        echo "   ⚠️  لا توجد سجلات معلقة - سيتم إنشاء فاتورة تجريبية\n\n";
        
        // إنشاء فاتورة تجريبية
        $customer = Customer::first();
        $product = Product::first();
        
        if ($customer && $product) {
            DB::beginTransaction();
            
            $order = Order::create([
                'customer_id' => $customer->id,
                'payment_method' => 'cash',
                'status' => 'paid',
                'total_amount' => 250.00,
                'total_paid' => 250.00,
                'date' => now()->format('Y-m-d'),
                'notes' => 'فاتورة اختبار المزامنة - ' . now()->format('Y-m-d H:i:s'),
                'discount_amount' => 0,
                'discount_rate' => 0,
                'final_amount' => 250.00,
            ]);
            
            $order->products()->attach($product->id, [
                'quantity' => 2,
                'price' => 125.00,
            ]);
            
            DB::commit();
            
            echo "   ✅ تم إنشاء فاتورة تجريبية رقم: {$order->id}\n";
            $testOrderId = $order->id;
        } else {
            echo "   ❌ لا توجد بيانات أساسية (عميل أو منتج)\n";
            exit(1);
        }
    } else {
        $testOrderId = null;
    }
    
    testResult("التحقق من sync_queue", true);
} catch (\Exception $e) {
    testResult("التحقق من sync_queue", false, $e->getMessage());
}

// 4. تشغيل المزامنة
echo "4. تشغيل المزامنة...\n";
try {
    $syncService = new DatabaseSyncService();
    $syncResults = $syncService->syncPendingChanges(null, 100);
    
    echo "   المزامنة: {$syncResults['synced']}\n";
    echo "   الفاشلة: {$syncResults['failed']}\n";
    
    if (!empty($syncResults['errors'])) {
        echo "   الأخطاء:\n";
        foreach (array_slice($syncResults['errors'], 0, 3) as $error) {
            echo "     - " . substr($error, 0, 80) . "\n";
        }
    }
    
    if ($syncResults['synced'] > 0) {
        testResult("تشغيل المزامنة", true);
    } else {
        testResult("تشغيل المزامنة", false, "لم يتم مزامنة أي سجلات");
    }
} catch (\Exception $e) {
    testResult("تشغيل المزامنة", false, $e->getMessage());
}

// 5. التحقق من البيانات في MySQL (بعد المزامنة)
echo "5. التحقق من البيانات في MySQL (بعد المزامنة)...\n";
try {
    $mysqlOrdersCount = DB::connection('mysql')->table('orders')->count();
    echo "   عدد الطلبات في MySQL: {$mysqlOrdersCount}\n";
    
    $mysqlOrders = DB::connection('mysql')->table('orders')
        ->orderBy('id', 'desc')
        ->limit(5)
        ->get();
    
    if ($mysqlOrders->isEmpty()) {
        testResult("وجود بيانات في MySQL", false, "لا توجد طلبات في MySQL");
    } else {
        echo "   آخر 5 طلبات في MySQL:\n";
        foreach ($mysqlOrders as $order) {
            echo "     - ID: {$order->id}, Customer: {$order->customer_id}, Total: {$order->total_amount}, Date: {$order->date}\n";
        }
        testResult("وجود بيانات في MySQL", true);
    }
} catch (\Exception $e) {
    testResult("وجود بيانات في MySQL", false, $e->getMessage());
}

// 6. مقارنة البيانات بين SQLite و MySQL
echo "6. مقارنة البيانات بين SQLite و MySQL...\n";
try {
    // جلب آخر 10 طلبات من SQLite
    $sqliteOrders = DB::table('orders')
        ->orderBy('id', 'desc')
        ->limit(10)
        ->get()
        ->keyBy('id');
    
    // جلب آخر 10 طلبات من MySQL
    $mysqlOrders = DB::connection('mysql')->table('orders')
        ->orderBy('id', 'desc')
        ->limit(10)
        ->get()
        ->keyBy('id');
    
    $matched = 0;
    $notMatched = 0;
    
    foreach ($sqliteOrders as $sqliteOrder) {
        $mysqlOrder = $mysqlOrders->get($sqliteOrder->id);
        
        if ($mysqlOrder) {
            // مقارنة البيانات
            $fieldsMatch = true;
            $differences = [];
            
            $fieldsToCompare = ['customer_id', 'total_amount', 'total_paid', 'status', 'date', 'final_amount'];
            
            foreach ($fieldsToCompare as $field) {
                if ($sqliteOrder->$field != $mysqlOrder->$field) {
                    $fieldsMatch = false;
                    $differences[] = "{$field}: SQLite={$sqliteOrder->$field}, MySQL={$mysqlOrder->$field}";
                }
            }
            
            if ($fieldsMatch) {
                $matched++;
                echo "   ✅ Order ID {$sqliteOrder->id}: البيانات متطابقة\n";
            } else {
                $notMatched++;
                echo "   ⚠️  Order ID {$sqliteOrder->id}: هناك اختلافات\n";
                foreach ($differences as $diff) {
                    echo "      - {$diff}\n";
                }
            }
        } else {
            $notMatched++;
            echo "   ❌ Order ID {$sqliteOrder->id}: غير موجود في MySQL\n";
        }
    }
    
    if ($matched > 0) {
        testResult("مقارنة البيانات", true, "تمت مطابقة {$matched} طلب(ات)");
    } else {
        testResult("مقارنة البيانات", false, "لم يتم العثور على تطابقات");
    }
} catch (\Exception $e) {
    testResult("مقارنة البيانات", false, $e->getMessage());
}

// 7. التحقق من order_product في MySQL
echo "7. التحقق من order_product في MySQL...\n";
try {
    $mysqlOrderProductCount = DB::connection('mysql')->table('order_product')->count();
    echo "   عدد سجلات order_product في MySQL: {$mysqlOrderProductCount}\n";
    
    $sqliteOrderProductCount = DB::table('order_product')->count();
    echo "   عدد سجلات order_product في SQLite: {$sqliteOrderProductCount}\n";
    
    if ($mysqlOrderProductCount > 0) {
        $mysqlOrderProducts = DB::connection('mysql')->table('order_product')
            ->orderBy('order_id', 'desc')
            ->limit(5)
            ->get();
        
        echo "   آخر 5 سجلات order_product في MySQL:\n";
        foreach ($mysqlOrderProducts as $op) {
            echo "     - Order ID: {$op->order_id}, Product ID: {$op->product_id}, Quantity: {$op->quantity}, Price: {$op->price}\n";
        }
        testResult("وجود order_product في MySQL", true);
    } else {
        testResult("وجود order_product في MySQL", false, "لا توجد سجلات order_product في MySQL");
    }
} catch (\Exception $e) {
    testResult("وجود order_product في MySQL", false, $e->getMessage());
}

// 8. التحقق من sync_queue بعد المزامنة
echo "8. التحقق من sync_queue بعد المزامنة...\n";
try {
    $pendingCount = DB::table('sync_queue')->where('status', 'pending')->count();
    $syncedCount = DB::table('sync_queue')->where('status', 'synced')->count();
    $failedCount = DB::table('sync_queue')->where('status', 'failed')->count();
    
    echo "   المعلقة: {$pendingCount}\n";
    echo "   المزامنة: {$syncedCount}\n";
    echo "   الفاشلة: {$failedCount}\n";
    
    if ($syncedCount > 0) {
        testResult("حالة sync_queue بعد المزامنة", true);
    } else {
        testResult("حالة sync_queue بعد المزامنة", false, "لا توجد سجلات مزامنة");
    }
} catch (\Exception $e) {
    testResult("حالة sync_queue بعد المزامنة", false, $e->getMessage());
}

// 9. قراءة بيانات محددة من MySQL
echo "9. قراءة بيانات محددة من MySQL...\n";
try {
    // جلب آخر طلب من MySQL
    $lastOrder = DB::connection('mysql')->table('orders')
        ->orderBy('id', 'desc')
        ->first();
    
    if ($lastOrder) {
        echo "   آخر طلب في MySQL:\n";
        echo "     - ID: {$lastOrder->id}\n";
        echo "     - Customer ID: {$lastOrder->customer_id}\n";
        echo "     - Total Amount: {$lastOrder->total_amount}\n";
        echo "     - Status: {$lastOrder->status}\n";
        echo "     - Date: {$lastOrder->date}\n";
        echo "     - Created: {$lastOrder->created_at}\n";
        
        // جلب المنتجات المرتبطة
        $orderProducts = DB::connection('mysql')->table('order_product')
            ->where('order_id', $lastOrder->id)
            ->get();
        
        if ($orderProducts->isNotEmpty()) {
            echo "   المنتجات المرتبطة:\n";
            foreach ($orderProducts as $op) {
                echo "     - Product ID: {$op->product_id}, Quantity: {$op->quantity}, Price: {$op->price}\n";
            }
        }
        
        testResult("قراءة بيانات محددة من MySQL", true);
    } else {
        testResult("قراءة بيانات محددة من MySQL", false, "لا توجد طلبات في MySQL");
    }
} catch (\Exception $e) {
    testResult("قراءة بيانات محددة من MySQL", false, $e->getMessage());
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
    echo "\n🎉 جميع الاختبارات نجحت! البيانات وصلت إلى السيرفر بنجاح!\n";
} else {
    echo "\n⚠️  بعض الاختبارات فشلت. يرجى مراجعة الأخطاء أعلاه.\n";
}

echo "\n";

