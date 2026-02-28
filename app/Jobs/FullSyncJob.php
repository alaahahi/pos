<?php

namespace App\Jobs;

use App\Http\Controllers\SyncMonitorController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FullSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 دقيقة
    public $tries = 1;

    protected string $jobId;
    protected string $direction;
    protected $tables;
    protected bool $safeMode;
    protected bool $createBackup;

    public function __construct(string $direction, $tables, bool $safeMode, bool $createBackup, string $jobId = null)
    {
        $this->direction = $direction;
        $this->tables = $tables;
        $this->safeMode = $safeMode;
        $this->createBackup = $createBackup;
        $this->jobId = $jobId ?? uniqid('fullsync_', true);
    }

    public function handle(): void
    {
        try {
            Cache::put("sync_status_{$this->jobId}", [
                'status' => 'running',
                'started_at' => now()->toDateTimeString(),
                'direction' => $this->direction,
            ], now()->addHours(1));

            $controller = app(SyncMonitorController::class);
            $result = $controller->runSyncInBackground(
                $this->direction,
                $this->tables,
                $this->safeMode,
                $this->createBackup,
                $this->jobId
            );

            Cache::put("sync_status_{$this->jobId}", array_merge($result, [
                'status' => 'completed',
                'completed_at' => now()->toDateTimeString(),
            ]), now()->addHours(1));
        } catch (\Throwable $e) {
            Log::error('FullSyncJob failed', ['job_id' => $this->jobId, 'error' => $e->getMessage()]);
            Cache::put("sync_status_{$this->jobId}", [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'completed_at' => now()->toDateTimeString(),
            ], now()->addHours(1));
            throw $e;
        }
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }
}
