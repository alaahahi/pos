<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailyClose;
use App\Models\MonthlyClose;
use Carbon\Carbon;

class TestClosesList extends Command
{
    protected $signature = 'test:closes-list {--type=all} {--status=all} {--start-date=} {--end-date=} {--year=} {--month=}';
    protected $description = 'Test closes list API with filters';

    public function handle()
    {
        $type = $this->option('type');
        $status = $this->option('status');
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $year = $this->option('year');
        $month = $this->option('month');

        $this->info("Testing Closes List API");
        $this->info(str_repeat('=', 60));
        $this->info("Filters:");
        $this->info("  Type: {$type}");
        $this->info("  Status: {$status}");
        if ($startDate) $this->info("  Start Date: {$startDate}");
        if ($endDate) $this->info("  End Date: {$endDate}");
        if ($year) $this->info("  Year: {$year}");
        if ($month) $this->info("  Month: {$month}");
        $this->info("");

        // Simulate the controller logic
        $dailyCloses = collect();
        $monthlyCloses = collect();

        // Get daily closes
        if ($type === 'all' || $type === 'daily') {
            $dailyQuery = DailyClose::query();

            if ($status !== 'all') {
                $dailyQuery->where('status', $status);
            }

            if ($startDate) {
                $dailyQuery->whereDate('close_date', '>=', $startDate);
            }

            if ($endDate) {
                $dailyQuery->whereDate('close_date', '<=', $endDate);
            }

            $dailyCloses = $dailyQuery->orderBy('close_date', 'desc')->paginate(15);
            $this->info("=== DAILY CLOSES ===");
            $this->info("Total: {$dailyCloses->total()}");
            $this->info("Current Page: {$dailyCloses->currentPage()}");
            $this->info("Per Page: {$dailyCloses->perPage()}");
            $this->info("");
            
            if ($dailyCloses->count() > 0) {
                $this->info("Daily Closes List:");
                foreach ($dailyCloses as $close) {
                    $this->info("  - Date: {$close->close_date}, Status: {$close->status}, Sales IQD: {$close->total_sales_iqd}, Sales USD: {$close->total_sales_usd}");
                }
            } else {
                $this->warn("  No daily closes found");
            }
            $this->info("");
        }

        // Get monthly closes
        if ($type === 'all' || $type === 'monthly') {
            $monthlyQuery = MonthlyClose::query();

            if ($status !== 'all') {
                $monthlyQuery->where('status', $status);
            }

            if ($year) {
                $monthlyQuery->where('year', $year);
            }

            if ($month) {
                $monthlyQuery->where('month', $month);
            }

            $monthlyCloses = $monthlyQuery->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->paginate(15);
            
            $this->info("=== MONTHLY CLOSES ===");
            $this->info("Total: {$monthlyCloses->total()}");
            $this->info("Current Page: {$monthlyCloses->currentPage()}");
            $this->info("Per Page: {$monthlyCloses->perPage()}");
            $this->info("");
            
            if ($monthlyCloses->count() > 0) {
                $this->info("Monthly Closes List:");
                foreach ($monthlyCloses as $close) {
                    $this->info("  - Month: {$close->month}/{$close->year}, Status: {$close->status}, Sales IQD: {$close->total_sales_iqd}, Sales USD: {$close->total_sales_usd}");
                }
            } else {
                $this->warn("  No monthly closes found");
            }
        }

        $this->info("");
        $this->info("✓ Test completed successfully!");
        
        return 0;
    }
}


