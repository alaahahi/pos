<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class UpdateBestSellingProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:update-best-selling {--limit=20 : Number of top products to mark as best selling}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update best selling products based on sales count';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        
        $this->info("Updating best selling products (top {$limit})...");
        
        // Reset all best selling flags
        Product::query()->update(['is_best_selling' => false]);
        
        // Get top selling products based on sales_count
        $topProducts = Product::where('is_active', true)
            ->where('sales_count', '>', 0)
            ->orderBy('sales_count', 'desc')
            ->limit($limit)
            ->get();
        
        // Mark them as best selling
        foreach ($topProducts as $product) {
            $product->update(['is_best_selling' => true]);
        }
        
        $this->info("Updated {$topProducts->count()} products as best selling.");
        
        // Show the top products
        if ($topProducts->count() > 0) {
            $this->table(
                ['ID', 'Name', 'Sales Count'],
                $topProducts->map(function ($product) {
                    return [
                        $product->id,
                        $product->name,
                        $product->sales_count
                    ];
                })
            );
        } else {
            $this->warn('No products with sales found.');
        }
        
        return Command::SUCCESS;
    }
}