<?php

namespace App\Jobs;

use App\Services\DatabaseSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SyncPendingChangesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * عدد مرات إعادة المحاولة (زيادة للتعامل مع MySQL غير المتاح)
     */
    public $tries = 10; // زيادة من 3 إلى 10 للمحاولات المتعددة

    /**
     * الوقت بين المحاولات (بالثواني) - exponential backoff
     */
    public $backoff = [30, 60, 120, 300, 600, 900, 1800, 3600, 7200]; // من 30 ثانية إلى ساعتين

    /**
     * timeout للـ job (بالثواني)
     */
    public $timeout = 600; // 10 دقائق

    protected $tableName;
    protected $limit;
    protected $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $tableName = null, int $limit = 100, ?string $jobId = null)
    {
        $this->tableName = $tableName;
        $this->limit = $limit;
        $this->jobId = $jobId ?? uniqid('sync_', true);
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info('Starting background sync job', [
                'job_id' => $this->jobId,
                'table_name' => $this->tableName,
                'limit' => $this->limit,
            ]);

            // حفظ حالة البدء في Cache
            $this->updateStatus([
                'status' => 'running',
                'started_at' => now()->toDateTimeString(),
                'synced' => 0,
                'failed' => 0,
                'errors' => [],
            ]);

            $syncService = new DatabaseSyncService();
            
            // التحقق من نوع المزامنة (API أم MySQL) قبل البدء
            // استخدام reflection للوصول إلى protected property useApi
            $reflection = new \ReflectionClass($syncService);
            $useApiProperty = $reflection->getProperty('useApi');
            $useApiProperty->setAccessible(true);
            $useApi = $useApiProperty->getValue($syncService);
            
            if ($useApi) {
                // إذا كان API Sync مفعّل، تحقق من API وليس MySQL
                $apiSyncServiceProperty = $reflection->getProperty('apiSyncService');
                $apiSyncServiceProperty->setAccessible(true);
                $apiSyncService = $apiSyncServiceProperty->getValue($syncService);
                
                if (!$apiSyncService || !$apiSyncService->isApiAvailable()) {
                    $attempt = $this->attempts();
                    $nextRetryIn = $this->backoff[$attempt - 1] ?? 30;
                    $errorMsg = 'API غير متاح - سيتم إعادة المحاولة تلقائياً عندما يعود الاتصال';
                    
                    $this->updateStatus([
                        'status' => 'waiting',
                        'message' => $errorMsg,
                        'will_retry' => true,
                        'attempt' => $attempt,
                        'max_tries' => $this->tries,
                        'next_retry_at' => now()->addSeconds($nextRetryIn)->toDateTimeString(),
                        'next_retry_in_seconds' => $nextRetryIn,
                    ]);
                    
                    Log::info('API not available, job will retry', [
                        'job_id' => $this->jobId,
                        'attempt' => $attempt,
                        'max_tries' => $this->tries,
                        'next_retry_in' => $nextRetryIn,
                    ]);
                    
                    throw new \Exception($errorMsg); // لإعادة المحاولة
                }
                
                Log::info('API Sync enabled, skipping MySQL check', [
                    'job_id' => $this->jobId,
                ]);
            } else {
                // إذا كان MySQL Sync مفعّل، تحقق من MySQL
                try {
                    DB::connection('mysql')->getPdo();
                    DB::connection('mysql')->select('SELECT 1');
                } catch (\Exception $e) {
                    $attempt = $this->attempts();
                    $nextRetryIn = $this->backoff[$attempt - 1] ?? 30;
                    $errorMsg = 'MySQL غير متاح - سيتم إعادة المحاولة تلقائياً عندما يعود الاتصال';
                    
                    $this->updateStatus([
                        'status' => 'waiting',
                        'message' => $errorMsg,
                        'will_retry' => true,
                        'attempt' => $attempt,
                        'max_tries' => $this->tries,
                        'next_retry_at' => now()->addSeconds($nextRetryIn)->toDateTimeString(),
                        'next_retry_in_seconds' => $nextRetryIn,
                    ]);
                    
                    Log::info('MySQL not available, job will retry', [
                        'job_id' => $this->jobId,
                        'attempt' => $attempt,
                        'max_tries' => $this->tries,
                        'next_retry_in' => $nextRetryIn,
                        'error' => $e->getMessage(),
                    ]);
                    
                    throw new \Exception($errorMsg); // لإعادة المحاولة
                }
            }
            
            Log::info('Calling syncPendingChanges', [
                'job_id' => $this->jobId,
                'table_name' => $this->tableName,
                'limit' => $this->limit,
            ]);
            
            $results = $syncService->syncPendingChanges($this->tableName, $this->limit, 300);
            
            Log::info('syncPendingChanges returned', [
                'job_id' => $this->jobId,
                'synced' => $results['synced'] ?? 0,
                'failed' => $results['failed'] ?? 0,
                'errors_count' => count($results['errors'] ?? []),
                'has_message' => !empty($results['message'] ?? null),
            ]);

            // حفظ النتائج في Cache
            $this->updateStatus([
                'status' => 'completed',
                'completed_at' => now()->toDateTimeString(),
                'synced' => $results['synced'] ?? 0,
                'failed' => $results['failed'] ?? 0,
                'errors' => $results['errors'] ?? [],
                'elapsed_time' => $results['elapsed_time'] ?? 0,
                'total_processed' => $results['total_processed'] ?? 0,
            ]);

            Log::info('Background sync job completed', [
                'job_id' => $this->jobId,
                'synced' => $results['synced'] ?? 0,
                'failed' => $results['failed'] ?? 0,
                'total_processed' => $results['total_processed'] ?? 0,
            ]);

            // حذف الحالة بعد 5 دقائق من اكتمال المزامنة
            Cache::put("sync_status_{$this->jobId}", null, now()->addMinutes(5));

        } catch (\Exception $e) {
            Log::error('Background sync job failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // حفظ حالة الفشل
            $this->updateStatus([
                'status' => 'failed',
                'completed_at' => now()->toDateTimeString(),
                'error' => $e->getMessage(),
            ]);

            throw $e; // لإعادة المحاولة
        }
    }

    /**
     * تحديث حالة المزامنة في Cache
     */
    protected function updateStatus(array $status)
    {
        $currentStatus = Cache::get("sync_status_{$this->jobId}", []);
        $newStatus = array_merge($currentStatus, $status);
        Cache::put("sync_status_{$this->jobId}", $newStatus, now()->addHours(1));
    }

    /**
     * الحصول على معرف الـ Job
     */
    public function getJobId(): string
    {
        return $this->jobId;
    }
}

