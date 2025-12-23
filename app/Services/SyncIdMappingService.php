<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SyncIdMappingService
{
    /**
     * حفظ mapping بين local_id و server_id
     */
    public function saveMapping(string $tableName, int $localId, int $serverId, string $direction = 'up'): bool
    {
        try {
            if (!Schema::hasTable('sync_id_mapping')) {
                return false;
            }

            // التحقق من وجود mapping موجود
            $existing = DB::table('sync_id_mapping')
                ->where('table_name', $tableName)
                ->where('local_id', $localId)
                ->where('sync_direction', $direction)
                ->first();

            if ($existing) {
                // تحديث الـ server_id إذا تغير
                if ($existing->server_id != $serverId) {
                    DB::table('sync_id_mapping')
                        ->where('id', $existing->id)
                        ->update([
                            'server_id' => $serverId,
                            'updated_at' => now(),
                        ]);
                }
            } else {
                // إضافة mapping جديد
                DB::table('sync_id_mapping')->insert([
                    'table_name' => $tableName,
                    'local_id' => $localId,
                    'server_id' => $serverId,
                    'sync_direction' => $direction,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to save ID mapping', [
                'table' => $tableName,
                'local_id' => $localId,
                'server_id' => $serverId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * الحصول على server_id من local_id
     */
    public function getServerId(string $tableName, int $localId, string $direction = 'up'): ?int
    {
        try {
            if (!Schema::hasTable('sync_id_mapping')) {
                return null;
            }

            $mapping = DB::table('sync_id_mapping')
                ->where('table_name', $tableName)
                ->where('local_id', $localId)
                ->where('sync_direction', $direction)
                ->first();

            return $mapping ? (int) $mapping->server_id : null;
        } catch (\Exception $e) {
            Log::error('Failed to get server ID', [
                'table' => $tableName,
                'local_id' => $localId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * الحصول على local_id من server_id
     */
    public function getLocalId(string $tableName, int $serverId, string $direction = 'up'): ?int
    {
        try {
            if (!Schema::hasTable('sync_id_mapping')) {
                return null;
            }

            $mapping = DB::table('sync_id_mapping')
                ->where('table_name', $tableName)
                ->where('server_id', $serverId)
                ->where('sync_direction', $direction)
                ->first();

            return $mapping ? (int) $mapping->local_id : null;
        } catch (\Exception $e) {
            Log::error('Failed to get local ID', [
                'table' => $tableName,
                'server_id' => $serverId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * التحقق من وجود ID في السيرفر (للتعارضات)
     * تم إزالة الاتصال المباشر بـ MySQL - API يتعامل مع ID conflicts تلقائياً
     */
    public function checkIdConflict(string $tableName, int $id): bool
    {
        // إذا كان SYNC_VIA_API=true، لا نحتاج للتحقق من MySQL
        // API سيتعامل مع ID conflicts تلقائياً
        $syncViaApi = filter_var(env('SYNC_VIA_API', false), FILTER_VALIDATE_BOOLEAN);
        if ($syncViaApi) {
            // مع API Sync، نعتبر أن لا يوجد تعارض (API سيتعامل معه)
            return false;
        }
        
        // فقط إذا كان SYNC_VIA_API=false (غير موصى به)
        try {
            return DB::connection('mysql')->table($tableName)->where('id', $id)->exists();
        } catch (\Exception $e) {
            // إذا فشل الاتصال بـ MySQL، افترض عدم وجود تعارض
            return false;
        }
    }

    /**
     * حل التعارض: إيجاد ID متاح جديد
     * تم إزالة الاتصال المباشر بـ MySQL - API يتعامل مع ID conflicts تلقائياً
     */
    public function resolveConflict(string $tableName, int $preferredId): int
    {
        // إذا كان SYNC_VIA_API=true، لا نحتاج للتحقق من MySQL
        // API سيتعامل مع ID conflicts تلقائياً
        $syncViaApi = filter_var(env('SYNC_VIA_API', false), FILTER_VALIDATE_BOOLEAN);
        if ($syncViaApi) {
            // مع API Sync، نعيد الـ preferred ID (API سيتعامل مع التعارض)
            return $preferredId;
        }
        
        // فقط إذا كان SYNC_VIA_API=false (غير موصى به)
        try {
            // إذا كان ID متاح في MySQL، استخدمه
            if (!$this->checkIdConflict($tableName, $preferredId)) {
                return $preferredId;
            }

            // البحث عن ID متاح في MySQL (ابحث بعد الـ ID المفضل)
            $maxId = DB::connection('mysql')->table($tableName)->max('id') ?? 0;
            $newId = max($preferredId, $maxId) + 1;

            // تأكد من أن الـ ID الجديد متاح في MySQL
            while ($this->checkIdConflict($tableName, $newId)) {
                $newId++;
            }

            return $newId;
        } catch (\Exception $e) {
            Log::error('Failed to resolve ID conflict', [
                'table' => $tableName,
                'preferred_id' => $preferredId,
                'error' => $e->getMessage(),
            ]);
            // في حالة الخطأ، استخدم timestamp كـ ID مؤقت
            return (int) (time() * 1000);
        }
    }

    /**
     * حذف mapping
     */
    public function deleteMapping(string $tableName, int $localId, string $direction = 'up'): bool
    {
        try {
            if (!Schema::hasTable('sync_id_mapping')) {
                return false;
            }

            DB::table('sync_id_mapping')
                ->where('table_name', $tableName)
                ->where('local_id', $localId)
                ->where('sync_direction', $direction)
                ->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete ID mapping', [
                'table' => $tableName,
                'local_id' => $localId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

