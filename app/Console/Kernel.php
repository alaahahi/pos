<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        
        // Clean old logs every month
        $schedule->command('logs:clean --days=30')->monthly();
        
        // المزامنة التلقائية الجديدة - Offline First
        // تعمل كل 5 دقائق وتزامن تلقائياً إذا كان الإنترنت متاح
        $schedule->command('sync:auto')
            ->everyFiveMinutes()
            ->name('auto-sync-offline-first')
            ->withoutOverlapping(10)
            ->runInBackground();
        
        // مزامنة تلقائية كل 5 دقائق (من المحلي إلى السيرفر)
        // يعمل فقط إذا كان API Sync مفعّل وكان هناك إنترنت
        // يستخدم SyncPendingChangesJob الموجود للمزامنة
        $schedule->call(function () {
            try {
                // التحقق من أن API Sync مفعّل
                if (!env('SYNC_VIA_API', false)) {
                    \Log::debug('Scheduled sync skipped: API Sync not enabled');
                    return;
                }
                
                // التحقق من توفر API
                $apiSyncService = new \App\Services\ApiSyncService();
                if (!$apiSyncService->isApiAvailable()) {
                    \Log::debug('Scheduled sync skipped: API not available (no internet)');
                    return;
                }
                
                // التحقق من وجود سجلات pending في sync_queue
                $connection = config('database.default');
                $pendingCount = \Illuminate\Support\Facades\DB::connection($connection)
                    ->table('sync_queue')
                    ->where('status', 'pending')
                    ->count();
                
                if ($pendingCount === 0) {
                    \Log::debug('Scheduled sync skipped: No pending changes');
                    return;
                }
                
                \Log::info('Dispatching scheduled sync job', [
                    'pending_count' => $pendingCount,
                ]);
                
                // استخدام SyncPendingChangesJob الموجود للمزامنة
                // هذا Job يحتوي على retry mechanism وstatus tracking
                $job = new \App\Jobs\SyncPendingChangesJob(null, 100); // مزامنة حتى 100 سجل كل 5 دقائق
                dispatch($job)->onQueue('sync');
                
                \Log::info('Scheduled sync job dispatched successfully', [
                    'job_id' => $job->getJobId(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Scheduled sync dispatch failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        })->everyFiveMinutes()
          ->name('auto-sync-to-server')
          ->withoutOverlapping(10); // منع التداخل - انتظر 10 دقائق إذا كان job آخر يعمل
        
        // مزامنة تلقائية من السيرفر إلى المحلي كل 10 دقائق (إذا كان هناك إنترنت)
        // يعمل فقط في النظام المحلي
        if (config('app.env') === 'local' || str_contains(config('app.url'), 'local') || str_contains(config('app.url'), '127.0.0.1')) {
            $schedule->call(function () {
                try {
                    // التحقق من أن API Sync مفعّل
                    if (!env('SYNC_VIA_API', false)) {
                        \Log::debug('Scheduled sync from server skipped: API Sync not enabled');
                        return;
                    }
                    
                    // التحقق من توفر API
                    $apiSyncService = new \App\Services\ApiSyncService();
                    if (!$apiSyncService->isApiAvailable()) {
                        \Log::debug('Scheduled sync from server skipped: API not available (no internet)');
                        return;
                    }
                    
                    \Log::info('Starting scheduled sync from server to local');
                    
                    // مزامنة جدول orders من السيرفر إلى المحلي
                    $controller = new \App\Http\Controllers\SyncMonitorController();
                    $request = new \Illuminate\Http\Request(['table_name' => 'orders', 'limit' => 1000]);
                    $response = $controller->syncFromServer($request);
                    
                    $responseData = json_decode($response->getContent(), true);
                    \Log::info('Scheduled sync from server completed', [
                        'synced' => $responseData['synced'] ?? 0,
                        'failed' => $responseData['failed'] ?? 0,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Scheduled sync from server failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            })->everyTenMinutes()
              ->name('auto-sync-from-server')
              ->withoutOverlapping(15); // منع التداخل - انتظر 15 دقيقة إذا كان job آخر يعمل
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
