<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AutoSyncService;

class AutoSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sync:auto {--force : Force sync now}';

    /**
     * The console command description.
     */
    protected $description = 'تنفيذ المزامنة التلقائية كل 5 دقائق (للاستخدام مع Scheduler أو Cron)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 بدء المزامنة التلقائية...');
        $this->info('الوقت: ' . date('Y-m-d H:i:s'));
        $this->newLine();
        
        try {
            $autoSync = new AutoSyncService();
            
            // إذا كان --force، فرض المزامنة الآن
            if ($this->option('force')) {
                $this->warn('⚡ فرض المزامنة الآن (تجاوز المؤقت)...');
                $result = $autoSync->forceSyncNow();
            } else {
                $result = $autoSync->performAutoSync();
            }
            
            $this->newLine();
            
            if ($result['success']) {
                $this->info('✅ ' . $result['message']);
                
                if (isset($result['data'])) {
                    $this->newLine();
                    $this->line('📊 تفاصيل المزامنة:');
                    
                    if (isset($result['data']['push'])) {
                        $this->line('  📤 رفع البيانات: ' . json_encode($result['data']['push'], JSON_UNESCAPED_UNICODE));
                    }
                    
                    if (isset($result['data']['pull'])) {
                        $this->line('  📥 تنزيل البيانات: ' . json_encode($result['data']['pull'], JSON_UNESCAPED_UNICODE));
                    }
                }
                
                if (isset($result['next_sync'])) {
                    $this->newLine();
                    $this->info('⏰ المزامنة القادمة: ' . $result['next_sync']);
                }
                
                return Command::SUCCESS;
            } else {
                $this->error('❌ ' . $result['message']);
                
                if (isset($result['health'])) {
                    $this->newLine();
                    $this->line('حالة النظام:');
                    $this->line('  الإنترنت: ' . ($result['health']['internet'] ? '✅ متصل' : '❌ غير متصل'));
                    $this->line('  السيرفر: ' . ($result['health']['remote_server'] ? '✅ متاح' : '❌ غير متاح'));
                    $this->line('  قاعدة البيانات المحلية: ' . ($result['health']['local_database'] ? '✅ متاحة' : '❌ غير متاحة'));
                }
                
                if (isset($result['next_sync'])) {
                    $this->newLine();
                    $this->info('⏰ المزامنة القادمة: ' . $result['next_sync']);
                }
                
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ خطأ في المزامنة التلقائية:');
            $this->error($e->getMessage());
            $this->newLine();
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
