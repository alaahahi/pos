<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SyncIdMappingService
{
    protected function isUuidTable(string $tableName): bool
    {
        $uuidTables = config('sync.uuid_tables', []);
        return in_array($tableName, $uuidTables, true);
    }

    /**
     * حفظ mapping بين local_id و server_id (لجداول integer فقط؛ جداول UUID لا تحتاج mapping)
     */
    public function saveMapping(string $tableName, int|string $localId, int|string $serverId, string $direction = 'up'): bool
    {
        if ($this->isUuidTable($tableName)) {
            return true;
        }
        try {
            if (!Schema::hasTable('sync_id_mapping')) {
                return false;
            }
            $localId = (string) $localId;
            $serverId = (string) $serverId;

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
     * الحصول على server_id من local_id (لجداول UUID يُعيد نفس القيمة)
     */
    public function getServerId(string $tableName, int|string $localId, string $direction = 'up'): int|string|null
    {
        if ($this->isUuidTable($tableName)) {
            return $localId;
        }
        try {
            if (!Schema::hasTable('sync_id_mapping')) {
                return null;
            }
            $localId = (string) $localId;
            $mapping = DB::table('sync_id_mapping')
                ->where('table_name', $tableName)
                ->where('local_id', $localId)
                ->where('sync_direction', $direction)
                ->first();

            if ($mapping) {
                $sid = $mapping->server_id;
                return is_numeric($sid) ? (int) $sid : $sid;
            }
            return null;
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
     * الحصول على local_id من server_id (لجداول UUID يُعيد نفس القيمة)
     */
    public function getLocalId(string $tableName, int|string $serverId, string $direction = 'up'): int|string|null
    {
        if ($this->isUuidTable($tableName)) {
            return $serverId;
        }
        try {
            if (!Schema::hasTable('sync_id_mapping')) {
                return null;
            }
            $serverId = (string) $serverId;
            $mapping = DB::table('sync_id_mapping')
                ->where('table_name', $tableName)
                ->where('server_id', $serverId)
                ->where('sync_direction', $direction)
                ->first();

            if ($mapping) {
                $lid = $mapping->local_id;
                return is_numeric($lid) ? (int) $lid : $lid;
            }
            return null;
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
    public function checkIdConflict(string $tableName, int|string $id): bool
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
    public function resolveConflict(string $tableName, int|string $preferredId): int|string
    {
        if ($this->isUuidTable($tableName)) {
            return is_string($preferredId) ? $preferredId : (string) $preferredId;
        }
        $syncViaApi = filter_var(env('SYNC_VIA_API', false), FILTER_VALIDATE_BOOLEAN);
        if ($syncViaApi) {
            return $preferredId;
        }
        $preferredId = (int) $preferredId;
        try {
            if (!$this->checkIdConflict($tableName, $preferredId)) {
                return $preferredId;
            }
            $maxId = (int) (DB::connection('mysql')->table($tableName)->max('id') ?? 0);
            $newId = max($preferredId, $maxId) + 1;
            while ($this->checkIdConflict($tableName, $newId)) {
                $newId++;
            }
            return $newId;
        } catch (\Exception $e) {
            Log::error('Failed to resolve ID conflict', ['table' => $tableName, 'preferred_id' => $preferredId, 'error' => $e->getMessage()]);
            return (int) (time() * 1000);
        }
    }

    /**
     * حذف mapping
     */
    public function deleteMapping(string $tableName, int|string $localId, string $direction = 'up'): bool
    {
        if ($this->isUuidTable($tableName)) {
            return true;
        }
        try {
            if (!Schema::hasTable('sync_id_mapping')) {
                return false;
            }
            $localId = (string) $localId;
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

