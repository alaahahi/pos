<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $type;

    /**
     * عدد مرات إعادة المحاولة
     */
    public $tries = 3;

    /**
     * الوقت بين المحاولات (بالثواني)
     */
    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info("معالجة مزامنة البيانات من نوع: {$this->type}");

            switch ($this->type) {
                case 'car':
                    $this->syncCar();
                    break;
                case 'contract':
                    $this->syncContract();
                    break;
                case 'transaction':
                    $this->syncTransaction();
                    break;
                default:
                    Log::warning("نوع غير معروف: {$this->type}");
            }

            Log::info("تمت مزامنة {$this->type} بنجاح");
        } catch (\Exception $e) {
            Log::error("فشل مزامنة {$this->type}: " . $e->getMessage());
            throw $e; // لإعادة المحاولة
        }
    }

    /**
     * مزامنة السيارة
     */
    protected function syncCar()
    {
        // منطق مزامنة السيارة
        // يمكن تخصيصه حسب الحاجة
    }

    /**
     * مزامنة العقد
     */
    protected function syncContract()
    {
        // منطق مزامنة العقد
    }

    /**
     * مزامنة المعاملة
     */
    protected function syncTransaction()
    {
        // منطق مزامنة المعاملة
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("فشلت كل محاولات مزامنة {$this->type}: " . $exception->getMessage());
        
        // يمكن إرسال إشعار للمسؤول
        // Notification::route('mail', 'admin@example.com')->notify(new SyncFailedNotification($this->type, $this->data));
    }
}

