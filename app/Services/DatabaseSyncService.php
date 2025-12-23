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
        
        // التحقق من استخدام API للمزامنة
        $this->useApi = env('SYNC_VIA_API', false);
        if ($this->useApi) {
            $this->apiSyncService = new ApiSyncService();
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
            // التحقق من توفر MySQL أو API قبل البدء
            if ($this->useApi) {
                if (!$this->apiSyncService->isApiAvailable()) {
                    $errorMsg = 'API غير متاح - لا يمكن المزامنة. يرجى التحقق من الاتصال بالسيرفر.';
                    $results['errors'][] = $errorMsg;
                    $results['message'] = $errorMsg;
                    Log::warning('Sync skipped: API not available', [
                        'pending_count' => count($this->syncQueueService->getPendingChanges($tableName, $limit))
                    ]);
                    return $results;
                }
            } else {
                if (!$this->isMySQLAvailable()) {
                    $errorMsg = 'MySQL غير متاح - لا يمكن المزامنة. يرجى التحقق من الاتصال بالسيرفر.';
                    $results['errors'][] = $errorMsg;
                    $results['message'] = $errorMsg;
                    Log::warning('Sync skipped: MySQL not available', [
                        'pending_count' => count($this->syncQueueService->getPendingChanges($tableName, $limit))
                    ]);
                    
                    // إعادة تعيين السجلات الفاشلة التي فشلت بسبب MySQL غير متاح إلى pending
                    // حتى يتم إعادة المحاولة تلقائياً عندما يكون MySQL متاحاً
                    $this->autoRetryFailedOnMySQLAvailable();
                    
                    return $results;
                }
            }

            // جلب التغييرات المعلقة
            $pendingChanges = $this->syncQueueService->getPendingChanges($tableName, $limit);

            if (empty($pendingChanges)) {
                Log::info('No pending changes to sync');
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
                                $synced = $this->processChange($change);
                                if ($synced) {
                                    $this->syncQueueService->markAsSynced($change['id']);
                                    $results['synced']++;
                                    break; // نجح - توقف عن المحاولة
                                }
                            } catch (\Exception $e) {
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

        // التحقق من توفر MySQL أو API مرة أخرى (للأمان)
        if ($this->useApi) {
            if (!$this->apiSyncService->isApiAvailable()) {
                throw new \Exception("API غير متاح - لا يمكن المزامنة");
            }
        } else {
            if (!$this->isMySQLAvailable()) {
                throw new \Exception("MySQL غير متاح - لا يمكن المزامنة");
            }

            // التحقق من وجود الجدول في MySQL (استخدام صريح للمزامنة)
            try {
                if (!Schema::connection('mysql')->hasTable($tableName)) {
                    throw new \Exception("الجدول {$tableName} غير موجود في MySQL");
                }
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                
                // إذا فشل التحقق من الجدول بسبب مشكلة في الاتصال
                if (str_contains($errorMessage, 'connection attempt failed') ||
                    str_contains($errorMessage, 'did not properly respond') ||
                    str_contains($errorMessage, 'connected host has failed to respond') ||
                    str_contains($errorMessage, 'SQLSTATE[HY000] [2002]') ||
                    str_contains($errorMessage, 'getaddrinfo failed') ||
                    str_contains($errorMessage, 'No such host is known') ||
                    str_contains($errorMessage, 'Connection') ||
                    str_contains($errorMessage, 'getaddrinfo')) {
                    throw new \Exception('MySQL غير متاح - لا يمكن المزامنة. يرجى التحقق من الاتصال بالسيرفر.', 0, $e);
                }
                throw $e;
            }
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
     * مزامنة إدراج جديد
     */
    protected function syncInsert(string $tableName, array $data): bool
    {
        // استخدام API إذا كان مفعلاً
        if ($this->useApi) {
            $result = $this->apiSyncService->syncInsert($tableName, $data);
            return $result['success'] ?? false;
        }
        
        try {
            // معالجة خاصة لـ pivot tables (order_product)
            if ($tableName === 'order_product') {
                // في pivot tables، نحتاج إلى تحويل order_id من local إلى server
                $localOrderId = $data['order_id'] ?? null;
                if ($localOrderId) {
                    $serverOrderId = $this->idMappingService->getServerId('orders', $localOrderId, 'up');
                    if ($serverOrderId) {
                        $data['order_id'] = $serverOrderId;
                    }
                }
                
                // تحويل product_id من local إلى server (إذا لزم الأمر)
                $localProductId = $data['product_id'] ?? null;
                if ($localProductId) {
                    $serverProductId = $this->idMappingService->getServerId('products', $localProductId, 'up');
                    if ($serverProductId) {
                        $data['product_id'] = $serverProductId;
                    }
                }
                
                // إزالة timestamps إذا كانت موجودة
                unset($data['created_at'], $data['updated_at']);
                
                // إدراج في MySQL (قد يكون هناك unique constraint على order_id + product_id)
                try {
                    DB::connection('mysql')->table($tableName)->insert($data);
                } catch (\Exception $e) {
                    // إذا كان السجل موجوداً بالفعل (unique constraint)، قم بالتحديث
                    if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                        DB::connection('mysql')->table($tableName)
                            ->where('order_id', $data['order_id'])
                            ->where('product_id', $data['product_id'])
                            ->update([
                                'quantity' => $data['quantity'],
                                'price' => $data['price'],
                                'updated_at' => now(),
                            ]);
                    } else {
                        throw $e;
                    }
                }
                
                return true;
            }
            
            // للجداول العادية
            $localId = $data['id'] ?? null;
            
            // إزالة timestamps إذا كانت موجودة (لأن MySQL سينشئها تلقائياً)
            unset($data['created_at'], $data['updated_at']);

            // التحقق من وجود ID في السيرفر (تعارض محتمل)
            if ($localId && $this->idMappingService->checkIdConflict($tableName, $localId)) {
                // يوجد تعارض - حل التعارض بإيجاد ID جديد
                $resolvedId = $this->idMappingService->resolveConflict($tableName, $localId);
                $data['id'] = $resolvedId;
                
                Log::warning("ID conflict resolved for {$tableName}", [
                    'local_id' => $localId,
                    'resolved_id' => $resolvedId,
                ]);
            } else {
                // لا يوجد تعارض - يمكن استخدام ID المحلي إذا كان موجوداً
                $preferredId = $localId ?? null;
                if ($preferredId) {
                    $data['id'] = $preferredId;
                } else {
                    // لا يوجد ID محلي - دع MySQL ينشئ ID جديد
                    unset($data['id']);
                }
            }

            // إدراج البيانات في MySQL (استخدام صريح للمزامنة)
            if (isset($data['id'])) {
                // استخدام ID محدد
                DB::connection('mysql')->table($tableName)->insert($data);
                $serverId = $data['id'];
            } else {
                // MySQL سينشئ ID جديد
                $serverId = DB::connection('mysql')->table($tableName)->insertGetId($data);
            }

            // حفظ mapping بين local_id و server_id (حتى لو كانا متساويين)
            if ($localId) {
                $this->idMappingService->saveMapping($tableName, $localId, $serverId, 'up');
            }

            return true;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // التحقق من أخطاء الاتصال
            if (str_contains($errorMessage, 'connection attempt failed') ||
                str_contains($errorMessage, 'did not properly respond') ||
                str_contains($errorMessage, 'connected host has failed to respond') ||
                str_contains($errorMessage, 'SQLSTATE[HY000] [2002]') ||
                str_contains($errorMessage, 'getaddrinfo failed') ||
                str_contains($errorMessage, 'No such host is known')) {
                throw new \Exception('MySQL غير متاح - لا يمكن المزامنة. يرجى التحقق من الاتصال بالسيرفر.', 0, $e);
            }
            
            Log::error("Failed to sync insert for {$tableName}", [
                'data' => $data,
                'error' => $errorMessage,
            ]);
            throw $e;
        }
    }

    /**
     * مزامنة تحديث
     */
    protected function syncUpdate(string $tableName, int $recordId, array $data, array $changes): bool
    {
        // استخدام API إذا كان مفعلاً
        if ($this->useApi) {
            $result = $this->apiSyncService->syncUpdate($tableName, $recordId, $data);
            return $result['success'] ?? false;
        }
        
        try {
            $localId = $recordId; // recordId هو local_id من sync_queue
            
            // الحصول على server_id من mapping
            $serverId = $this->idMappingService->getServerId($tableName, $localId, 'up');
            
            // إذا لم يكن هناك mapping، جرب استخدام local_id مباشرة
            if (!$serverId) {
                // التحقق من وجود السجل بالـ local_id
                if ($this->idMappingService->checkIdConflict($tableName, $localId)) {
                    $serverId = $localId;
                    // حفظ mapping
                    $this->idMappingService->saveMapping($tableName, $localId, $serverId, 'up');
                } else {
                    // السجل غير موجود - إنشاؤه كـ insert جديد
                    return $this->syncInsert($tableName, $data);
                }
            }

            // إزالة id من البيانات
            unset($data['id']);

            // إزالة timestamps القديمة
            unset($data['created_at']);

            // تحديث updated_at
            $data['updated_at'] = now();

            // التحقق من وجود السجل في MySQL (استخدام صريح للمزامنة)
            $exists = DB::connection('mysql')->table($tableName)->where('id', $serverId)->exists();

            if ($exists) {
                // تحديث السجل الموجود
                DB::connection('mysql')->table($tableName)
                    ->where('id', $serverId)
                    ->update($data);
            } else {
                // إذا لم يكن موجوداً، إنشاؤه
                $data['id'] = $serverId;
                DB::connection('mysql')->table($tableName)->insert($data);
            }

            return true;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // التحقق من أخطاء الاتصال
            if (str_contains($errorMessage, 'connection attempt failed') ||
                str_contains($errorMessage, 'did not properly respond') ||
                str_contains($errorMessage, 'connected host has failed to respond') ||
                str_contains($errorMessage, 'SQLSTATE[HY000] [2002]') ||
                str_contains($errorMessage, 'getaddrinfo failed') ||
                str_contains($errorMessage, 'No such host is known')) {
                throw new \Exception('MySQL غير متاح - لا يمكن المزامنة. يرجى التحقق من الاتصال بالسيرفر.', 0, $e);
            }
            
            Log::error("Failed to sync update for {$tableName}", [
                'record_id' => $recordId,
                'data' => $data,
                'changes' => $changes,
                'error' => $errorMessage,
            ]);
            throw $e;
        }
    }

    /**
     * مزامنة حذف
     */
    protected function syncDelete(string $tableName, int $recordId): bool
    {
        // استخدام API إذا كان مفعلاً
        if ($this->useApi) {
            $result = $this->apiSyncService->syncDelete($tableName, $recordId);
            return $result['success'] ?? false;
        }
        
        try {
            $localId = $recordId; // recordId هو local_id من sync_queue
            
            // معالجة خاصة لـ pivot tables (order_product)
            // في pivot tables، recordId هو order_id وليس id
            if ($tableName === 'order_product') {
                // الحصول على server_id للـ order من mapping
                $orderServerId = $this->idMappingService->getServerId('orders', $localId, 'up');
                
                if (!$orderServerId) {
                    $orderServerId = $localId; // إذا لم يكن هناك mapping، استخدم local_id
                }
                
                // حذف جميع السجلات المرتبطة بالطلب من order_product
                DB::connection('mysql')->table($tableName)
                    ->where('order_id', $orderServerId)
                    ->delete();
                
                return true;
            }
            
            // للجداول العادية
            // الحصول على server_id من mapping
            $serverId = $this->idMappingService->getServerId($tableName, $localId, 'up');
            
            // إذا لم يكن هناك mapping، جرب استخدام local_id مباشرة
            if (!$serverId) {
                $serverId = $localId;
            }

            // التحقق من وجود السجل في MySQL (استخدام صريح للمزامنة)
            $exists = DB::connection('mysql')->table($tableName)->where('id', $serverId)->exists();

            if ($exists) {
                // حذف السجل
                DB::connection('mysql')->table($tableName)->where('id', $serverId)->delete();
            }

            // حذف mapping
            $this->idMappingService->deleteMapping($tableName, $localId, 'up');

            return true;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // التحقق من أخطاء الاتصال
            if (str_contains($errorMessage, 'connection attempt failed') ||
                str_contains($errorMessage, 'did not properly respond') ||
                str_contains($errorMessage, 'connected host has failed to respond') ||
                str_contains($errorMessage, 'SQLSTATE[HY000] [2002]') ||
                str_contains($errorMessage, 'getaddrinfo failed') ||
                str_contains($errorMessage, 'No such host is known')) {
                throw new \Exception('MySQL غير متاح - لا يمكن المزامنة. يرجى التحقق من الاتصال بالسيرفر.', 0, $e);
            }
            
            Log::error("Failed to sync delete for {$tableName}", [
                'record_id' => $recordId,
                'error' => $errorMessage,
            ]);
            throw $e;
        }
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

