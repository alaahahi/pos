<?php
/**
 * اختبار API فحص حالة المزامنة
 */

$url = 'http://127.0.0.1:8000/api/sync-monitor/sync-health';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "========================================\n";
echo "   فحص حالة المزامنة\n";
echo "========================================\n\n";

echo "URL: {$url}\n";
echo "HTTP Code: {$httpCode}\n\n";

if ($response) {
    $data = json_decode($response, true);
    
    if ($data && isset($data['success']) && $data['success']) {
        $health = $data['health'];
        
        echo "📊 الحالة العامة: " . strtoupper($health['overall_status']) . "\n";
        echo "💬 الرسالة: {$health['message']}\n\n";
        
        // API Sync
        echo "🔌 API Sync:\n";
        echo "   مفعّل: " . ($health['api_sync']['enabled'] ? '✅ نعم' : '❌ لا') . "\n";
        echo "   ONLINE_URL: {$health['api_sync']['online_url']}\n";
        echo "   API Token: " . ($health['api_sync']['api_token_set'] ? '✅ محدد' : '❌ غير محدد') . "\n";
        echo "   Timeout: {$health['api_sync']['api_timeout']}s\n\n";
        
        // API Service
        if (isset($health['api_service'])) {
            echo "🌐 API Service:\n";
            echo "   متاح: " . ($health['api_service']['available'] ? '✅ نعم' : '❌ لا') . "\n";
            echo "   الحالة: {$health['api_service']['status']}\n";
            if (isset($health['api_service']['error'])) {
                echo "   خطأ: {$health['api_service']['error']}\n";
            }
            echo "\n";
        }
        
        // Database Sync Service
        if (isset($health['database_sync_service'])) {
            echo "🔄 Database Sync Service:\n";
            echo "   يستخدم API: " . ($health['database_sync_service']['use_api'] ? '✅ نعم' : '❌ لا') . "\n";
            echo "   الحالة: {$health['database_sync_service']['status']}\n";
            if (isset($health['database_sync_service']['error'])) {
                echo "   خطأ: {$health['database_sync_service']['error']}\n";
            }
            echo "\n";
        }
        
        // MySQL (إذا كان موجود)
        if (isset($health['mysql'])) {
            echo "🗄️  MySQL:\n";
            echo "   متاح: " . ($health['mysql']['available'] ? '✅ نعم' : '❌ لا') . "\n";
            echo "   الحالة: {$health['mysql']['status']}\n";
            if (isset($health['mysql']['error'])) {
                echo "   خطأ: {$health['mysql']['error']}\n";
            }
            echo "\n";
        }
        
        // Sync Queue
        if (isset($health['sync_queue'])) {
            echo "📋 Sync Queue:\n";
            echo "   الجدول موجود: " . ($health['sync_queue']['table_exists'] ? '✅ نعم' : '❌ لا') . "\n";
            if (isset($health['sync_queue']['stats'])) {
                $stats = $health['sync_queue']['stats'];
                echo "   Pending: {$stats['pending']}\n";
                echo "   Synced: {$stats['synced']}\n";
                echo "   Failed: {$stats['failed']}\n";
                echo "   Total: {$stats['total']}\n";
            }
            echo "   الحالة: {$health['sync_queue']['status']}\n";
            if (isset($health['sync_queue']['error'])) {
                echo "   خطأ: {$health['sync_queue']['error']}\n";
            }
            echo "\n";
        }
        
        // Queue Worker
        if (isset($health['queue_worker'])) {
            echo "⚙️  Queue Worker:\n";
            echo "   Connection: {$health['queue_worker']['connection']}\n";
            echo "   الحالة: {$health['queue_worker']['status']}\n";
            if (isset($health['queue_worker']['jobs_table_exists'])) {
                echo "   جدول jobs موجود: " . ($health['queue_worker']['jobs_table_exists'] ? '✅ نعم' : '❌ لا') . "\n";
            }
            if (isset($health['queue_worker']['error'])) {
                echo "   خطأ: {$health['queue_worker']['error']}\n";
            }
            echo "\n";
        }
        
        // Issues
        if (!empty($health['issues'])) {
            echo "❌ المشاكل:\n";
            foreach ($health['issues'] as $issue) {
                echo "   - {$issue}\n";
            }
            echo "\n";
        }
        
        // Warnings
        if (!empty($health['warnings'])) {
            echo "⚠️  التحذيرات:\n";
            foreach ($health['warnings'] as $warning) {
                echo "   - {$warning}\n";
            }
            echo "\n";
        }
        
        // Info
        if (!empty($health['info'])) {
            echo "ℹ️  معلومات:\n";
            foreach ($health['info'] as $info) {
                echo "   - {$info}\n";
            }
            echo "\n";
        }
        
        // Recommendations
        if (!empty($health['recommendations'])) {
            echo "💡 التوصيات:\n";
            foreach ($health['recommendations'] as $rec) {
                echo "   - {$rec}\n";
            }
            echo "\n";
        }
        
    } else {
        echo "❌ فشل الطلب:\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "❌ فشل الاتصال بالـ API\n";
}

echo "========================================\n\n";

