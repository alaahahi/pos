<?php

/**
 * اختبار فحص API Health
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ApiSyncService;
use Illuminate\Support\Facades\Http;

echo "========================================\n";
echo "   اختبار فحص API Health\n";
echo "========================================\n\n";

// 1. اختبار ApiSyncService::isApiAvailable()
echo "1. اختبار ApiSyncService::isApiAvailable()\n";
echo "   ----------------------------------------\n";

$apiSyncService = new ApiSyncService();
$isAvailable = $apiSyncService->isApiAvailable();

echo "   النتيجة: " . ($isAvailable ? "✅ متاح" : "❌ غير متاح") . "\n\n";

// 2. اختبار مباشر لـ sync-health endpoint
echo "2. اختبار مباشر لـ sync-health endpoint\n";
echo "   ----------------------------------------\n";

$onlineUrl = env('ONLINE_URL', 'https://nissan.intellij-app.com');
$apiToken = env('SYNC_API_TOKEN', '');

echo "   ONLINE_URL: {$onlineUrl}\n";
echo "   API Token: " . (!empty($apiToken) ? "✅ محدد" : "❌ غير محدد") . "\n\n";

try {
    $response = Http::timeout(10)
        ->withToken($apiToken)
        ->get("{$onlineUrl}/api/sync-monitor/sync-health");
    
    $statusCode = $response->status();
    $responseData = $response->json();
    
    echo "   HTTP Status: {$statusCode}\n";
    
    if ($response->successful()) {
        echo "   ✅ الاتصال ناجح\n\n";
        
        if (isset($responseData['success']) && $responseData['success'] === true) {
            $health = $responseData['health'] ?? [];
            $overallStatus = $health['overall_status'] ?? 'unknown';
            
            echo "   📊 الحالة العامة: {$overallStatus}\n";
            echo "   💬 الرسالة: " . ($health['message'] ?? 'N/A') . "\n\n";
            
            // API Sync
            if (isset($health['api_sync'])) {
                echo "   🔌 API Sync:\n";
                echo "      مفعّل: " . ($health['api_sync']['enabled'] ? "✅" : "❌") . "\n";
                echo "      ONLINE_URL: " . ($health['api_sync']['online_url'] ?? 'N/A') . "\n";
                echo "      API Token: " . ($health['api_sync']['api_token_set'] ? "✅" : "❌") . "\n\n";
            }
            
            // API Service
            if (isset($health['api_service'])) {
                echo "   🌐 API Service:\n";
                echo "      متاح: " . ($health['api_service']['available'] ? "✅" : "❌") . "\n";
                echo "      الحالة: " . ($health['api_service']['status'] ?? 'N/A') . "\n\n";
            }
            
            // Issues
            if (isset($health['issues']) && count($health['issues']) > 0) {
                echo "   ⚠️  المشاكل:\n";
                foreach ($health['issues'] as $issue) {
                    echo "      - {$issue}\n";
                }
                echo "\n";
            }
            
            // Warnings
            if (isset($health['warnings']) && count($health['warnings']) > 0) {
                echo "   ⚠️  التحذيرات:\n";
                foreach ($health['warnings'] as $warning) {
                    echo "      - {$warning}\n";
                }
                echo "\n";
            }
        } else {
            echo "   ❌ الاستجابة غير صحيحة\n";
            echo "   Response: " . json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        }
    } else {
        echo "   ❌ الاتصال فشل\n";
        echo "   Response: " . $response->body() . "\n\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

// 3. اختبار local endpoint (إذا كان ONLINE_URL = local)
echo "3. اختبار local endpoint\n";
echo "   ----------------------------------------\n";

$localUrl = env('LOCAL_URL', 'http://127.0.0.1:8000');
echo "   LOCAL_URL: {$localUrl}\n\n";

try {
    $response = Http::timeout(5)
        ->get("{$localUrl}/api/sync-monitor/sync-health");
    
    $statusCode = $response->status();
    
    echo "   HTTP Status: {$statusCode}\n";
    
    if ($response->successful()) {
        echo "   ✅ الاتصال ناجح\n";
        $responseData = $response->json();
        echo "   Response: " . json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    } else {
        echo "   ❌ الاتصال فشل\n";
        echo "   Response: " . $response->body() . "\n\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n\n";
}

echo "========================================\n";
echo "   انتهى الاختبار\n";
echo "========================================\n";

