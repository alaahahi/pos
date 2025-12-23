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
        $this->timeout = env('SYNC_API_TIMEOUT', 30); // 30 ثانية timeout
    }

    /**
     * التحقق من توفر API
     */
    public function isApiAvailable(): bool
    {
        try {
            $response = Http::timeout(5)
                ->withToken($this->apiToken)
                ->get("{$this->apiUrl}/api/sync/health");

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Sync API not available', [
                'error' => $e->getMessage(),
                'url' => $this->apiUrl,
            ]);
            return false;
        }
    }

    /**
     * مزامنة insert عبر API
     */
    public function syncInsert(string $tableName, array $data): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiToken)
                ->post("{$this->apiUrl}/api/sync/insert", [
                    'table' => $tableName,
                    'data' => $data,
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
            Log::error("Failed to sync insert via API: {$tableName}", [
                'error' => $e->getMessage(),
                'table' => $tableName,
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
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiToken)
                ->put("{$this->apiUrl}/api/sync/update", [
                    'table' => $tableName,
                    'id' => $recordId,
                    'data' => $data,
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
            Log::error("Failed to sync update via API: {$tableName}", [
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
     * مزامنة delete عبر API
     */
    public function syncDelete(string $tableName, int $recordId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiToken)
                ->delete("{$this->apiUrl}/api/sync/delete", [
                    'table' => $tableName,
                    'id' => $recordId,
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
            $response = Http::timeout($this->timeout * 2) // timeout أطول للـ batch
                ->withToken($this->apiToken)
                ->post("{$this->apiUrl}/api/sync/batch", [
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

            $response = Http::timeout(10)
                ->withToken($this->apiToken)
                ->get("{$this->apiUrl}/api/sync/mapping", [
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

