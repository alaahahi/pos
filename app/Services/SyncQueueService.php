<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SyncQueueService
{
    /**
     * إضافة تغيير إلى قائمة الانتظار للمزامنة
     * يعمل فقط في البيئة المحلية (Local) أو عندما يكون الاتصال الافتراضي SQLite
     */
    public function queueChange(string $tableName, int $recordId, string $action, array $data = null, array $changes = null): bool
    {
        // العمل فقط إذا كان الاتصال الافتراضي SQLite
        $defaultConnection = config('database.default');
        $isSQLite = in_array($defaultConnection, ['sync_sqlite', 'sqlite']);
        
        // إذا لم يكن SQLite، لا نحتاج لـ sync_queue
        // (على السيرفر مع MySQL، لا حاجة للمزامنة)
        if (!$isSQLite) {
            return false;
        }

        // استخدام الاتصال الافتراضي (sync_sqlite)
        $connection = config('database.default');
        
        // التحقق من وجود الجدول
        if (!Schema::connection($connection)->hasTable('sync_queue')) {
            Log::warning('sync_queue table does not exist. Skipping queue change.');
            return false;
        }

        try {
            // التحقق من وجود سجل pending لنفس السجل
            $existing = DB::connection($connection)->table('sync_queue')
                ->where('table_name', $tableName)
                ->where('record_id', $recordId)
                ->where('status', 'pending')
                ->first();

            if ($existing) {
                // تحديث السجل الموجود
                DB::connection($connection)->table('sync_queue')
                    ->where('id', $existing->id)
                    ->update([
                        'action' => $action,
                        'data' => $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
                        'changes' => $changes ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null,
                        'status' => 'pending',
                        'retry_count' => 0,
                        'error_message' => null,
                        'updated_at' => now()
                    ]);
            } else {
                // إضافة سجل جديد
                DB::connection($connection)->table('sync_queue')->insert([
                    'table_name' => $tableName,
                    'record_id' => $recordId,
                    'action' => $action,
                    'data' => $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
                    'changes' => $changes ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue sync change', [
                'table' => $tableName,
                'record_id' => $recordId,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * تسجيل تحديث سجل
     */
    public function queueUpdate(string $tableName, int $recordId, array $oldData, array $newData): bool
    {
        // حساب الحقول التي تغيرت
        $changes = [];
        foreach ($newData as $key => $value) {
            if (!isset($oldData[$key]) || $oldData[$key] != $value) {
                $changes[$key] = [
                    'old' => $oldData[$key] ?? null,
                    'new' => $value
                ];
            }
        }

        if (empty($changes)) {
            // لا توجد تغييرات فعلية
            return false;
        }

        // حفظ البيانات الكاملة للسجل الجديد
        return $this->queueChange($tableName, $recordId, 'update', $newData, $changes);
    }

    /**
     * تسجيل إدراج سجل جديد
     */
    public function queueInsert(string $tableName, int $recordId, array $data): bool
    {
        return $this->queueChange($tableName, $recordId, 'insert', $data);
    }

    /**
     * تسجيل حذف سجل
     */
    public function queueDelete(string $tableName, int $recordId): bool
    {
        return $this->queueChange($tableName, $recordId, 'delete');
    }

    /**
     * جلب التغييرات المعلقة للمزامنة
     */
    public function getPendingChanges(string $tableName = null, int $limit = 100): array
    {
        // استخدام الاتصال الافتراضي (sync_sqlite في Local)
        $connection = config('database.default');
        
        $query = DB::connection($connection)->table('sync_queue')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc');

        if ($tableName) {
            $query->where('table_name', $tableName);
        }

        return $query->limit($limit)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'table_name' => $item->table_name,
                'record_id' => $item->record_id,
                'action' => $item->action,
                'data' => $item->data ? json_decode($item->data, true) : null,
                'changes' => $item->changes ? json_decode($item->changes, true) : null,
                'retry_count' => $item->retry_count,
                'created_at' => $item->created_at
            ];
        })->toArray();
    }

    /**
     * تحديث حالة السجل بعد المزامنة
     */
    public function markAsSynced(int $queueId): bool
    {
        try {
            // استخدام الاتصال الافتراضي (sync_sqlite في Local)
            $connection = config('database.default');
            
            DB::connection($connection)->table('sync_queue')
                ->where('id', $queueId)
                ->update([
                    'status' => 'synced',
                    'synced_at' => now(),
                    'updated_at' => now()
                ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark sync queue as synced', [
                'queue_id' => $queueId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * تحديث حالة السجل بعد فشل المزامنة
     */
    public function markAsFailed(int $queueId, string $errorMessage): bool
    {
        try {
            // استخدام الاتصال الافتراضي (sync_sqlite في Local)
            $connection = config('database.default');
            
            DB::connection($connection)->table('sync_queue')
                ->where('id', $queueId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $errorMessage,
                    'retry_count' => DB::raw('retry_count + 1'),
                    'updated_at' => now()
                ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark sync queue as failed', [
                'queue_id' => $queueId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * حذف السجلات المزامنة (أقدم من X ساعة)
     */
    public function cleanSyncedRecords(int $hoursOld = 24): int
    {
        try {
            // استخدام الاتصال الافتراضي (sync_sqlite في Local)
            $connection = config('database.default');
            
            $deleted = DB::connection($connection)->table('sync_queue')
                ->where('status', 'synced')
                ->where('synced_at', '<', now()->subHours($hoursOld))
                ->delete();

            Log::info('Cleaned synced records from sync_queue', [
                'deleted_count' => $deleted,
                'hours_old' => $hoursOld
            ]);

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Failed to clean synced records', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * إعادة محاولة المزامنة للفاشلة
     */
    public function retryFailed(int $maxRetries = 3): int
    {
        try {
            // استخدام الاتصال الافتراضي (sync_sqlite في Local)
            $connection = config('database.default');
            
            $updated = DB::connection($connection)->table('sync_queue')
                ->where('status', 'failed')
                ->where('retry_count', '<', $maxRetries)
                ->update([
                    'status' => 'pending',
                    'error_message' => null,
                    'updated_at' => now()
                ]);

            return $updated;
        } catch (\Exception $e) {
            Log::error('Failed to retry failed sync records', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * إعادة تعيين جميع السجلات الفاشلة إلى pending (للمحاولة مرة أخرى)
     */
    public function resetFailedToPending(): int
    {
        try {
            // استخدام الاتصال الافتراضي (sync_sqlite في Local)
            $connection = config('database.default');
            
            $updated = DB::connection($connection)->table('sync_queue')
                ->where('status', 'failed')
                ->update([
                    'status' => 'pending',
                    'error_message' => null,
                    'retry_count' => 0,
                    'updated_at' => now()
                ]);

            Log::info('Reset failed sync records to pending', [
                'count' => $updated
            ]);

            return $updated;
        } catch (\Exception $e) {
            Log::error('Failed to reset failed sync records', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}

