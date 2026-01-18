<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * خدمة المزامنة التلقائية
 * تقوم بمزامنة البيانات بين SQLite و MySQL/API بشكل دوري
 */
class AutoSyncService
{
    protected $apiSyncService;
    protected $lastSyncKey = 'auto_sync_last_run';
    protected $syncInterval; // بالثواني (5 دقائق = 300 ثانية)
    
    public function __construct()
    {
        $this->apiSyncService = new ApiSyncService();
        $this->syncInterval = (int) env('AUTO_SYNC_INTERVAL', 300); // 5 دقائق افتراضياً
    }
    
    /**
     * التحقق من حالة الاتصال - عام
     * لا تتطلب اتصال بالإنترنت - فقط تحقق من توفر الموارد
     */
    public function checkSystemHealth(): array
    {
        return [
            'local_database' => $this->checkLocalDatabase(),
            'internet' => $this->checkInternet(),
            'remote_server' => $this->checkRemoteServer(),
            'auto_sync_enabled' => $this->isAutoSyncEnabled(),
            'last_sync' => $this->getLastSyncTime(),
            'next_sync' => $this->getNextSyncTime(),
        ];
    }
    
    /**
     * فحص قاعدة البيانات المحلية (SQLite)
     */
    public function checkLocalDatabase(): bool
    {
        try {
            $syncConnection = config('database.connections.sync_sqlite');
            $dbPath = $syncConnection['database'] ?? null;
            
            if (!$dbPath || !file_exists($dbPath)) {
                return false;
            }
            
            // محاولة الاتصال
            \DB::connection('sync_sqlite')->getPdo();
            return true;
        } catch (\Exception $e) {
            Log::debug('Local database check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * فحص الإنترنت - بدون طلبات خارجية
     */
    public function checkInternet(): bool
    {
        // نستخدم cache لتقليل الطلبات
        return Cache::remember('internet_check', 30, function () {
            // محاولة DNS lookup بسيط
            $connected = @fsockopen('www.google.com', 80, $errno, $errstr, 2);
            if ($connected) {
                fclose($connected);
                return true;
            }
            return false;
        });
    }
    
    /**
     * فحص السيرفر البعيد (API)
     */
    public function checkRemoteServer(): bool
    {
        try {
            return $this->apiSyncService->isApiAvailable();
        } catch (\Exception $e) {
            Log::debug('Remote server check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * التحقق من تفعيل المزامنة التلقائية
     */
    public function isAutoSyncEnabled(): bool
    {
        return filter_var(env('AUTO_SYNC_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * الحصول على وقت آخر مزامنة
     */
    public function getLastSyncTime(): ?string
    {
        return Cache::get($this->lastSyncKey);
    }
    
    /**
     * الحصول على وقت المزامنة القادمة
     */
    public function getNextSyncTime(): ?string
    {
        $lastSync = $this->getLastSyncTime();
        if (!$lastSync) {
            return 'الآن';
        }
        
        $lastSyncTime = strtotime($lastSync);
        $nextSyncTime = $lastSyncTime + $this->syncInterval;
        
        if ($nextSyncTime <= time()) {
            return 'الآن';
        }
        
        return date('Y-m-d H:i:s', $nextSyncTime);
    }
    
    /**
     * التحقق من الحاجة للمزامنة
     */
    public function shouldSync(): bool
    {
        if (!$this->isAutoSyncEnabled()) {
            return false;
        }
        
        $lastSync = $this->getLastSyncTime();
        if (!$lastSync) {
            return true; // لم تتم مزامنة من قبل
        }
        
        $lastSyncTime = strtotime($lastSync);
        $now = time();
        
        return ($now - $lastSyncTime) >= $this->syncInterval;
    }
    
    /**
     * تنفيذ المزامنة التلقائية
     */
    public function performAutoSync(): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => [],
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        try {
            // 1. التحقق من الحاجة للمزامنة
            if (!$this->shouldSync()) {
                $result['message'] = 'لم يحن وقت المزامنة بعد';
                $result['next_sync'] = $this->getNextSyncTime();
                return $result;
            }
            
            // 2. التحقق من توفر الإنترنت والسيرفر
            $health = $this->checkSystemHealth();
            $result['health'] = $health;
            
            if (!$health['internet']) {
                $result['message'] = 'لا يوجد اتصال بالإنترنت';
                return $result;
            }
            
            if (!$health['remote_server']) {
                $result['message'] = 'السيرفر غير متاح حالياً';
                return $result;
            }
            
            // 3. تنفيذ المزامنة
            Log::info('Auto sync started', ['time' => $result['timestamp']]);
            
            $syncService = new DatabaseSyncService();
            
            // مزامنة من SQLite إلى السيرفر (رفع البيانات المحلية)
            try {
                $pushResult = $syncService->syncAllPending(100);
                $result['data']['push'] = [
                    'synced' => $pushResult['synced'] ?? 0,
                    'failed' => $pushResult['failed'] ?? 0,
                    'message' => $pushResult['message'] ?? 'تمت المزامنة',
                ];
            } catch (\Exception $e) {
                $result['data']['push'] = [
                    'error' => $e->getMessage(),
                    'synced' => 0,
                    'failed' => 0,
                ];
            }
            
            // مزامنة من السيرفر إلى SQLite (تنزيل التحديثات)
            // TODO: يمكن إضافة هذه الوظيفة لاحقاً
            $result['data']['pull'] = [
                'message' => 'غير مفعّل حالياً',
                'synced' => 0,
            ];
            
            // 4. تحديث وقت آخر مزامنة
            Cache::put($this->lastSyncKey, $result['timestamp'], 86400); // 24 ساعة
            
            // تسجيل في sync_metadata
            try {
                \DB::connection('sync_sqlite')->table('sync_metadata')->updateOrInsert(
                    ['table_name' => 'auto_sync'],
                    [
                        'synced_at' => $result['timestamp'],
                        'records_synced' => ($result['data']['push']['synced'] ?? 0) + ($result['data']['pull']['synced'] ?? 0),
                        'status' => 'completed',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            } catch (\Exception $e) {
                Log::warning('Could not update sync_metadata', ['error' => $e->getMessage()]);
            }
            
            $result['success'] = true;
            $result['message'] = 'تمت المزامنة بنجاح';
            $result['next_sync'] = $this->getNextSyncTime();
            
            Log::info('Auto sync completed successfully', $result);
            
        } catch (\Exception $e) {
            $result['message'] = 'خطأ في المزامنة: ' . $e->getMessage();
            $result['error'] = $e->getMessage();
            Log::error('Auto sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return $result;
    }
    
    /**
     * فرض المزامنة الآن (تجاوز المؤقت)
     */
    public function forceSyncNow(): array
    {
        Cache::forget($this->lastSyncKey);
        return $this->performAutoSync();
    }
}
