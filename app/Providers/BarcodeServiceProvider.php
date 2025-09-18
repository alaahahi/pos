<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\BarcodeService;

class BarcodeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('barcode', function ($app) {
            return new BarcodeService();
        });

        $this->app->alias('barcode', BarcodeService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../../config/barcode.php' => config_path('barcode.php'),
        ], 'barcode-config');

        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/barcode.php', 'barcode'
        );
    }
}