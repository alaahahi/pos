<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Services\ApiSyncService;

class DatabaseSyncService
{
    protected $syncQueueService;
    protected $idMappingService;
    protected $useApi;
    protected $apiSyncService;

    public function __construct()
    {
        $this->syncQueueService = new SyncQueueService();
        $this->idMappingService = new SyncIdMappingService();
        
        // إجبار استخدام API للمزامنة - لا اتصال مباشر بـ MySQL
        // تحويل string "true" إلى boolean
        $syncViaApi = env('SYNC_VIA_API', false);
        $this->useApi = filter_var($syncViaApi, FILTER_VALIDATE_BOOLEAN);
        
        // إذا كان SYNC_VIA_API=true، إجبار استخدام API فقط
        if ($this->useApi) {
            $this->apiSyncService = new ApiSyncService();
            // تم إزالة Log::debug لأن DatabaseSyncService يتم إنشاؤه كثيراً (كل 5 ثواني من SyncStatusBar)
            // مما يسبب تكرار الرسائل في اللوغ
        } else {
            // حتى لو كان SYNC_VIA_API=false، نحذر المستخدم
            Log::warning('SYNC_VIA_API=false - سيتم استخدام MySQL مباشرة (غير موصى به)');
        }
    }

    /**
     * مزامنة ذكية: مزامنة التغييرات من sync_queue فقط
     * مع Batch Processing و Retry Mechanism محسّن
     */
    public function syncPendingChanges(string $tableName = null, int $limit = 100, int $timeout = 300): array
    {
        $results = [
            'synced' => 0,
            'failed' => 0,
            'errors' => [],
            'started_at' => now()->toDateTimeString(),
        ];

        $startTime = microtime(true);

        try {
            // إجبار استخدام API فقط - لا اتصال مباشر بـ MySQL
            if (!$this->useApi) {
                $errorMsg = 'SYNC_VIA_API غير مفعّل - يجب تفعيل API Sync. لا يمكن استخدام MySQL مباشرة.';
                $results['errors'][] = $errorMsg;
                $results['message'] = $errorMsg;
                Log::error('Sync blocked: SYNC_VIA_API must be enabled', [
                    'pending_count' => count($this->syncQueueService->getPendingChanges($tableName, $limit))
                ]);
                return $results;
            }
            
            // التحقق من توفر API فقط
            $apiAvailable = $this->apiSyncService->isApiAvailable();
            Log::info('API availability check', [
                'api_available' => $apiAvailable,
                'online_url' => env('ONLINE_URL'),
                'api_token_set' => !empty(env('SYNC_API_TOKEN')),
            ]);
            
            if (!$apiAvailable) {
                $errorMsg = 'API غير متاح - لا يمكن المزامنة. يرجى التحقق من الاتصال بالسيرفر.';
                $results['errors'][] = $errorMsg;
                $results['message'] = $errorMsg;
                Log::warning('Sync skipped: API not available', [
                    'pending_count' => count($this->syncQueueService->getPendingChanges($tableName, $limit))
                ]);
                return $results;
            }

            // جلب التغييرات المعلقة
            $pendingChanges = $this->syncQueueService->getPendingChanges($tableName, $limit);
            
            Log::info('Pending changes check', [
                'pending_count' => count($pendingChanges),
                'table_name' => $tableName,
                'limit' => $limit,
                'sample_tables' => array_slice(array_unique(array_column($pendingChanges, 'table_name')), 0, 5),
            ]);

            if (empty($pendingChanges)) {
                Log::info('No pending changes to sync', [
                    'table_name' => $tableName,
                    'limit' => $limit,
                ]);
                return $results;
            }

            // Batch Processing: معالجة السجلات في batches
            $batchSize = min(50, count($pendingChanges)); // batch size أصغر للأداء
            $batches = array_chunk($pendingChanges, $batchSize);
            
            Log::info('Starting sync', [
                'total_changes' => count($pendingChanges),
                'batch_count' => count($batches),
                'batch_size' => $batchSize,
            ]);

            foreach ($batches as $batchIndex => $batch) {
                // التحقق من timeout
                $elapsedTime = microtime(true) - $startTime;
                if ($elapsedTime > $timeout) {
                    Log::warning('Sync timeout reached', [
                        'elapsed_time' => $elapsedTime,
                        'timeout' => $timeout,
                        'processed' => $results['synced'] + $results['failed'],
                        'remaining' => count($pendingChanges) - ($results['synced'] + $results['failed']),
                    ]);
                    $results['errors'][] = "انتهت مهلة المزامنة ({$timeout} ثانية). تمت معالجة " . ($results['synced'] + $results['failed']) . " من " . count($pendingChanges) . " سجل.";
                    break;
                }

                // معالجة كل batch
                foreach ($batch as $change) {
                    try {
                        // Retry mechanism: محاولة حتى 3 مرات
                        $maxRetries = 3;
                        $retryCount = 0;
                        $synced = false;

                        while ($retryCount < $maxRetries && !$synced) {
                            try {
                                // إذا كانت محاولة إعادة (retry > 0)، انتظر دقيقة
                                if ($retryCount > 0) {
                                    Log::info('Waiting 60 seconds before retry', [
                                        'table' => $change['table_name'],
                                        'record_id' => $change['record_id'],
                                        'retry' => $retryCount,
                                    ]);
                                    sleep(60); // انتظار دقيقة كاملة
                                }
                                
                                Log::debug('Processing sync change', [
                                    'table' => $change['table_name'],
                                    'record_id' => $change['record_id'],
                                    'action' => $change['action'],
                                    'retry' => $retryCount,
                                ]);
                                
                                $synced = $this->processChange($change);
                                if ($synced) {
                                    $this->syncQueueService->markAsSynced($change['id']);
                                    $results['synced']++;
                                    Log::debug('Sync change succeeded', [
                                        'table' => $change['table_name'],
                                        'record_id' => $change['record_id'],
                                        'action' => $change['action'],
                                    ]);
                                    break; // نجح - توقف عن المحاولة
                                } else {
                                    Log::warning('Sync change returned false', [
                                        'table' => $change['table_name'],
                                        'record_id' => $change['record_id'],
                                        'action' => $change['action'],
                                    ]);
                                }
                            } catch (\Exception $e) {
                                Log::error('Sync change exception', [
                                    'table' => $change['table_name'],
                                    'record_id' => $change['record_id'],
                                    'action' => $change['action'],
                                    'error' => $e->getMessage(),
                                    'retry' => $retryCount,
                                ]);
                                $retryCount++;
                                
                                // إذا كان الخطأ بسبب MySQL غير متاح أو فشل الاتصال، توقف فوراً
                                $errorMessage = $e->getMessage();
                                if (str_contains($errorMessage, 'MySQL غير متاح') || 
                                    str_contains($errorMessage, 'لا يمكن الاتصال') ||
                                    str_contains($errorMessage, 'connection attempt failed') ||
                                    str_contains($errorMessage, 'did not properly respond') ||
                                    str_contains($errorMessage, 'connected host has failed to respond') ||
                                    str_contains($errorMessage, 'SQLSTATE[HY000] [2002]')) {
                                    // لا تحاول مرة أخرى - MySQL غير متاح
                                    throw new \Exception('MySQL غير متاح - لا يمكن المزامنة. يرجى التحقق من الاتصال بالسيرفر.', 0, $e);
                                }
                                
                                // إذا كان الخطأ 429 (Too Many Requests)، توقف تماماً عن المحاولات
                                if (str_contains($errorMessage, 'HTTP 429') || 
                                    str_contains($errorMessage, 'Too Many Requests') ||
                                    str_contains($errorMessage, 'rate limit')) {
                                    Log::warning('Rate limit hit (429), stopping retry attempts for this batch', [
                                        'table' => $change['table_name'],
                                        'record_id' => $change['record_id'],
                                    ]);
                                    // وضع علامة failed مع رسالة واضحة
                                    throw new \Exception('تم تجاوز حد الطلبات (429). يرجى الانتظار قبل المحاولة مرة أخرى.', 0, $e);
                                }

                                // إذا كانت المحاولة الأخيرة، سجل الخطأ
                                if ($retryCount >= $maxRetries) {
                                    throw $e;
                                }

                                // انتظر قليلاً قبل إعادة المحاولة (exponential backoff)
                                usleep(100000 * $retryCount); // 100ms, 200ms, 300ms
                            }
                        }

                        if (!$synced) {
                            $results['failed']++;
                        }
                    } catch (\Exception $e) {
                        $errorMsg = "Table: {$change['table_name']}, Record: {$change['record_id']}, Error: " . $e->getMessage();
                        $this->syncQueueService->markAsFailed($change['id'], $errorMsg);
                        $results['failed']++;
                        $results['errors'][] = $errorMsg;
                        Log::error('Sync failed for change', [
                            'change' => $change,
                            'error' => $e->getMessage(),
                            'retry_count' => $retryCount ?? 0,
                        ]);
                    }
                }

                // Log progress بعد كل batch
                Log::info('Batch processed', [
                    'batch_index' => $batchIndex + 1,
                    'total_batches' => count($batches),
                    'synced' => $results['synced'],
                    'failed' => $results['failed'],
                    'elapsed_time' => round(microtime(true) - $startTime, 2),
                ]);
            }

            $results['completed_at'] = now()->toDateTimeString();
            $results['elapsed_time'] = round(microtime(true) - $startTime, 2);
            $results['total_processed'] = $results['synced'] + $results['failed'];

            Log::info('Sync completed', [
                'synced' => $results['synced'],
                'failed' => $results['failed'],
                'elapsed_time' => $results['elapsed_time'],
            ]);

            return $results;
        } catch (\Exception $e) {
            Log::error('Failed to sync pending changes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $results['errors'][] = 'خطأ عام في المزامنة: ' . $e->getMessage();
            $results['completed_at'] = now()->toDateTimeString();
            $results['elapsed_time'] = round(microtime(true) - $startTime, 2);
            return $results;
        }
    }

    /**
     * التحقق من توفر MySQL مع timeout قصير و retry mechanism
     */
    protected function isMySQLAvailable(): bool
    {
        $maxRetries = config('database.connections.mysql.max_retries', 3);
        $timeout = config('database.connections.mysql.timeout', 5);
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // محاولة الاتصال مع timeout قصير
                $host = config('database.connections.mysql.host');
                $port = config('database.connections.mysql.port', 3306);
                
                // أولاً: التحقق من أن السيرفر متاح (socket check)
                if ($this->checkHostPort($host, $port, $timeout)) {
                    // إذا كان السيرفر متاح، جرب الاتصال بـ PDO
                    $pdo = DB::connection('mysql')->getPdo();
                    
                    // اختبار بسيط للتأكد من أن الاتصال يعمل
                    DB::connection('mysql')->select('SELECT 1');
                    
                    if ($attempt > 1) {
                        Log::info('MySQL connection succeeded after retry', [
                            'attempt' => $attempt,
                            'host' => $host,
                            'port' => $port,
                        ]);
                    }
                    
                    return true;
                }
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                
                // التحقق من نوع الخطأ
                $isConnectionError = str_contains($errorMessage, 'connection attempt failed') ||
                                    str_contains($errorMessage, 'did not properly respond') ||
                                    str_contains($errorMessage, 'connected host has failed to respond') ||
                                    str_contains($errorMessage, 'SQLSTATE[HY000] [2002]') ||
                                    str_contains($errorMessage, 'getaddrinfo failed') ||
                                    str_contains($errorMessage, 'No such host is known');
                
                if ($attempt < $maxRetries) {
                    // انتظر قليلاً قبل إعادة المحاولة (exponential backoff)
                    $waitTime = min($attempt * 2, 5); // 2s, 4s, 5s
                    Log::debug('MySQL connection attempt failed, retrying', [
                        'attempt' => $attempt,
                        'max_retries' => $maxRetries,
                        'wait_time' => $waitTime,
                        'error' => $errorMessage,
                    ]);
                    sleep($waitTime);
                    continue;
                }
                
                Log::warning('MySQL connection failed after all retries', [
                    'attempts' => $maxRetries,
                    'error' => $errorMessage,
                    'is_connection_error' => $isConnectionError,
                ]);
                
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * التحقق من أن Host و Port متاحان (socket check)
     */
    protected function checkHostPort(string $host, int $port, int $timeout = 5): bool
    {
        try {
            // استخدام fsockopen للتحقق السريع من الاتصال
            $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
            
            if ($connection) {
                fclose($connection);
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * إعادة تعيين السجلات الفاشلة تلقائياً إلى pending إذا كان MySQL متاحاً الآن
     */
    protected function autoRetryFailedOnMySQLAvailable(): void
    {
        try {
            // إذا كان MySQL متاحاً الآن، أعد تعيين السجلات الفاشلة التي فشلت بسبب MySQL غير متاح
            if ($this->isMySQLAvailable()) {
                $connection = config('database.default');
                $updated = DB::connection($connection)->table('sync_queue')
                    ->where('status', 'failed')
                    ->where(function ($query) {
                        $query->where('error_message', 'like', '%MySQL غير متاح%')
                              ->orWhere('error_message', 'like', '%لا يمكن الاتصال%')
                              ->orWhere('error_message', 'like', '%connection attempt failed%');
                    })
                    ->update([
                        'status' => 'pending',
                        'error_message' => null,
                        'retry_count' => 0,
                        'updated_at' => now()
                    ]);

                if ($updated > 0) {
                    Log::info("Auto-retried {$updated} failed records after MySQL became available");
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to auto-retry failed records', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * معالجة تغيير واحد من sync_queue
     */
    protected function processChange(array $change): bool
    {
        $tableName = $change['table_name'];
        $recordId = $change['record_id'];
        $action = $change['action'];
        $data = $change['data'] ?? [];
        $changes = $change['changes'] ?? [];

        // إجبار استخدام API فقط - لا اتصال مباشر بـ MySQL
        if (!$this->useApi) {
            throw new \Exception("SYNC_VIA_API غير مفعّل - يجب تفعيل API Sync. لا يمكن استخدام MySQL مباشرة.");
        }
        
        // التحقق من توفر API فقط
        if (!$this->apiSyncService->isApiAvailable()) {
            throw new \Exception("API غير متاح - لا يمكن المزامنة");
        }

        // إذا لم تكن البيانات موجودة، جلبها من SQLite المحلي
        // المزامنة تكون من SQLite المحلي إلى MySQL على السيرفر
        if (empty($data) && in_array($action, ['insert', 'update'])) {
            try {
                $sqliteConnection = 'sync_sqlite';
                if (Schema::connection($sqliteConnection)->hasTable($tableName)) {
                    $record = DB::connection($sqliteConnection)->table($tableName)
                        ->where('id', $recordId)
                        ->first();
                    
                    if ($record) {
                        $data = (array) $record;
                        Log::debug('Fetched data from SQLite for sync', [
                            'table' => $tableName,
                            'record_id' => $recordId,
                            'action' => $action,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch data from SQLite', [
                    'table' => $tableName,
                    'record_id' => $recordId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // تنظيف البيانات قبل الإرسال (يجب أن يتم هنا أيضاً للتأكد)
        // البيانات قد تكون من sync_queue (JSON decoded) أو من SQLite
        if (!empty($data) && in_array($action, ['insert', 'update'])) {
            $originalTimestamps = [
                'created_at' => $data['created_at'] ?? null,
                'updated_at' => $data['updated_at'] ?? null,
            ];
            $data = $this->cleanDataForSync($data, $action);
            Log::debug('Data cleaned for sync', [
                'table' => $tableName,
                'record_id' => $recordId,
                'action' => $action,
                'original_timestamps' => $originalTimestamps,
                'cleaned_timestamps' => [
                    'created_at' => $data['created_at'] ?? null,
                    'updated_at' => $data['updated_at'] ?? null,
                ],
            ]);
        }

        switch ($action) {
            case 'insert':
                return $this->syncInsert($tableName, $data);

            case 'update':
                return $this->syncUpdate($tableName, $recordId, $data, $changes);

            case 'delete':
                return $this->syncDelete($tableName, $recordId);

            default:
                throw new \Exception("إجراء غير معروف: {$action}");
        }
    }

    /**
     * تنظيف البيانات قبل إرسالها للسيرفر
     * - تحويل timestamps من ISO 8601 إلى MySQL format
     * - إزالة deleted_at للعمليات insert
     * - إزالة id (السيرفر سينشئ id جديد)
     * - إزالة الحقول الإضافية (مثل avatar_url)
     */
    protected function cleanDataForSync(array $data, string $action = 'insert'): array
    {
        // إزالة id - السيرفر سينشئ id جديد
        unset($data['id']);
        
        // للعمليات insert، إزالة deleted_at (لا نريد إدراج سجل محذوف)
        if ($action === 'insert') {
            unset($data['deleted_at']);
        }
        
        // إزالة الحقول التي لا نريد مزامنتها أبداً
        // هذه الحقول خاصة بكل سيرفر ولا يجب مزامنتها
        $excludedFields = [
            'last_activity_at',  // آخر تفاعل - خاص بكل سيرفر
            'avatar',            // مسار الصورة - قد يختلف بين السيرفرات
            'avatar_url',        // رابط الصورة - خاص بكل سيرفر
            'session_id',        // Session - خاص بكل سيرفر
            'updated_at',        // وقت التحديث - سيتم تحديثه تلقائياً على كل سيرفر
        ];
        
        foreach ($excludedFields as $field) {
            unset($data[$field]);
        }
        
        // إزالة الحقول الإضافية التي لا توجد في MySQL
        // ملاحظة: avatar_url الآن موجود في MySQL (تمت إضافته عبر migration)
        $extraFields = [
            // يمكن إضافة حقول إضافية أخرى هنا إذا لزم الأمر
        ];
        
        foreach ($extraFields as $field) {
            unset($data[$field]);
        }
        
        // تحويل timestamps من ISO 8601 إلى MySQL format (Y-m-d H:i:s)
        // ملاحظة: last_activity_at و updated_at تم استثناؤهما أعلاه، لذا لن يصلا هنا
        $timestampFields = ['created_at', 'deleted_at', 'email_verified_at'];
        foreach ($timestampFields as $field) {
            if (isset($data[$field])) {
                $timestampValue = $data[$field];
                
                // إذا كانت القيمة بصيغة ISO 8601 (تحتوي على T أو Z)
                if (is_string($timestampValue) && (strpos($timestampValue, 'T') !== false || strpos($timestampValue, 'Z') !== false)) {
                    try {
                        // تحويل من ISO 8601 إلى Carbon ثم إلى MySQL format
                        $carbon = \Carbon\Carbon::parse($timestampValue);
                        $data[$field] = $carbon->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        // إذا فشل التحويل، إزالة الحقل أو ترك القيمة الحالية
                        Log::warning('Failed to convert timestamp', [
                            'field' => $field,
                            'value' => $timestampValue,
                            'error' => $e->getMessage(),
                        ]);
                        // للعمليات insert، نفضل إزالة timestamps والسماح للسيرفر بإنشائها
                        if ($action === 'insert' && in_array($field, ['created_at', 'updated_at'])) {
                            unset($data[$field]);
                        }
                    }
                }
            }
        }
        
        return $data;
    }

    /**
     * مزامنة إدراج جديد
     */
    protected function syncInsert(string $tableName, array $data): bool
    {
        // إجبار استخدام API فقط - لا اتصال مباشر بـ MySQL
        if (!$this->useApi) {
            throw new \Exception("SYNC_VIA_API غير مفعّل - يجب تفعيل API Sync. لا يمكن استخدام MySQL مباشرة.");
        }
        
        // تنظيف البيانات قبل الإرسال (تحويل timestamps، إزالة deleted_at و id)
        $cleanedData = $this->cleanDataForSync($data, 'insert');
        
        // استخدام API فقط
        $result = $this->apiSyncService->syncInsert($tableName, $cleanedData);
        
        if ($result['success'] ?? false) {
            // حفظ ID mapping من استجابة API إذا كان متوفراً
            if (isset($result['data']['local_id']) && isset($result['data']['server_id'])) {
                $this->idMappingService->saveMapping($tableName, $result['data']['local_id'], $result['data']['server_id'], 'up');
            }
            return true;
        }
        
        Log::error("Failed to sync insert via API for {$tableName}", [
            'data' => $data,
            'error' => $result['error'] ?? 'Unknown error',
        ]);
        return false;
    }

    /**
     * مزامنة تحديث
     */
    protected function syncUpdate(string $tableName, int $recordId, array $data, array $changes): bool
    {
        // إجبار استخدام API فقط - لا اتصال مباشر بـ MySQL
        if (!$this->useApi) {
            throw new \Exception("SYNC_VIA_API غير مفعّل - يجب تفعيل API Sync. لا يمكن استخدام MySQL مباشرة.");
        }
        
        // تنظيف البيانات قبل الإرسال (تحويل timestamps)
        $cleanedData = $this->cleanDataForSync($data, 'update');
        
        // استخدام API فقط
        $result = $this->apiSyncService->syncUpdate($tableName, $recordId, $cleanedData);
        
        if ($result['success'] ?? false) {
            return true;
        }
        
        Log::error("Failed to sync update via API for {$tableName}", [
            'record_id' => $recordId,
            'data' => $data,
            'error' => $result['error'] ?? 'Unknown error',
        ]);
        return false;
    }

    /**
     * مزامنة حذف
     */
    protected function syncDelete(string $tableName, int $recordId): bool
    {
        // إجبار استخدام API فقط - لا اتصال مباشر بـ MySQL
        if (!$this->useApi) {
            throw new \Exception("SYNC_VIA_API غير مفعّل - يجب تفعيل API Sync. لا يمكن استخدام MySQL مباشرة.");
        }
        
        // استخدام API فقط
        $result = $this->apiSyncService->syncDelete($tableName, $recordId);
        
        if ($result['success'] ?? false) {
            // حذف mapping إذا كان متوفراً
            try {
                $this->idMappingService->deleteMapping($tableName, $recordId, 'up');
            } catch (\Exception $e) {
                // تجاهل خطأ حذف mapping
                Log::debug("Failed to delete mapping for {$tableName}:{$recordId}", [
                    'error' => $e->getMessage(),
                ]);
            }
            return true;
        }
        
        Log::error("Failed to sync delete via API for {$tableName}", [
            'record_id' => $recordId,
            'error' => $result['error'] ?? 'Unknown error',
        ]);
        return false;
    }

    /**
     * مزامنة كاملة: مزامنة جميع التغييرات المعلقة
     */
    public function syncAllPending(int $batchSize = 100): array
    {
        $totalResults = [
            'synced' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        do {
            $results = $this->syncPendingChanges(null, $batchSize);
            $totalResults['synced'] += $results['synced'];
            $totalResults['failed'] += $results['failed'];
            $totalResults['errors'] = array_merge($totalResults['errors'], $results['errors']);
        } while ($results['synced'] > 0 || $results['failed'] > 0);

        return $totalResults;
    }

    /**
     * الحصول على إحصائيات sync_queue
     */
    public function getQueueStats(): array
    {
        try {
            // استخدام الاتصال الافتراضي (sync_sqlite في Local)
            $connection = config('database.default');
            
            if (!Schema::connection($connection)->hasTable('sync_queue')) {
                return [
                    'pending' => 0,
                    'synced' => 0,
                    'failed' => 0,
                    'total' => 0,
                ];
            }

            $stats = DB::connection($connection)->table('sync_queue')
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = "synced" THEN 1 ELSE 0 END) as synced,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
                ')
                ->first();

            return [
                'pending' => (int) ($stats->pending ?? 0),
                'synced' => (int) ($stats->synced ?? 0),
                'failed' => (int) ($stats->failed ?? 0),
                'total' => (int) ($stats->total ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get queue stats', [
                'error' => $e->getMessage(),
            ]);
            return [
                'pending' => 0,
                'synced' => 0,
                'failed' => 0,
                'total' => 0,
            ];
        }
    }
}

