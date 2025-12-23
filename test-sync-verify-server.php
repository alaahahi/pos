<?php

/**
 * اختبار شامل: التحقق من وصول البيانات إلى السيرفر وقراءتها
 * يعمل حتى لو كان MySQL غير متاح (محاكاة)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\DatabaseSyncService;

echo "========================================\n";
echo "   اختبار التحقق من وصول البيانات للسيرفر\n";
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
$mysqlConnection = null;
try {
    $mysqlConnection = DB::connection('mysql');
    $mysqlConnection->getPdo();
    $mysqlAvailable = true;
    echo "   ✅ MySQL متاح\n";
    echo "   Host: " . config('database.connections.mysql.host') . "\n";
    echo "   Database: " . config('database.connections.mysql.database') . "\n\n";
    testResult("توفر MySQL", true);
} catch (\Exception $e) {
    echo "   ❌ MySQL غير متاح: " . $e->getMessage() . "\n";
    echo "   ⚠️  سيتم محاكاة الاختبار (Simulation Mode)\n\n";
    testResult("توفر MySQL", false, "MySQL غير متاح - وضع المحاكاة");
    $mysqlAvailable = false;
}

// 2. قراءة البيانات من SQLite
echo "2. قراءة البيانات من SQLite (المحلي)...\n";
try {
    $sqliteOrders = DB::table('orders')
        ->orderBy('id', 'desc')
        ->limit(10)
        ->get();
    
    $sqliteOrdersCount = DB::table('orders')->count();
    echo "   عدد الطلبات في SQLite: {$sqliteOrdersCount}\n";
    
    if ($sqliteOrders->isEmpty()) {
        testResult("قراءة البيانات من SQLite", false, "لا توجد طلبات في SQLite");
    } else {
        echo "   آخر 10 طلبات في SQLite:\n";
        foreach ($sqliteOrders as $order) {
            echo "     - ID: {$order->id}, Customer: {$order->customer_id}, Total: {$order->total_amount}, Status: {$order->status}, Date: {$order->date}\n";
        }
        testResult("قراءة البيانات من SQLite", true);
    }
    
    // قراءة order_product
    $sqliteOrderProducts = DB::table('order_product')
        ->orderBy('order_id', 'desc')
        ->limit(10)
        ->get();
    
    $sqliteOrderProductCount = DB::table('order_product')->count();
    echo "   عدد سجلات order_product في SQLite: {$sqliteOrderProductCount}\n";
    
} catch (\Exception $e) {
    testResult("قراءة البيانات من SQLite", false, $e->getMessage());
}

// 3. التحقق من sync_queue
echo "3. التحقق من sync_queue...\n";
try {
    $pendingCount = DB::table('sync_queue')->where('status', 'pending')->count();
    $syncedCount = DB::table('sync_queue')->where('status', 'synced')->count();
    $failedCount = DB::table('sync_queue')->where('status', 'failed')->count();
    
    echo "   المعلقة: {$pendingCount}\n";
    echo "   المزامنة: {$syncedCount}\n";
    echo "   الفاشلة: {$failedCount}\n";
    
    if ($pendingCount > 0) {
        echo "   السجلات المعلقة:\n";
        $pending = DB::table('sync_queue')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->limit(5)
            ->get();
        
        foreach ($pending as $record) {
            echo "     - ID: {$record->id}, Table: {$record->table_name}, Record ID: {$record->record_id}, Action: {$record->action}\n";
        }
    }
    
    testResult("التحقق من sync_queue", true);
} catch (\Exception $e) {
    testResult("التحقق من sync_queue", false, $e->getMessage());
}

// 4. محاولة المزامنة (إذا كان MySQL متاحاً)
if ($mysqlAvailable) {
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
    
    // 5. قراءة البيانات من MySQL (بعد المزامنة)
    echo "5. قراءة البيانات من MySQL (بعد المزامنة)...\n";
    try {
        $mysqlOrdersCount = DB::connection('mysql')->table('orders')->count();
        echo "   عدد الطلبات في MySQL: {$mysqlOrdersCount}\n";
        
        $mysqlOrders = DB::connection('mysql')->table('orders')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
        
        if ($mysqlOrders->isEmpty()) {
            testResult("قراءة البيانات من MySQL", false, "لا توجد طلبات في MySQL");
        } else {
            echo "   آخر 10 طلبات في MySQL:\n";
            foreach ($mysqlOrders as $order) {
                echo "     - ID: {$order->id}, Customer: {$order->customer_id}, Total: {$order->total_amount}, Status: {$order->status}, Date: {$order->date}\n";
            }
            testResult("قراءة البيانات من MySQL", true);
        }
        
        // قراءة order_product
        $mysqlOrderProducts = DB::connection('mysql')->table('order_product')
            ->orderBy('order_id', 'desc')
            ->limit(10)
            ->get();
        
        $mysqlOrderProductCount = DB::connection('mysql')->table('order_product')->count();
        echo "   عدد سجلات order_product في MySQL: {$mysqlOrderProductCount}\n";
        
        if ($mysqlOrderProducts->isNotEmpty()) {
            echo "   آخر 10 سجلات order_product في MySQL:\n";
            foreach ($mysqlOrderProducts as $op) {
                echo "     - Order ID: {$op->order_id}, Product ID: {$op->product_id}, Quantity: {$op->quantity}, Price: {$op->price}\n";
            }
        }
        
    } catch (\Exception $e) {
        testResult("قراءة البيانات من MySQL", false, $e->getMessage());
    }
    
    // 6. مقارنة البيانات بين SQLite و MySQL
    echo "6. مقارنة البيانات بين SQLite و MySQL...\n";
    try {
        $sqliteOrders = DB::table('orders')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->keyBy('id');
        
        $mysqlOrders = DB::connection('mysql')->table('orders')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->keyBy('id');
        
        $matched = 0;
        $notMatched = 0;
        $notFound = 0;
        
        foreach ($sqliteOrders as $sqliteOrder) {
            $mysqlOrder = $mysqlOrders->get($sqliteOrder->id);
            
            if ($mysqlOrder) {
                // مقارنة البيانات
                $fieldsMatch = true;
                $differences = [];
                
                $fieldsToCompare = ['customer_id', 'total_amount', 'total_paid', 'status', 'date', 'final_amount'];
                
                foreach ($fieldsToCompare as $field) {
                    $sqliteValue = $sqliteOrder->$field;
                    $mysqlValue = $mysqlOrder->$field;
                    
                    // معالجة الفروقات في الأنواع
                    if (is_numeric($sqliteValue) && is_numeric($mysqlValue)) {
                        if (abs($sqliteValue - $mysqlValue) > 0.01) {
                            $fieldsMatch = false;
                            $differences[] = "{$field}: SQLite={$sqliteValue}, MySQL={$mysqlValue}";
                        }
                    } elseif ($sqliteValue != $mysqlValue) {
                        $fieldsMatch = false;
                        $differences[] = "{$field}: SQLite={$sqliteValue}, MySQL={$mysqlValue}";
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
                $notFound++;
                echo "   ❌ Order ID {$sqliteOrder->id}: غير موجود في MySQL\n";
            }
        }
        
        echo "\n   الملخص:\n";
        echo "     - متطابقة: {$matched}\n";
        echo "     - مختلفة: {$notMatched}\n";
        echo "     - غير موجودة: {$notFound}\n";
        
        if ($matched > 0) {
            testResult("مقارنة البيانات", true, "تمت مطابقة {$matched} طلب(ات)");
        } else {
            testResult("مقارنة البيانات", false, "لم يتم العثور على تطابقات");
        }
    } catch (\Exception $e) {
        testResult("مقارنة البيانات", false, $e->getMessage());
    }
    
    // 7. قراءة بيانات محددة من MySQL
    echo "7. قراءة بيانات محددة من MySQL...\n";
    try {
        $lastOrder = DB::connection('mysql')->table('orders')
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastOrder) {
            echo "   آخر طلب في MySQL:\n";
            echo "     - ID: {$lastOrder->id}\n";
            echo "     - Customer ID: {$lastOrder->customer_id}\n";
            echo "     - Total Amount: {$lastOrder->total_amount}\n";
            echo "     - Total Paid: {$lastOrder->total_paid}\n";
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
            } else {
                echo "   ⚠️  لا توجد منتجات مرتبطة\n";
            }
            
            testResult("قراءة بيانات محددة من MySQL", true);
        } else {
            testResult("قراءة بيانات محددة من MySQL", false, "لا توجد طلبات في MySQL");
        }
    } catch (\Exception $e) {
        testResult("قراءة بيانات محددة من MySQL", false, $e->getMessage());
    }
} else {
    echo "4. ⚠️  وضع المحاكاة (MySQL غير متاح)...\n";
    echo "   سيتم عرض البيانات التي ستُزامن:\n\n";
    
    $pending = DB::table('sync_queue')
        ->where('status', 'pending')
        ->orderBy('created_at', 'asc')
        ->get();
    
    if ($pending->isEmpty()) {
        echo "   لا توجد سجلات معلقة للمزامنة\n";
    } else {
        echo "   السجلات التي ستُزامن ({$pending->count()}):\n";
        foreach ($pending as $record) {
            echo "     - Table: {$record->table_name}, Record ID: {$record->record_id}, Action: {$record->action}\n";
            
            if ($record->data) {
                $data = json_decode($record->data, true);
                if ($record->table_name === 'orders') {
                    echo "       Order: ID={$data['id']}, Customer={$data['customer_id']}, Total={$data['total_amount']}, Status={$data['status']}\n";
                } elseif ($record->table_name === 'order_product') {
                    echo "       OrderProduct: Order={$data['order_id']}, Product={$data['product_id']}, Qty={$data['quantity']}, Price={$data['price']}\n";
                }
            }
        }
    }
    
    echo "\n   عندما يكون MySQL متاحاً:\n";
    echo "   1. سيتم قراءة هذه السجلات من sync_queue\n";
    echo "   2. سيتم إرسالها إلى MySQL\n";
    echo "   3. سيتم تحديث الحالة إلى 'synced'\n";
    echo "   4. يمكنك قراءتها من MySQL باستخدام:\n";
    echo "      SELECT * FROM orders ORDER BY id DESC LIMIT 10;\n";
    echo "      SELECT * FROM order_product ORDER BY order_id DESC LIMIT 10;\n";
}

// 8. ملخص النتائج
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

if ($mysqlAvailable) {
    if ($results['tests_failed'] === 0) {
        echo "\n🎉 جميع الاختبارات نجحت! البيانات وصلت إلى السيرفر بنجاح!\n";
    } else {
        echo "\n⚠️  بعض الاختبارات فشلت. يرجى مراجعة الأخطاء أعلاه.\n";
    }
} else {
    echo "\n⚠️  MySQL غير متاح - تم عرض البيانات التي ستُزامن عند توفر MySQL\n";
    echo "   عندما يكون MySQL متاحاً، قم بتشغيل الاختبار مرة أخرى:\n";
    echo "   php test-sync-verify-server.php\n";
}

echo "\n";

