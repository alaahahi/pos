<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Box;
use App\Models\DailyClose;

class AutoCloseDailyBoxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting auto daily close job');
            
            $today = now()->format('Y-m-d');
            
            // البحث عن الصناديق المفتوحة لهذا اليوم والتي لم يتم إغلاقها
            $openBoxes = Box::where('is_open', true)
                ->whereDate('created_at', $today)
                ->get();
            
            if ($openBoxes->isEmpty()) {
                Log::info('No open boxes found for auto close');
                return;
            }
            
            $closed = 0;
            $failed = 0;
            
            foreach ($openBoxes as $box) {
                // التحقق من عدم وجود إغلاق يومي لهذا الصندوق اليوم
                $existingClose = DailyClose::where('box_id', $box->id)
                    ->whereDate('date', $today)
                    ->first();
                
                if ($existingClose) {
                    Log::info('Box already has daily close record', [
                        'box_id' => $box->id,
                        'close_id' => $existingClose->id
                    ]);
                    continue;
                }
                
                try {
                    DB::beginTransaction();
                    
                    // حساب المبالغ
                    $expectedCash = $this->calculateExpectedCash($box);
                    $actualCash = $box->balance; // أو يمكن استخدام قيمة افتراضية
                    $difference = $actualCash - $expectedCash;
                    
                    // إنشاء سجل الإغلاق اليومي
                    $dailyClose = DailyClose::create([
                        'box_id' => $box->id,
                        'date' => $today,
                        'opening_balance' => $box->opening_balance ?? 0,
                        'closing_balance' => $box->balance,
                        'expected_cash' => $expectedCash,
                        'actual_cash' => $actualCash,
                        'difference' => $difference,
                        'total_sales' => $this->getTotalSales($box),
                        'total_expenses' => $this->getTotalExpenses($box),
                        'total_deposits' => $this->getTotalDeposits($box),
                        'total_withdrawals' => $this->getTotalWithdrawals($box),
                        'notes' => 'إغلاق تلقائي - Auto Close',
                        'closed_by' => null, // يمكن تعيين مستخدم النظام
                        'closed_at' => now(),
                        'is_auto_closed' => true,
                    ]);
                    
                    // تحديث حالة الصندوق
                    $box->update([
                        'is_open' => false,
                        'last_closed_at' => now(),
                    ]);
                    
                    DB::commit();
                    
                    Log::info('Box closed automatically', [
                        'box_id' => $box->id,
                        'daily_close_id' => $dailyClose->id,
                        'closing_balance' => $box->balance,
                        'difference' => $difference
                    ]);
                    
                    $closed++;
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    
                    Log::error('Failed to auto close box', [
                        'box_id' => $box->id,
                        'error' => $e->getMessage()
                    ]);
                    
                    $failed++;
                }
            }
            
            Log::info('Auto daily close job completed', [
                'closed' => $closed,
                'failed' => $failed,
                'total' => $openBoxes->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Auto daily close job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * حساب النقد المتوقع
     */
    protected function calculateExpectedCash(Box $box): float
    {
        // المبيعات النقدية
        $cashSales = DB::table('orders')
            ->where('box_id', $box->id)
            ->where('payment_method', 'cash')
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->sum('final_amount');
        
        // المصروفات
        $expenses = DB::table('expenses')
            ->where('box_id', $box->id)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->sum('amount');
        
        // الإيداعات والسحوبات من cashbox_transactions
        $deposits = DB::table('cashbox_transactions')
            ->where('box_id', $box->id)
            ->where('type', 'deposit')
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->sum('amount');
        
        $withdrawals = DB::table('cashbox_transactions')
            ->where('box_id', $box->id)
            ->where('type', 'withdrawal')
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->sum('amount');
        
        $openingBalance = $box->opening_balance ?? 0;
        
        return $openingBalance + $cashSales + $deposits - $expenses - $withdrawals;
    }
    
    /**
     * إجمالي المبيعات
     */
    protected function getTotalSales(Box $box): float
    {
        return DB::table('orders')
            ->where('box_id', $box->id)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->sum('final_amount');
    }
    
    /**
     * إجمالي المصروفات
     */
    protected function getTotalExpenses(Box $box): float
    {
        return DB::table('expenses')
            ->where('box_id', $box->id)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->sum('amount');
    }
    
    /**
     * إجمالي الإيداعات
     */
    protected function getTotalDeposits(Box $box): float
    {
        return DB::table('cashbox_transactions')
            ->where('box_id', $box->id)
            ->where('type', 'deposit')
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->sum('amount');
    }
    
    /**
     * إجمالي السحوبات
     */
    protected function getTotalWithdrawals(Box $box): float
    {
        return DB::table('cashbox_transactions')
            ->where('box_id', $box->id)
            ->where('type', 'withdrawal')
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->sum('amount');
    }
}
