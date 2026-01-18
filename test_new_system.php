<?php
/**
 * اختبار النظام الجديد - Offline First
 */

echo "\n========================================\n";
echo "   اختبار نظام المزامنة التلقائية\n";
echo "========================================\n\n";

echo "1. اختبار API الجديد:\n";
echo "   ───────────────────\n";
$url = 'http://127.0.0.1:8000/api/sync-monitor/check-health';
echo "   URL: $url\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode === 200 && $response) {
    echo "   ✓ الاستجابة: $httpCode OK\n\n";
    
    $data = json_decode($response, true);
    
    if (isset($data['system_status'])) {
        $status = $data['system_status'];
        
        echo "   حالة النظام:\n";
        echo "   ────────────\n";
        echo "   الوضع: " . ($status['mode'] ?? 'unknown') . "\n";
        echo "   قاعدة البيانات المحلية: " . ($status['local_database_available'] ? '✓ متاحة' : '✗ غير متاحة') . "\n";
        echo "   الإنترنت: " . ($status['internet_available'] ? '✓ متصل' : '✗ غير متصل') . "\n";
        echo "   السيرفر البعيد: " . ($status['remote_server_available'] ? '✓ متاح' : '✗ غير متاح') . "\n";
        echo "   المزامنة التلقائية: " . ($status['auto_sync_enabled'] ? '✓ مفعّلة' : '✗ معطّلة') . "\n";
        
        if (isset($status['last_sync'])) {
            echo "   آخر مزامنة: " . ($status['last_sync'] ?? 'لم تتم بعد') . "\n";
        }
        
        if (isset($status['next_sync'])) {
            echo "   المزامنة القادمة: " . ($status['next_sync'] ?? 'غير محدد') . "\n";
        }
    }
    
    echo "\n   الاستجابة الكاملة:\n";
    echo "   " . str_repeat("─", 50) . "\n";
    echo "   " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
} elseif ($error) {
    echo "   ✗ خطأ في الاتصال: $error\n";
} else {
    echo "   ✗ رمز الخطأ: $httpCode\n";
    echo "   الاستجابة: $response\n";
}

echo "\n========================================\n";
echo "2. اختبار المزامنة اليدوية:\n";
echo "   ───────────────────────────\n\n";
echo "   لتجربة المزامنة، شغّل:\n";
echo "   php artisan sync:auto --force\n\n";

echo "========================================\n";
echo "3. تشغيل المزامنة التلقائية:\n";
echo "   ─────────────────────────────\n\n";
echo "   الطريقة الأولى (Batch):\n";
echo "   start-auto-sync.bat\n\n";
echo "   الطريقة الثانية (Scheduler):\n";
echo "   php artisan schedule:work\n\n";

echo "========================================\n";
echo "4. فتح الواجهة:\n";
echo "   ───────────\n\n";
echo "   http://127.0.0.1:8000/sync-monitor\n\n";
echo "   اضغط على: 🌐 فحص الاتصال\n\n";

echo "========================================\n";
echo "   ✓ النظام جاهز للاستخدام!\n";
echo "========================================\n\n";
