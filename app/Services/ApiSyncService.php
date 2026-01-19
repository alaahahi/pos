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
     * مع Cache لمدة دقيقتين لتقليل عدد الطلبات
     */
    public function isApiAvailable(): bool
    {
        // استخدام Cache لمدة دقيقتين (120 ثانية)
        $cacheKey = 'api_health_check_' . md5($this->apiUrl);
        
        return Cache::remember($cacheKey, 120, function () {
            return $this->performHealthCheck();
        });
    }
    
    /**
     * تنفيذ فحص الاتصال الفعلي
     */
    protected function performHealthCheck(): bool
    {
        try {
            // محاولة الاتصال بـ API - نستخدم endpoint أبسط للتجريب
            // TODO: إزالة هذا التعديل بعد التجريب - إعادة تفعيل التوكن
            $httpRequest = Http::timeout(5)
                ->connectTimeout(3); // timeout للاتصال (3 ثواني)
            
            // إضافة التوكن فقط إذا كان موجوداً (للتجريب - يمكن تعطيله مؤقتاً)
            if (!empty($this->apiToken)) {
                $httpRequest->withToken($this->apiToken);
            }
            
            // استخدام endpoint أبسط للتحقق من توفر API (لا يحتاج authentication)
            // هذا endpoint يعمل على السيرفر ويتحقق من قاعدة البيانات
            $checkUrl = "{$this->apiUrl}/api/check-database-connection";
            $response = $httpRequest->get($checkUrl);
            $usedEndpoint = 'check-database-connection';
            
            // إذا فشل endpoint الأول (404)، نجرب sync-health كـ fallback
            if ($response->status() === 404) {
                Log::debug('check-database-connection not found, trying sync-health', [
                    'url' => $this->apiUrl,
                ]);
                $checkUrl = "{$this->apiUrl}/api/sync-monitor/sync-health";
                $response = $httpRequest->get($checkUrl);
                $usedEndpoint = 'sync-health';
            }

            $statusCode = $response->status();
            
            // إذا وصلنا للـ API (حتى لو كان 401, 403, 404) فهذا يعني أن API متاح
            // الخطأ 0 يعني connection failed (السيرفر غير متاح)
            if ($statusCode > 0) {
                // API متاح - وصلنا للسيرفر
                if ($response->successful()) {
                    $responseData = $response->json();
                    
                    // التعامل مع check-database-connection endpoint
                    if ($usedEndpoint === 'check-database-connection') {
                        if (isset($responseData['success']) && $responseData['success'] === true) {
                            $connected = $responseData['connection']['connected'] ?? false;
                            if ($connected) {
                                // تم إزالة Log::debug لتقليل الـ logs
                                return true;
                            }
                        }
                        // إذا كان success = false أو connected = false
                        Log::warning('API health check: database connection check failed', [
                            'response' => $responseData,
                            'url' => $this->apiUrl,
                        ]);
                        return true; // API متاح لكن connection check فشل
                    }
                    
                    // التعامل مع sync-health endpoint
                    if ($usedEndpoint === 'sync-health') {
                        if (isset($responseData['success']) && $responseData['success'] === true) {
                            $overallStatus = $responseData['health']['overall_status'] ?? 'unknown';
                            
                            // إذا كان overall_status "ok" أو "warning"، API متاح
                            if (in_array($overallStatus, ['ok', 'warning'])) {
                                // تم إزالة Log::debug لتقليل الـ logs
                                return true;
                            }
                            
                            // إذا كان overall_status "issue"، API متاح لكن به مشاكل
                            Log::warning('API health check: issues detected', [
                                'overall_status' => $overallStatus,
                                'issues' => $responseData['health']['issues'] ?? [],
                                'url' => $this->apiUrl,
                            ]);
                            return true; // API متاح لكن به مشاكل
                        }
                        
                        // إذا كان success = false
                        Log::warning('API health check failed', [
                            'response' => $responseData,
                            'url' => $this->apiUrl,
                        ]);
                        return true; // API متاح لكن health check فشل
                    }
                } else {
                    // Status code موجود (401, 403, 404, 500, etc) - يعني API متاح لكن به مشكلة
                    // هذا أفضل من connection failed - على الأقل السيرفر يعمل
                    $message = match($statusCode) {
                        401 => 'Authentication required',
                        403 => 'Access forbidden',
                        404 => 'Endpoint not found',
                        default => 'Server error'
                    };
                    
                    // تقليل اللوغات - فقط warning عند وجود مشكلة
                    if ($statusCode >= 500) {
                        Log::warning('API health check: server error', [
                            'status' => $statusCode,
                            'endpoint' => $usedEndpoint,
                            'message' => $message,
                        ]);
                    }
                    return true; // API متاح (السيرفر يعمل) لكن يحتاج authentication أو به مشكلة
                }
            }
            
            // Status code = 0 يعني connection failed
            Log::warning('API health check: connection failed (status 0)', [
                'url' => $this->apiUrl,
            ]);
            return false; // API غير متاح - connection failed
            
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

            $statusCode = $response->status();
            $responseBody = $response->json();
            
            Log::info('API sync insert response', [
                'table' => $tableName,
                'record_id' => $recordId,
                'status_code' => $statusCode,
                'response_body' => $responseBody,
            ]);

            if ($response->successful()) {
                $success = $responseBody['success'] ?? true;
                
                if (!$success) {
                    $errorMsg = $responseBody['message'] ?? 'API returned success: false';
                    Log::warning('API sync insert failed (success: false)', [
                        'table' => $tableName,
                        'record_id' => $recordId,
                        'error' => $errorMsg,
                        'response' => $responseBody,
                    ]);
                    
                    return [
                        'success' => false,
                        'error' => $errorMsg,
                        'data' => $responseBody,
                    ];
                }
                
                Log::info('API sync insert succeeded', [
                    'table' => $tableName,
                    'record_id' => $recordId,
                    'response' => $responseBody,
                ]);
                
                return [
                    'success' => true,
                    'data' => $responseBody,
                ];
            }

            // إذا كان 429 (Too Many Requests)، رفع Exception لإيقاف المحاولات
            if ($statusCode === 429) {
                $errorMsg = 'HTTP 429 - Too Many Requests. Server rate limit exceeded.';
                Log::warning('API sync: Rate limit exceeded (429)', [
                    'table' => $tableName,
                    'record_id' => $recordId,
                ]);
                throw new \Exception($errorMsg);
            }
            
            $errorMsg = $responseBody['message'] ?? 'HTTP ' . $statusCode;
            Log::warning('API sync insert failed (HTTP error)', [
                'table' => $tableName,
                'record_id' => $recordId,
                'status' => $statusCode,
                'error' => $errorMsg,
                'response' => $responseBody,
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

            $statusCode = $response->status();
            $responseBody = $response->json();
            
            Log::info('API sync update response', [
                'table' => $tableName,
                'record_id' => $recordId,
                'status_code' => $statusCode,
                'response_body' => $responseBody,
            ]);

            if ($response->successful()) {
                $success = $responseBody['success'] ?? true;
                
                if (!$success) {
                    $errorMsg = $responseBody['message'] ?? 'API returned success: false';
                    Log::warning('API sync update failed (success: false)', [
                        'table' => $tableName,
                        'record_id' => $recordId,
                        'error' => $errorMsg,
                        'response' => $responseBody,
                    ]);
                    
                    return [
                        'success' => false,
                        'error' => $errorMsg,
                        'data' => $responseBody,
                    ];
                }
                
                Log::info('API sync update succeeded', [
                    'table' => $tableName,
                    'record_id' => $recordId,
                    'response' => $responseBody,
                ]);
                
                return [
                    'success' => true,
                    'data' => $responseBody,
                ];
            }

            // إذا كان 429 (Too Many Requests)، رفع Exception لإيقاف المحاولات
            if ($statusCode === 429) {
                $errorMsg = 'HTTP 429 - Too Many Requests. Server rate limit exceeded.';
                Log::warning('API sync: Rate limit exceeded (429)', [
                    'table' => $tableName,
                    'record_id' => $recordId,
                ]);
                throw new \Exception($errorMsg);
            }
            
            $errorMsg = $responseBody['message'] ?? 'HTTP ' . $statusCode;
            Log::warning('API sync update failed (HTTP error)', [
                'table' => $tableName,
                'record_id' => $recordId,
                'status' => $statusCode,
                'error' => $errorMsg,
                'response' => $responseBody,
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
            Log::info('API sync delete attempt', [
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
            
            $url = "{$this->apiUrl}/api/sync-monitor/api-sync";
            $payload = [
                'table_name' => $tableName,
                'record_id' => $recordId,
                'action' => 'delete',
            ];
            
            Log::debug('Sending delete request to API', [
                'url' => $url,
                'payload' => $payload,
            ]);
            
            $response = $httpRequest->post($url, $payload);

            $statusCode = $response->status();
            $responseBody = $response->json();
            
            Log::info('API sync delete response', [
                'table' => $tableName,
                'record_id' => $recordId,
                'status_code' => $statusCode,
                'response_body' => $responseBody,
            ]);

            if ($response->successful()) {
                $success = $responseBody['success'] ?? true;
                
                if (!$success) {
                    $errorMsg = $responseBody['message'] ?? 'API returned success: false';
                    Log::warning('API sync delete failed (success: false)', [
                        'table' => $tableName,
                        'record_id' => $recordId,
                        'error' => $errorMsg,
                        'response' => $responseBody,
                    ]);
                    
                    return [
                        'success' => false,
                        'error' => $errorMsg,
                        'data' => $responseBody,
                    ];
                }
                
                Log::info('API sync delete succeeded', [
                    'table' => $tableName,
                    'record_id' => $recordId,
                    'response' => $responseBody,
                ]);
                
                return [
                    'success' => true,
                    'data' => $responseBody,
                ];
            }

            $errorMsg = $responseBody['message'] ?? 'HTTP ' . $statusCode;
            Log::warning('API sync delete failed (HTTP error)', [
                'table' => $tableName,
                'record_id' => $recordId,
                'status' => $statusCode,
                'error' => $errorMsg,
                'response' => $responseBody,
            ]);

            return [
                'success' => false,
                'error' => $errorMsg,
                'status' => $statusCode,
                'data' => $responseBody,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to sync delete via API: {$tableName}", [
                'error' => $e->getMessage(),
                'table' => $tableName,
                'record_id' => $recordId,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * جلب البيانات من السيرفر (للمقارنة)
     */
    public function getTableData(string $tableName, int $limit = 1000, int $offset = 0): array
    {
        try {
            Log::debug('Fetching table data from server', [
                'table' => $tableName,
                'limit' => $limit,
                'offset' => $offset,
            ]);
            
            $httpRequest = Http::timeout($this->timeout * 2); // timeout أطول لجلب البيانات
            if (!empty($this->apiToken)) {
                $httpRequest->withToken($this->apiToken);
            }
            
            $url = "{$this->apiUrl}/api/sync-monitor/table/{$tableName}";
            $response = $httpRequest->get($url, [
                'limit' => $limit,
                'offset' => $offset,
            ]);

            $statusCode = $response->status();
            $responseBody = $response->json();
            
            if ($response->successful() && ($responseBody['success'] ?? false)) {
                Log::debug('Table data fetched successfully', [
                    'table' => $tableName,
                    'count' => count($responseBody['data'] ?? []),
                ]);
                
                return [
                    'success' => true,
                    'data' => $responseBody['data'] ?? [],
                    'total' => $responseBody['total'] ?? 0,
                    'columns' => $responseBody['columns'] ?? [],
                ];
            }

            $errorMsg = $responseBody['message'] ?? 'HTTP ' . $statusCode;
            Log::warning('Failed to fetch table data from server', [
                'table' => $tableName,
                'status' => $statusCode,
                'error' => $errorMsg,
            ]);

            return [
                'success' => false,
                'error' => $errorMsg,
                'status' => $statusCode,
                'data' => [],
            ];
        } catch (\Exception $e) {
            Log::error("Failed to fetch table data from server: {$tableName}", [
                'error' => $e->getMessage(),
                'table' => $tableName,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [],
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

