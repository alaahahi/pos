<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DatabaseSyncService
{
    protected $syncQueueService;
    protected $idMappingService;

    public function __construct()
    {
        $this->syncQueueService = new SyncQueueService();
        $this->idMappingService = new SyncIdMappingService();
    }

    /**
     * مزامنة ذكية: مزامنة التغييرات من sync_queue فقط
     */
    public function syncPendingChanges(string $tableName = null, int $limit = 100): array
    {
        $results = [
            'synced' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            // جلب التغييرات المعلقة
            $pendingChanges = $this->syncQueueService->getPendingChanges($tableName, $limit);

            foreach ($pendingChanges as $change) {
                try {
                    $synced = $this->processChange($change);
                    if ($synced) {
                        $this->syncQueueService->markAsSynced($change['id']);
                        $results['synced']++;
                    } else {
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
                    ]);
                }
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Failed to sync pending changes', [
                'error' => $e->getMessage(),
            ]);
            return $results;
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

        // التحقق من وجود الجدول في MySQL
        if (!Schema::hasTable($tableName)) {
            throw new \Exception("الجدول {$tableName} غير موجود في MySQL");
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
        try {
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

            // إدراج البيانات
            if (isset($data['id'])) {
                // استخدام ID محدد
                DB::table($tableName)->insert($data);
                $serverId = $data['id'];
            } else {
                // MySQL سينشئ ID جديد
                $serverId = DB::table($tableName)->insertGetId($data);
            }

            // حفظ mapping بين local_id و server_id (حتى لو كانا متساويين)
            if ($localId) {
                $this->idMappingService->saveMapping($tableName, $localId, $serverId, 'up');
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to sync insert for {$tableName}", [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * مزامنة تحديث
     */
    protected function syncUpdate(string $tableName, int $recordId, array $data, array $changes): bool
    {
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

            // التحقق من وجود السجل في السيرفر
            $exists = DB::table($tableName)->where('id', $serverId)->exists();

            if ($exists) {
                // تحديث السجل الموجود
                DB::table($tableName)
                    ->where('id', $serverId)
                    ->update($data);
            } else {
                // إذا لم يكن موجوداً، إنشاؤه
                $data['id'] = $serverId;
                DB::table($tableName)->insert($data);
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to sync update for {$tableName}", [
                'record_id' => $recordId,
                'data' => $data,
                'changes' => $changes,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * مزامنة حذف
     */
    protected function syncDelete(string $tableName, int $recordId): bool
    {
        try {
            $localId = $recordId; // recordId هو local_id من sync_queue
            
            // الحصول على server_id من mapping
            $serverId = $this->idMappingService->getServerId($tableName, $localId, 'up');
            
            // إذا لم يكن هناك mapping، جرب استخدام local_id مباشرة
            if (!$serverId) {
                $serverId = $localId;
            }

            // التحقق من وجود السجل في السيرفر
            $exists = DB::table($tableName)->where('id', $serverId)->exists();

            if ($exists) {
                // حذف السجل
                DB::table($tableName)->where('id', $serverId)->delete();
            }

            // حذف mapping
            $this->idMappingService->deleteMapping($tableName, $localId, 'up');

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to sync delete for {$tableName}", [
                'record_id' => $recordId,
                'error' => $e->getMessage(),
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
            if (!Schema::hasTable('sync_queue')) {
                return [
                    'pending' => 0,
                    'synced' => 0,
                    'failed' => 0,
                    'total' => 0,
                ];
            }

            $stats = DB::table('sync_queue')
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

