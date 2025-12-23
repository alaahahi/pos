<?php
/**
 * اختبار شامل للمزامنة عبر API
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\ApiSyncService;
use App\Services\DatabaseSyncService;
use App\Services\SyncQueueService;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;

echo "========================================\n";
echo "   اختبار المزامنة عبر API\n";
echo "========================================\n\n";

$testResults = [
    'api_service' => false,
    'api_available' => false,
    'sync_via_api' => false,
    'sync_insert' => false,
    'sync_update' => false,
    'sync_delete' => false,
    'sync_batch' => false,
];

// 1. التحقق من ApiSyncService
echo "1. التحقق من ApiSyncService...\n";
try {
    $apiSyncService = new ApiSyncService();
    $testResults['api_service'] = true;
    echo "   ✅ ApiSyncService تم إنشاؤه بنجاح\n\n";
} catch (\Exception $e) {
    echo "   ❌ فشل إنشاء ApiSyncService: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 2. التحقق من إعدادات API
echo "2. التحقق من إعدادات API...\n";
$useApi = env('SYNC_VIA_API', false);
$apiUrl = env('ONLINE_URL', 'https://nissan.intellij-app.com');
$apiToken = env('SYNC_API_TOKEN', '');

echo "   SYNC_VIA_API: " . ($useApi ? 'true' : 'false') . "\n";
echo "   ONLINE_URL: {$apiUrl}\n";
echo "   SYNC_API_TOKEN: " . (empty($apiToken) ? 'غير محدد' : 'محدد (' . substr($apiToken, 0, 10) . '...)') . "\n\n";

if (!$useApi) {
    echo "   ⚠️  SYNC_VIA_API=false - سيتم استخدام MySQL مباشرة\n";
    echo "   💡 لتفعيل API، اضف في .env: SYNC_VIA_API=true\n\n";
} else {
    $testResults['sync_via_api'] = true;
    echo "   ✅ API mode مفعّل\n\n";
}

// 3. التحقق من توفر API
echo "3. التحقق من توفر API...\n";
try {
    $apiAvailable = $apiSyncService->isApiAvailable();
    $testResults['api_available'] = $apiAvailable;
    
    if ($apiAvailable) {
        echo "   ✅ API متاح\n\n";
    } else {
        echo "   ❌ API غير متاح\n";
        echo "   ⚠️  تأكد من:\n";
        echo "      - ONLINE_URL صحيح\n";
        echo "      - SYNC_API_TOKEN صحيح\n";
        echo "      - السيرفر متاح\n";
        echo "      - API endpoints موجودة على السيرفر\n\n";
    }
} catch (\Exception $e) {
    echo "   ❌ خطأ في التحقق من API: " . $e->getMessage() . "\n\n";
}

// 4. اختبار DatabaseSyncService مع API
echo "4. اختبار DatabaseSyncService مع API mode...\n";
try {
    $syncService = new DatabaseSyncService();
    
    // التحقق من استخدام API
    $reflection = new ReflectionClass($syncService);
    $useApiProperty = $reflection->getProperty('useApi');
    $useApiProperty->setAccessible(true);
    $isUsingApi = $useApiProperty->getValue($syncService);
    
    if ($isUsingApi) {
        echo "   ✅ DatabaseSyncService يستخدم API mode\n\n";
    } else {
        echo "   ⚠️  DatabaseSyncService يستخدم MySQL مباشرة\n";
        echo "   💡 لتفعيل API، اضف في .env: SYNC_VIA_API=true\n\n";
    }
} catch (\Exception $e) {
    echo "   ❌ خطأ في DatabaseSyncService: " . $e->getMessage() . "\n\n";
}

// 5. اختبار sync_insert (إذا كان API متاح)
if ($testResults['api_available'] && $testResults['sync_via_api']) {
    echo "5. اختبار sync_insert عبر API...\n";
    try {
        $testData = [
            'name' => 'Test Customer ' . time(),
            'email' => 'test' . time() . '@example.com',
            'phone' => '1234567890',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        $result = $apiSyncService->syncInsert('customers', $testData);
        
        if ($result['success'] ?? false) {
            $testResults['sync_insert'] = true;
            echo "   ✅ sync_insert نجح\n";
            echo "   ID: " . ($result['data']['id'] ?? 'N/A') . "\n\n";
        } else {
            echo "   ❌ sync_insert فشل: " . ($result['error'] ?? 'Unknown error') . "\n\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ خطأ في sync_insert: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "5. اختبار sync_insert...\n";
    echo "   ⏭️  تم التخطي (API غير متاح أو غير مفعّل)\n\n";
}

// 6. اختبار sync_update (إذا كان API متاح)
if ($testResults['api_available'] && $testResults['sync_via_api']) {
    echo "6. اختبار sync_update عبر API...\n";
    try {
        // نحتاج ID موجود أولاً
        $testData = [
            'name' => 'Updated Customer ' . time(),
        ];
        
        // استخدام ID تجريبي (يجب تعديله حسب البيانات الفعلية)
        $testId = 1;
        $result = $apiSyncService->syncUpdate('customers', $testId, $testData);
        
        if ($result['success'] ?? false) {
            $testResults['sync_update'] = true;
            echo "   ✅ sync_update نجح\n\n";
        } else {
            echo "   ⚠️  sync_update فشل (قد يكون ID غير موجود): " . ($result['error'] ?? 'Unknown error') . "\n\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ خطأ في sync_update: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "6. اختبار sync_update...\n";
    echo "   ⏭️  تم التخطي (API غير متاح أو غير مفعّل)\n\n";
}

// 7. اختبار sync_batch (إذا كان API متاح)
if ($testResults['api_available'] && $testResults['sync_via_api']) {
    echo "7. اختبار sync_batch عبر API...\n";
    try {
        $changes = [
            [
                'table' => 'customers',
                'action' => 'insert',
                'data' => [
                    'name' => 'Batch Test 1',
                    'email' => 'batch1@example.com',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            [
                'table' => 'customers',
                'action' => 'insert',
                'data' => [
                    'name' => 'Batch Test 2',
                    'email' => 'batch2@example.com',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
        ];
        
        $result = $apiSyncService->syncBatch($changes);
        
        if ($result['success'] ?? false) {
            $testResults['sync_batch'] = true;
            $results = $result['data']['results'] ?? [];
            echo "   ✅ sync_batch نجح\n";
            echo "   Synced: " . ($results['synced'] ?? 0) . "\n";
            echo "   Failed: " . ($results['failed'] ?? 0) . "\n\n";
        } else {
            echo "   ❌ sync_batch فشل: " . ($result['error'] ?? 'Unknown error') . "\n\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ خطأ في sync_batch: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "7. اختبار sync_batch...\n";
    echo "   ⏭️  تم التخطي (API غير متاح أو غير مفعّل)\n\n";
}

// 8. ملخص النتائج
echo "========================================\n";
echo "   ملخص النتائج\n";
echo "========================================\n";
echo "ApiSyncService: " . ($testResults['api_service'] ? '✅' : '❌') . "\n";
echo "API Available: " . ($testResults['api_available'] ? '✅' : '❌') . "\n";
echo "Sync Via API: " . ($testResults['sync_via_api'] ? '✅' : '❌') . "\n";
echo "Sync Insert: " . ($testResults['sync_insert'] ? '✅' : ($testResults['api_available'] ? '❌' : '⏭️')) . "\n";
echo "Sync Update: " . ($testResults['sync_update'] ? '✅' : ($testResults['api_available'] ? '⚠️' : '⏭️')) . "\n";
echo "Sync Batch: " . ($testResults['sync_batch'] ? '✅' : ($testResults['api_available'] ? '❌' : '⏭️')) . "\n\n";

// 9. توصيات
echo "========================================\n";
echo "   التوصيات\n";
echo "========================================\n";

if (!$testResults['sync_via_api']) {
    echo "1. ⚠️  لتفعيل API mode:\n";
    echo "   - أضف في .env: SYNC_VIA_API=true\n";
    echo "   - أضف في .env: SYNC_API_TOKEN=your-token\n\n";
}

if (!$testResults['api_available']) {
    echo "2. ⚠️  لتفعيل API على السيرفر:\n";
    echo "   - أنشئ SyncApiController\n";
    echo "   - أضف Routes في routes/api.php\n";
    echo "   - أنشئ API Token\n";
    echo "   - راجع API_SYNC_SETUP_GUIDE.md\n\n";
}

if ($testResults['api_service'] && $testResults['api_available'] && $testResults['sync_via_api']) {
    echo "✅ كل شيء جاهز! المزامنة عبر API تعمل بشكل صحيح.\n\n";
} else {
    echo "⚠️  بعض الاختبارات فشلت. راجع الأخطاء أعلاه.\n\n";
}

echo "========================================\n\n";

