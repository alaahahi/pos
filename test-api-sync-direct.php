<?php
/**
 * اختبار مباشر لـ API Sync
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\DatabaseSyncService;
use App\Services\ApiSyncService;
use Illuminate\Support\Facades\Log;

echo "========================================\n";
echo "   اختبار API Sync\n";
echo "========================================\n\n";

try {
    // 1. التحقق من الإعدادات
    echo "1. التحقق من الإعدادات:\n";
    $syncViaApi = env('SYNC_VIA_API', false);
    $useApi = filter_var($syncViaApi, FILTER_VALIDATE_BOOLEAN);
    echo "   SYNC_VIA_API: {$syncViaApi}\n";
    echo "   useApi (boolean): " . ($useApi ? 'true' : 'false') . "\n";
    echo "   ONLINE_URL: " . env('ONLINE_URL') . "\n";
    echo "   SYNC_API_TOKEN: " . (empty(env('SYNC_API_TOKEN')) ? 'غير محدد' : 'محدد') . "\n\n";
    
    if (!$useApi) {
        echo "❌ API Sync غير مفعّل!\n";
        echo "   أضف SYNC_VIA_API=true في .env\n\n";
        exit(1);
    }
    
    // 2. اختبار ApiSyncService
    echo "2. اختبار ApiSyncService:\n";
    $apiService = new ApiSyncService();
    
    // استخدام reflection للوصول إلى protected properties
    $reflection = new ReflectionClass($apiService);
    $apiUrlProperty = $reflection->getProperty('apiUrl');
    $apiUrlProperty->setAccessible(true);
    $apiTokenProperty = $reflection->getProperty('apiToken');
    $apiTokenProperty->setAccessible(true);
    
    echo "   API URL: " . $apiUrlProperty->getValue($apiService) . "\n";
    echo "   API Token: " . (empty($apiTokenProperty->getValue($apiService)) ? 'غير محدد' : 'محدد') . "\n";
    
    $isAvailable = $apiService->isApiAvailable();
    echo "   API Available: " . ($isAvailable ? '✅ نعم' : '❌ لا') . "\n\n";
    
    if (!$isAvailable) {
        echo "❌ API غير متاح!\n";
        echo "   تحقق من:\n";
        echo "   - ONLINE_URL صحيح\n";
        echo "   - السيرفر متاح\n";
        echo "   - SYNC_API_TOKEN صحيح\n\n";
        exit(1);
    }
    
    // 3. اختبار DatabaseSyncService
    echo "3. اختبار DatabaseSyncService:\n";
    $syncService = new DatabaseSyncService();
    
    // استخدام reflection للوصول إلى protected properties
    $reflection = new ReflectionClass($syncService);
    $useApiProperty = $reflection->getProperty('useApi');
    $useApiProperty->setAccessible(true);
    $useApiValue = $useApiProperty->getValue($syncService);
    
    echo "   useApi: " . ($useApiValue ? '✅ true' : '❌ false') . "\n";
    
    if (!$useApiValue) {
        echo "❌ DatabaseSyncService لا يستخدم API!\n\n";
        exit(1);
    }
    
    // 4. اختبار sync بسيط
    echo "4. اختبار sync بسيط:\n";
    $stats = $syncService->getQueueStats();
    echo "   Pending: {$stats['pending']}\n";
    echo "   Synced: {$stats['synced']}\n";
    echo "   Failed: {$stats['failed']}\n\n";
    
    if ($stats['pending'] > 0) {
        echo "⚠️  يوجد {$stats['pending']} سجل(ات) في انتظار المزامنة\n";
        echo "   جرب المزامنة الآن...\n\n";
    } else {
        echo "✅ لا توجد سجلات في انتظار المزامنة\n\n";
    }
    
    echo "========================================\n";
    echo "✅ جميع الاختبارات نجحت!\n";
    echo "========================================\n\n";
    
} catch (\Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}

