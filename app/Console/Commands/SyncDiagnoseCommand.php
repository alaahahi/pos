<?php

namespace App\Console\Commands;

use App\Services\ApiSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncDiagnoseCommand extends Command
{
    protected $signature = 'sync:diagnose';

    protected $description = 'Print sync environment checklist: DB connection, SYNC_VIA_API, API reachability, sync_queue stats';

    public function handle(): int
    {
        $this->info('=== Sync diagnosis ===');

        $default = config('database.default');
        $this->line('database.default: '.$default);

        $syncViaApi = filter_var(env('SYNC_VIA_API', false), FILTER_VALIDATE_BOOLEAN);
        $this->line('SYNC_VIA_API: '.($syncViaApi ? 'true' : 'false'));

        $onlineUrl = rtrim((string) env('ONLINE_URL', ''), '/');
        $this->line('ONLINE_URL: '.($onlineUrl !== '' ? $onlineUrl : '(empty)'));

        $tokenSet = ! empty(env('SYNC_API_TOKEN'));
        $this->line('SYNC_API_TOKEN set: '.($tokenSet ? 'yes' : 'no'));

        $queueConn = config('queue.default');
        $this->line('queue.default: '.$queueConn);

        $sqliteOk = in_array($default, ['sync_sqlite', 'sqlite'], true);
        $this->line('SQLite mode (required for sync_queue writes): '.($sqliteOk ? 'yes' : 'no'));

        try {
            if (Schema::connection($default)->hasTable('sync_queue')) {
                $pending = DB::connection($default)->table('sync_queue')->where('status', 'pending')->count();
                $failed = DB::connection($default)->table('sync_queue')->where('status', 'failed')->count();
                $synced = DB::connection($default)->table('sync_queue')->where('status', 'synced')->count();
                $this->line("sync_queue: pending={$pending}, failed={$failed}, synced={$synced}");
            } else {
                $this->warn('sync_queue table does not exist on default connection.');
            }
        } catch (\Throwable $e) {
            $this->error('sync_queue check failed: '.$e->getMessage());
        }

        try {
            $api = new ApiSyncService();
            $available = $api->isApiAvailable();
            $this->line('API reachable (cached health): '.($available ? 'yes' : 'no'));
        } catch (\Throwable $e) {
            $this->error('API check failed: '.$e->getMessage());
        }

        $this->newLine();
        $this->comment('Tips: after editing a Product locally, pending should increase if SyncQueueModelObserver is registered.');
        $this->comment('Run smart sync or worker only after pending > 0 and SYNC_VIA_API=true.');

        return self::SUCCESS;
    }
}
