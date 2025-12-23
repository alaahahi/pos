<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ApiSyncService
{
    protected $apiUrl;
    protected $apiToken;
    protected $timeout;

    public function __construct()
    {
        $this->apiUrl = env('ONLINE_URL', 'https://nissan.intellij-app.com');
        $this->apiToken = env('SYNC_API_TOKEN', '');
        $this->timeout = (int) env('SYNC_API_TIMEOUT', 10); // 10 ثواني timeout للـ health check (أقل من 30)
    }

    /**
     * التحقق من توفر API باستخدام sync-health endpoint
     */
    public function isApiAvailable(): bool
    {
        try {
            // محاولة الاتصال بـ API health endpoint مع timeout أقصر (5 ثواني)
            // TODO: إزالة هذا التعديل بعد التجريب - إعادة تفعيل التوكن
            $httpRequest = Http::timeout(5)
                ->connectTimeout(3); // timeout للاتصال (3 ثواني)
            
            // إضافة التوكن فقط إذا كان موجوداً (للتجريب - يمكن تعطيله مؤقتاً)
            if (!empty($this->apiToken)) {
                $httpRequest->withToken($this->apiToken);
            }
            
            $response = $httpRequest->get("{$this->apiUrl}/api/sync-monitor/sync-health");

            // التحقق من أن الاستجابة ناجحة (200 OK)
            if ($response->successful()) {
                $healthData = $response->json();
                
                // التحقق من أن overall_status هو "ok" أو "warning" (ليس "issue")
                if (isset($healthData['success']) && $healthData['success'] === true) {
                    $overallStatus = $healthData['health']['overall_status'] ?? 'unknown';
                    
                    // إذا كان overall_status "ok" أو "warning"، API متاح
                    if (in_array($overallStatus, ['ok', 'warning'])) {
                        Log::debug('API health check passed', [
                            'overall_status' => $overallStatus,
                            'url' => $this->apiUrl,
                        ]);
                        return true;
                    }
                    
                    // إذا كان overall_status "issue"، API متاح لكن به مشاكل
                    Log::warning('API health check: issues detected', [
                        'overall_status' => $overallStatus,
                        'issues' => $healthData['health']['issues'] ?? [],
                        'url' => $this->apiUrl,
                    ]);
                    return true; // API متاح لكن به مشاكل
                }
                
                // إذا كان success = false، API متاح لكن health check فشل
                Log::warning('API health check failed', [
                    'response' => $healthData,
                    'url' => $this->apiUrl,
                ]);
                return true; // API متاح لكن health check فشل
            }
            
            // إذا كان status code غير 200، API متاح لكن endpoint غير متاح
            Log::warning('API health check: non-200 status', [
                'status' => $response->status(),
                'url' => $this->apiUrl,
            ]);
            return $response->status() !== 0; // 0 = connection failed
            
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            
            // إذا كان الخطأ connection error، API غير متاح
            if (str_contains($errorMsg, 'Connection') || 
                str_contains($errorMsg, 'timeout') ||
                str_contains($errorMsg, 'resolve') ||
                str_contains($errorMsg, 'getaddrinfo') ||
                str_contains($errorMsg, 'cURL error') ||
                str_contains($errorMsg, 'Failed to connect')) {
                Log::warning('Sync API not available (connection error)', [
                    'error' => $errorMsg,
                    'url' => $this->apiUrl,
                ]);
                return false;
            }
            
            // خطأ آخر - API متاح لكن حدث خطأ
            Log::warning('API health check exception', [
                'error' => $errorMsg,
                'url' => $this->apiUrl,
            ]);
            return true; // نفترض أن API متاح
        }
    }

    /**
     * مزامنة insert عبر API
     */
    public function syncInsert(string $tableName, array $data): array
    {
        try {
            $recordId = $data['id'] ?? 0;
            
            Log::info('API sync insert attempt', [
                'table' => $tableName,
                'record_id' => $recordId,
                'api_url' => $this->apiUrl,
                'has_token' => !empty($this->apiToken),
            ]);
            
            // TODO: إزالة هذا التعديل بعد التجريب - إعادة تفعيل التوكن
            $httpRequest = Http::timeout($this->timeout);
            if (!empty($this->apiToken)) {
                $httpRequest->withToken($this->apiToken);
            }
            
            $response = $httpRequest->post("{$this->apiUrl}/api/sync-monitor/api-sync", [
                    'table_name' => $tableName,
                    'record_id' => $recordId,
                    'action' => 'insert',
                    'data' => $data,
                ]);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('API sync insert succeeded', [
                    'table' => $tableName,
                    'record_id' => $recordId,
                    'response' => $result,
                ]);
                return [
                    'success' => $result['success'] ?? true,
                    'data' => $result,
                ];
            }

            $errorMsg = $response->json('message', 'Unknown error');
            Log::warning('API sync insert failed', [
                'table' => $tableName,
                'record_id' => $recordId,
                'status' => $response->status(),
                'error' => $errorMsg,
            ]);

            return [
                'success' => false,
                'error' => $errorMsg,
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to sync insert via API: {$tableName}", [
                'error' => $e->getMessage(),
                'table' => $tableName,
                'record_id' => $recordId ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * مزامنة update عبر API
     */
    public function syncUpdate(string $tableName, int $recordId, array $data): array
    {
        try {
            Log::info('API sync update attempt', [
                'table' => $tableName,
                'record_id' => $recordId,
                'api_url' => $this->apiUrl,
                'has_token' => !empty($this->apiToken),
            ]);
            
            // TODO: إزالة هذا التعديل بعد التجريب - إعادة تفعيل التوكن
            $httpRequest = Http::timeout($this->timeout);
            if (!empty($this->apiToken)) {
                $httpRequest->withToken($this->apiToken);
            }
            
            $response = $httpRequest->post("{$this->apiUrl}/api/sync-monitor/api-sync", [
                    'table_name' => $tableName,
                    'record_id' => $recordId,
                    'action' => 'update',
                    'data' => $data,
                ]);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('API sync update succeeded', [
                    'table' => $tableName,
                    'record_id' => $recordId,
                    'response' => $result,
                ]);
                return [
                    'success' => $result['success'] ?? true,
                    'data' => $result,
                ];
            }

            $errorMsg = $response->json('message', 'Unknown error');
            Log::warning('API sync update failed', [
                'table' => $tableName,
                'record_id' => $recordId,
                'status' => $response->status(),
                'error' => $errorMsg,
            ]);

            return [
                'success' => false,
                'error' => $errorMsg,
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to sync update via API: {$tableName}", [
                'error' => $e->getMessage(),
                'table' => $tableName,
                'id' => $recordId,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * مزامنة delete عبر API
     */
    public function syncDelete(string $tableName, int $recordId): array
    {
        try {
            // TODO: إزالة هذا التعديل بعد التجريب - إعادة تفعيل التوكن
            $httpRequest = Http::timeout($this->timeout);
            if (!empty($this->apiToken)) {
                $httpRequest->withToken($this->apiToken);
            }
            
            $response = $httpRequest->post("{$this->apiUrl}/api/sync-monitor/api-sync", [
                    'table_name' => $tableName,
                    'record_id' => $recordId,
                    'action' => 'delete',
                ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => $result['success'] ?? true,
                    'data' => $result,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Unknown error'),
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to sync delete via API: {$tableName}", [
                'error' => $e->getMessage(),
                'table' => $tableName,
                'id' => $recordId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * مزامنة batch عبر API (أكثر كفاءة)
     */
    public function syncBatch(array $changes): array
    {
        try {
            // TODO: إزالة هذا التعديل بعد التجريب - إعادة تفعيل التوكن
            $httpRequest = Http::timeout($this->timeout * 2); // timeout أطول للـ batch
            if (!empty($this->apiToken)) {
                $httpRequest->withToken($this->apiToken);
            }
            
            $response = $httpRequest->post("{$this->apiUrl}/api/sync/batch", [
                    'changes' => $changes,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Unknown error'),
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to sync batch via API", [
                'error' => $e->getMessage(),
                'changes_count' => count($changes),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * الحصول على server ID من local ID (لحل تعارضات ID)
     */
    public function getServerId(string $tableName, int $localId): ?int
    {
        try {
            $cacheKey = "sync_id_mapping_{$tableName}_{$localId}";
            
            // التحقق من Cache أولاً
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            // TODO: إزالة هذا التعديل بعد التجريب - إعادة تفعيل التوكن
            $httpRequest = Http::timeout(10);
            if (!empty($this->apiToken)) {
                $httpRequest->withToken($this->apiToken);
            }
            
            $response = $httpRequest->get("{$this->apiUrl}/api/sync/mapping", [
                    'table' => $tableName,
                    'local_id' => $localId,
                ]);

            if ($response->successful()) {
                $serverId = $response->json('server_id');
                if ($serverId) {
                    // حفظ في Cache
                    Cache::put($cacheKey, $serverId, now()->addHours(24));
                    return $serverId;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::warning("Failed to get server ID via API", [
                'error' => $e->getMessage(),
                'table' => $tableName,
                'local_id' => $localId,
            ]);
            return null;
        }
    }
}

