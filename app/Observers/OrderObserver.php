<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Log;
use App\Services\SyncQueueService;
use Illuminate\Support\Facades\DB;

class OrderObserver 
{
    protected $syncQueueService;

    public function __construct()
    {
        $this->syncQueueService = new SyncQueueService();
    }

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        try {
            // إضافة Order إلى sync_queue
            $this->syncQueueService->queueInsert('orders', $order->id, $order->toArray());

            // مزامنة pivot table (order_product) - العلاقة many-to-many
            // ملاحظة: قد لا تكون المنتجات مرتبطة بعد في هذا الوقت
            // سيتم استدعاء syncOrderProducts من حدث updated بعد إرفاق المنتجات
            $this->syncOrderProducts($order);

            // مزامنة transactions المرتبطة (إذا كانت موجودة)
            // ملاحظة: قد لا تكون transactions موجودة بعد في هذا الوقت
            // سيتم استدعاؤها من حدث updated بعد إنشاء transactions
            $this->syncOrderTransactions($order);
        } catch (\Exception $e) {
            \Log::error('OrderObserver::created failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Get the original data before the update
        $originalData = $order->getOriginal();

        // إضافة إلى sync_queue للمزامنة الذكية
        $this->syncQueueService->queueUpdate('orders', $order->id, $originalData, $order->toArray());

        // مزامنة pivot table (order_product) - العلاقة many-to-many
        // عند التحديث، نحذف pivot table القديمة ثم نضيف الجديدة
        $this->syncQueueService->queueChange(
            'order_product',
            $order->id,
            'delete',
            null
        );

        // إضافة المنتجات الجديدة
        $this->syncOrderProducts($order);
    }
    
    /**
     * Handle the Order "saved" event (called after both created and updated)
     * هذا مفيد عندما يتم إرفاق المنتجات بعد إنشاء Order
     */
    public function saved(Order $order): void
    {
        // مزامنة pivot table (order_product) - للتأكد من أن المنتجات المرتبطة تتم مزامنتها
        $this->syncOrderProducts($order);
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        // إضافة إلى sync_queue للمزامنة الذكية
        $this->syncQueueService->queueDelete('orders', $order->id);

        // حذف pivot table (order_product)
        // نستخدم queueChange مع action 'delete' لحذف جميع السجلات المرتبطة
        $this->syncQueueService->queueChange(
            'order_product',
            $order->id,
            'delete',
            null
        );
    }

    /**
     * مزامنة pivot table (order_product)
     */
    protected function syncOrderProducts(Order $order): void
    {
        try {
            // جلب جميع المنتجات المرتبطة بالطلب
            $products = $order->products()->get();
            
            foreach ($products as $product) {
                $pivotData = [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $product->pivot->quantity,
                    'price' => $product->pivot->price,
                    'created_at' => $product->pivot->created_at ?? now(),
                    'updated_at' => $product->pivot->updated_at ?? now(),
                ];

                // إضافة إلى sync_queue
                // نستخدم order_id و product_id كـ composite key
                $this->syncQueueService->queueChange(
                    'order_product',
                    $order->id, // نستخدم order_id كـ record_id
                    'insert',
                    $pivotData
                );
            }
        } catch (\Exception $e) {
            \Log::error('Failed to sync order products', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * مزامنة transactions المرتبطة بالطلب
     */
    protected function syncOrderTransactions(Order $order): void
    {
        try {
            // جلب جميع المعاملات المالية المرتبطة بالطلب
            $transactions = DB::table('transactions')
                ->where('transactionable_id', $order->id)
                ->where('transactionable_type', Order::class)
                ->get();

            foreach ($transactions as $transaction) {
                $transactionData = (array) $transaction;
                $this->syncQueueService->queueInsert('transactions', $transaction->id, $transactionData);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to sync order transactions', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        // عند الاستعادة، نعيد المزامنة
        $this->syncQueueService->queueInsert('orders', $order->id, $order->toArray());
        $this->syncOrderProducts($order);
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        // نفس معالجة الحذف العادي
        $this->deleted($order);
    }
}

