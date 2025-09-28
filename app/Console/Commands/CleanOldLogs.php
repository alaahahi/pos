<?php

namespace App\Console\Commands;

use App\Models\Log;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clean {--days=30 : Number of days to keep logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old logs older than specified days (default: 30 days)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Cleaning logs older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})");
        
        $deletedCount = Log::where('created_at', '<', $cutoffDate)->delete();
        
        $this->info("Successfully deleted {$deletedCount} old log records.");
        
        return Command::SUCCESS;
    }
}