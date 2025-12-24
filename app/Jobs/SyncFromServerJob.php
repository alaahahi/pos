<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SyncFromServerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * عدد مرات إعادة المحاولة
     */
    public $tries = 5;

    /**
     * الوقت بين المحاولات (بالثواني)
     */
    public $backoff = [30, 60, 120, 300, 600];

    /**
     * timeout للـ job (بالثواني)
     */
    public $timeout = 600; // 10 دقائق

    protected $tableName;
    protected $recordId;
    protected $action; // insert, update, delete
    protected $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $tableName, int $recordId, string $action = 'insert', ?string $jobId = null)
    {
        $this->tableName = $tableName;
        $this->recordId = $recordId;
        $this->action = $action;
        $this->jobId = $jobId ?? uniqid('sync_from_server_', true);
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info('Starting sync from server job', [
                'job_id' => $this->jobId,
                'table_name' => $this->tableName,
                'record_id' => $this->recordId,
                'action' => $this->action,
            ]);

            // حفظ حالة البدء في Cache
            $this->updateStatus([
                'status' => 'running',
                'started_at' => now()->toDateTimeString(),
            ]);

            // التحقق من أننا على المحلي (هذا Job يعمل على المحلي فقط)
            $appEnv = config('app.env');
            if ($appEnv === 'server' || $appEnv === 'production') {
                // على السيرفر: لا يمكن تنفيذ المزامنة من السيرفر إلى المحلي
                // هذا Job يجب أن يعمل على المحلي فقط
                throw new \Exception('هذا Job يجب أن يعمل على النظام المحلي فقط');
            }

            // استدعاء SyncMonitorController@syncFromServer
            $controller = new \App\Http\Controllers\SyncMonitorController();
            $request = new \Illuminate\Http\Request([
                'table_name' => $this->tableName,
                'limit' => 1000,
            ]);

            $response = $controller->syncFromServer($request);
            $responseData = json_decode($response->getContent(), true);

            if ($responseData['success'] ?? false) {
                // حفظ حالة النجاح
                $this->updateStatus([
                    'status' => 'completed',
                    'completed_at' => now()->toDateTimeString(),
                    'synced' => $responseData['synced'] ?? 0,
                    'failed' => $responseData['failed'] ?? 0,
                ]);

                Log::info('Sync from server job completed', [
                    'job_id' => $this->jobId,
                    'synced' => $responseData['synced'] ?? 0,
                ]);
            } else {
                throw new \Exception($responseData['message'] ?? 'فشلت المزامنة من السيرفر');
            }

            // حذف الحالة بعد 5 دقائق من اكتمال المزامنة
            Cache::put("sync_from_server_status_{$this->jobId}", null, now()->addMinutes(5));

        } catch (\Exception $e) {
            Log::error('Sync from server job failed', [
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
        $currentStatus = Cache::get("sync_from_server_status_{$this->jobId}", []);
        $newStatus = array_merge($currentStatus, [
            'job_id' => $this->jobId,
            'table_name' => $this->tableName,
            'record_id' => $this->recordId,
            'action' => $this->action,
        ], $status);
        Cache::put("sync_from_server_status_{$this->jobId}", $newStatus, now()->addHours(1));
    }

    /**
     * الحصول على معرف الـ Job
     */
    public function getJobId(): string
    {
        return $this->jobId;
    }
}

