<?php

/**
 * عرض بيانات sync_queue بالتفصيل
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "   بيانات المزامنة (sync_queue)\n";
echo "========================================\n\n";

// 1. إحصائيات عامة
echo "1. إحصائيات عامة:\n";
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
    echo "   المعلقة (pending): {$stats->pending}\n";
    echo "   المزامنة (synced): {$stats->synced}\n";
    echo "   الفاشلة (failed): {$stats->failed}\n\n";
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

// 2. بنية الجدول
echo "2. بنية جدول sync_queue:\n";
echo "   - id: معرف السجل\n";
echo "   - table_name: اسم الجدول (مثل: orders, order_product)\n";
echo "   - record_id: معرف السجل في الجدول المحلي\n";
echo "   - action: نوع العملية (insert, update, delete)\n";
echo "   - data: البيانات الكاملة للسجل (JSON)\n";
echo "   - changes: الحقول التي تغيرت (للتحديثات) (JSON)\n";
echo "   - status: الحالة (pending, synced, failed)\n";
echo "   - retry_count: عدد المحاولات\n";
echo "   - error_message: رسالة الخطأ (إن وجدت)\n";
echo "   - synced_at: تاريخ المزامنة\n";
echo "   - created_at: تاريخ الإنشاء\n";
echo "   - updated_at: تاريخ التحديث\n\n";

// 3. عرض أمثلة على السجلات المعلقة
echo "3. أمثلة على السجلات المعلقة:\n";
try {
    $pending = DB::table('sync_queue')
        ->where('status', 'pending')
        ->orderBy('created_at', 'asc')
        ->limit(5)
        ->get();
    
    if ($pending->isEmpty()) {
        echo "   لا توجد سجلات معلقة\n\n";
    } else {
        foreach ($pending as $index => $record) {
            echo "   السجل #" . ($index + 1) . ":\n";
            echo "     - ID: {$record->id}\n";
            echo "     - الجدول: {$record->table_name}\n";
            echo "     - معرف السجل: {$record->record_id}\n";
            echo "     - العملية: {$record->action}\n";
            echo "     - الحالة: {$record->status}\n";
            echo "     - تاريخ الإنشاء: {$record->created_at}\n";
            
            // عرض البيانات (JSON)
            if ($record->data) {
                $data = json_decode($record->data, true);
                echo "     - البيانات:\n";
                foreach ($data as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        echo "         {$key}: " . json_encode($value, JSON_UNESCAPED_UNICODE) . "\n";
                    } else {
                        $displayValue = is_string($value) && strlen($value) > 50 
                            ? substr($value, 0, 50) . '...' 
                            : $value;
                        echo "         {$key}: {$displayValue}\n";
                    }
                }
            }
            
            // عرض التغييرات (للتحديثات)
            if ($record->changes) {
                $changes = json_decode($record->changes, true);
                echo "     - التغييرات:\n";
                foreach ($changes as $key => $change) {
                    $oldValue = is_array($change['old'] ?? null) ? json_encode($change['old']) : ($change['old'] ?? 'null');
                    $newValue = is_array($change['new'] ?? null) ? json_encode($change['new']) : ($change['new'] ?? 'null');
                    echo "         {$key}: {$oldValue} → {$newValue}\n";
                }
            }
            
            echo "\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

// 4. عرض أمثلة على السجلات المزامنة
echo "4. آخر السجلات المزامنة:\n";
try {
    $synced = DB::table('sync_queue')
        ->where('status', 'synced')
        ->orderBy('synced_at', 'desc')
        ->limit(3)
        ->get();
    
    if ($synced->isEmpty()) {
        echo "   لا توجد سجلات مزامنة بعد\n\n";
    } else {
        foreach ($synced as $index => $record) {
            echo "   السجل #" . ($index + 1) . ":\n";
            echo "     - ID: {$record->id}\n";
            echo "     - الجدول: {$record->table_name}\n";
            echo "     - معرف السجل: {$record->record_id}\n";
            echo "     - العملية: {$record->action}\n";
            echo "     - تاريخ المزامنة: {$record->synced_at}\n\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

// 5. عرض أمثلة على السجلات الفاشلة
echo "5. آخر السجلات الفاشلة:\n";
try {
    $failed = DB::table('sync_queue')
        ->where('status', 'failed')
        ->orderBy('updated_at', 'desc')
        ->limit(3)
        ->get();
    
    if ($failed->isEmpty()) {
        echo "   لا توجد سجلات فاشلة\n\n";
    } else {
        foreach ($failed as $index => $record) {
            echo "   السجل #" . ($index + 1) . ":\n";
            echo "     - ID: {$record->id}\n";
            echo "     - الجدول: {$record->table_name}\n";
            echo "     - معرف السجل: {$record->record_id}\n";
            echo "     - العملية: {$record->action}\n";
            echo "     - عدد المحاولات: {$record->retry_count}\n";
            echo "     - رسالة الخطأ: " . substr($record->error_message ?? 'N/A', 0, 100) . "\n\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

// 6. إحصائيات حسب الجدول
echo "6. إحصائيات حسب الجدول:\n";
try {
    $tableStats = DB::table('sync_queue')
        ->selectRaw('
            table_name,
            COUNT(*) as total,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = "synced" THEN 1 ELSE 0 END) as synced,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
        ')
        ->groupBy('table_name')
        ->orderBy('total', 'desc')
        ->get();
    
    if ($tableStats->isEmpty()) {
        echo "   لا توجد بيانات\n\n";
    } else {
        foreach ($tableStats as $stat) {
            echo "   {$stat->table_name}:\n";
            echo "     - الإجمالي: {$stat->total}\n";
            echo "     - المعلقة: {$stat->pending}\n";
            echo "     - المزامنة: {$stat->synced}\n";
            echo "     - الفاشلة: {$stat->failed}\n\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

// 7. إحصائيات حسب العملية
echo "7. إحصائيات حسب العملية:\n";
try {
    $actionStats = DB::table('sync_queue')
        ->selectRaw('
            action,
            COUNT(*) as total,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = "synced" THEN 1 ELSE 0 END) as synced,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
        ')
        ->groupBy('action')
        ->get();
    
    foreach ($actionStats as $stat) {
        echo "   {$stat->action}:\n";
        echo "     - الإجمالي: {$stat->total}\n";
        echo "     - المعلقة: {$stat->pending}\n";
        echo "     - المزامنة: {$stat->synced}\n";
        echo "     - الفاشلة: {$stat->failed}\n\n";
    }
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

echo "========================================\n";
echo "   ملاحظات:\n";
echo "========================================\n";
echo "1. البيانات (data): تحتوي على جميع حقول السجل الكاملة\n";
echo "2. التغييرات (changes): تحتوي على الحقول التي تغيرت فقط (للتحديثات)\n";
echo "3. عند المزامنة:\n";
echo "   - يتم قراءة البيانات من sync_queue\n";
echo "   - يتم إرسالها إلى MySQL (السيرفر)\n";
echo "   - يتم تحديث الحالة إلى 'synced'\n";
echo "4. إذا فشلت المزامنة:\n";
echo "   - يتم تحديث الحالة إلى 'failed'\n";
echo "   - يتم حفظ رسالة الخطأ في error_message\n";
echo "   - يمكن إعادة المحاولة لاحقاً\n";
echo "\n";

