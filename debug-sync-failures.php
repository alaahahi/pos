<?php

/**
 * فحص تفصيلي لأسباب فشل المزامنة
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "   فحص أسباب فشل المزامنة\n";
echo "========================================\n\n";

// 1. فحص السجلات الفاشلة بالتفصيل
echo "1. السجلات الفاشلة بالتفصيل:\n";
try {
    $failed = DB::table('sync_queue')
        ->where('status', 'failed')
        ->orderBy('updated_at', 'desc')
        ->get();
    
    if ($failed->isEmpty()) {
        echo "   ✅ لا توجد سجلات فاشلة\n\n";
    } else {
        echo "   عدد السجلات الفاشلة: " . $failed->count() . "\n\n";
        
        foreach ($failed as $index => $record) {
            echo "   السجل #" . ($index + 1) . ":\n";
            echo "     - ID: {$record->id}\n";
            echo "     - Table: {$record->table_name}\n";
            echo "     - Record ID: {$record->record_id}\n";
            echo "     - Action: {$record->action}\n";
            echo "     - Retry Count: {$record->retry_count}\n";
            echo "     - Error Message: " . ($record->error_message ?? 'N/A') . "\n";
            echo "     - Created: {$record->created_at}\n";
            echo "     - Updated: {$record->updated_at}\n";
            
            // عرض البيانات إذا كانت موجودة
            if ($record->data) {
                $data = json_decode($record->data, true);
                echo "     - Data Preview:\n";
                if ($record->table_name === 'orders') {
                    echo "         Order ID: " . ($data['id'] ?? 'N/A') . "\n";
                    echo "         Customer: " . ($data['customer_id'] ?? 'N/A') . "\n";
                    echo "         Total: " . ($data['total_amount'] ?? 'N/A') . "\n";
                } elseif ($record->table_name === 'order_product') {
                    echo "         Order ID: " . ($data['order_id'] ?? 'N/A') . "\n";
                    echo "         Product ID: " . ($data['product_id'] ?? 'N/A') . "\n";
                }
            }
            echo "\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

// 2. تحليل الأخطاء
echo "2. تحليل الأخطاء:\n";
try {
    $errorTypes = [];
    $failed = DB::table('sync_queue')
        ->where('status', 'failed')
        ->get();
    
    foreach ($failed as $record) {
        $errorMsg = $record->error_message ?? '';
        
        if (str_contains($errorMsg, 'MySQL غير متاح') || str_contains($errorMsg, 'connection attempt failed')) {
            $errorTypes['MySQL غير متاح'] = ($errorTypes['MySQL غير متاح'] ?? 0) + 1;
        } elseif (str_contains($errorMsg, 'Integrity constraint') || str_contains($errorMsg, 'Foreign key')) {
            $errorTypes['مشكلة في العلاقات'] = ($errorTypes['مشكلة في العلاقات'] ?? 0) + 1;
        } elseif (str_contains($errorMsg, 'Duplicate entry') || str_contains($errorMsg, 'UNIQUE constraint')) {
            $errorTypes['تضارب في البيانات'] = ($errorTypes['تضارب في البيانات'] ?? 0) + 1;
        } elseif (str_contains($errorMsg, 'no such table')) {
            $errorTypes['جدول غير موجود'] = ($errorTypes['جدول غير موجود'] ?? 0) + 1;
        } else {
            $errorTypes['أخطاء أخرى'] = ($errorTypes['أخطاء أخرى'] ?? 0) + 1;
        }
    }
    
    if (empty($errorTypes)) {
        echo "   ✅ لا توجد أخطاء\n\n";
    } else {
        echo "   أنواع الأخطاء:\n";
        foreach ($errorTypes as $type => $count) {
            echo "     - {$type}: {$count} سجل(ات)\n";
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

// 3. التحقق من MySQL
echo "3. التحقق من MySQL:\n";
try {
    DB::connection('mysql')->getPdo();
    echo "   ✅ MySQL متاح\n";
    
    // التحقق من الجداول
    $tables = ['orders', 'order_product'];
    foreach ($tables as $table) {
        try {
            $exists = DB::connection('mysql')->select("SHOW TABLES LIKE '{$table}'");
            if (!empty($exists)) {
                $count = DB::connection('mysql')->table($table)->count();
                echo "   ✅ جدول {$table} موجود - عدد السجلات: {$count}\n";
            } else {
                echo "   ❌ جدول {$table} غير موجود\n";
            }
        } catch (\Exception $e) {
            echo "   ❌ خطأ في التحقق من جدول {$table}: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ MySQL غير متاح: " . $e->getMessage() . "\n";
    echo "   ⚠️  هذا هو السبب الرئيسي للفشل!\n\n";
}

// 4. التحقق من sync_id_mapping
echo "4. التحقق من sync_id_mapping:\n";
try {
    $mappingCount = DB::table('sync_id_mapping')->count();
    echo "   عدد mappings: {$mappingCount}\n";
    
    if ($mappingCount > 0) {
        $mappings = DB::table('sync_id_mapping')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        echo "   آخر 5 mappings:\n";
        foreach ($mappings as $mapping) {
            echo "     - Table: {$mapping->table_name}, Local ID: {$mapping->local_id}, Server ID: {$mapping->server_id}\n";
        }
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

// 5. الحلول المقترحة
echo "5. الحلول المقترحة:\n";
try {
    $failedCount = DB::table('sync_queue')->where('status', 'failed')->count();
    $pendingCount = DB::table('sync_queue')->where('status', 'pending')->count();
    
    if ($failedCount > 0) {
        echo "   يوجد {$failedCount} سجل(ات) فاشل(ة)\n";
        echo "   الحل:\n";
        echo "     1. تأكد من أن MySQL متاح\n";
        echo "     2. قم بإعادة تعيين السجلات الفاشلة:\n";
        echo "        php retry-sync.php\n";
        echo "     3. أو استخدم API:\n";
        echo "        POST /api/sync-monitor/retry-failed\n";
    }
    
    if ($pendingCount > 0) {
        echo "   يوجد {$pendingCount} سجل(ات) معلقة\n";
        echo "   الحل:\n";
        echo "     1. تأكد من أن MySQL متاح\n";
        echo "     2. قم بتشغيل المزامنة:\n";
        echo "        من واجهة المستخدم: اضغط على 'المزامنة الذكية'\n";
        echo "        أو من API: POST /api/sync-monitor/smart-sync\n";
    }
    
    if ($failedCount === 0 && $pendingCount === 0) {
        echo "   ✅ لا توجد مشاكل - كل شيء جاهز!\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

echo "========================================\n";

