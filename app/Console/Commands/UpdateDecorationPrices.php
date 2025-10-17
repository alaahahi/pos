<?php

namespace App\Console\Commands;

use App\Models\Decoration;
use App\Models\SystemConfig;
use Illuminate\Console\Command;

class UpdateDecorationPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'decorations:update-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update decoration prices to include both currencies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating decoration prices...');
        
        // Get system config for exchange rate
        $systemConfig = SystemConfig::first();
        $exchangeRate = $systemConfig ? $systemConfig->exchange_rate : 1500;
        
        $this->info("Using exchange rate: {$exchangeRate}");
        
        $decorations = Decoration::all();
        $updated = 0;
        
        foreach ($decorations as $decoration) {
            // Check if decoration needs price updates
            $needsUpdate = false;
            $updates = [];
            
            // If base_price_dinar is missing, calculate it
            if (!$decoration->base_price_dinar || $decoration->base_price_dinar <= 0) {
                if ($decoration->base_price && $decoration->currency === 'dinar') {
                    $updates['base_price_dinar'] = $decoration->base_price;
                    $updates['base_price_dollar'] = round($decoration->base_price / $exchangeRate, 2);
                    $needsUpdate = true;
                } elseif ($decoration->base_price_dollar && $decoration->base_price_dollar > 0) {
                    $updates['base_price_dinar'] = round($decoration->base_price_dollar * $exchangeRate, 2);
                    $needsUpdate = true;
                }
            }
            
            // If base_price_dollar is missing, calculate it
            if (!$decoration->base_price_dollar || $decoration->base_price_dollar <= 0) {
                if ($decoration->base_price && $decoration->currency === 'dollar') {
                    $updates['base_price_dollar'] = $decoration->base_price;
                    $updates['base_price_dinar'] = round($decoration->base_price * $exchangeRate, 2);
                    $needsUpdate = true;
                } elseif ($decoration->base_price_dinar && $decoration->base_price_dinar > 0) {
                    $updates['base_price_dollar'] = round($decoration->base_price_dinar / $exchangeRate, 2);
                    $needsUpdate = true;
                }
            }
            
            if ($needsUpdate && !empty($updates)) {
                $decoration->update($updates);
                $updated++;
                $this->line("Updated decoration: {$decoration->name}");
            }
        }
        
        $this->info("Updated {$updated} decorations successfully!");
        
        return 0;
    }
}

