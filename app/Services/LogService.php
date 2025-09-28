<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;

class LogService
{
    /**
     * Create a log entry
     */
    public static function createLog(string $moduleName, string $action, int $recordId, array $originalData = [], array $updatedData = [], string $badge = 'info'): void
    {
        Log::create([
            'module_name' => $moduleName,
            'action' => $action,
            'badge' => $badge,
            'affected_record_id' => $recordId,
            'original_data' => json_encode($originalData),
            'updated_data' => json_encode($updatedData),
            'by_user_id' => Auth::id(),
        ]);
    }

    /**
     * Log product creation
     */
    public static function logProductCreated($product): void
    {
        self::createLog(
            'Product',
            'Created',
            $product->id,
            [],
            $product->toArray(),
            'success'
        );
    }

    /**
     * Log product update
     */
    public static function logProductUpdated($product, array $originalData): void
    {
        self::createLog(
            'Product',
            'Updated',
            $product->id,
            $originalData,
            $product->toArray(),
            'warning'
        );
    }

    /**
     * Log product deletion
     */
    public static function logProductDeleted($product): void
    {
        self::createLog(
            'Product',
            'Deleted',
            $product->id,
            $product->toArray(),
            [],
            'danger'
        );
    }

    /**
     * Log order creation
     */
    public static function logOrderCreated($order): void
    {
        self::createLog(
            'Order',
            'Created',
            $order->id,
            [],
            $order->toArray(),
            'success'
        );
    }

    /**
     * Log order update
     */
    public static function logOrderUpdated($order, array $originalData): void
    {
        self::createLog(
            'Order',
            'Updated',
            $order->id,
            $originalData,
            $order->toArray(),
            'warning'
        );
    }

    /**
     * Log order deletion
     */
    public static function logOrderDeleted($order): void
    {
        self::createLog(
            'Order',
            'Deleted',
            $order->id,
            $order->toArray(),
            [],
            'danger'
        );
    }

    /**
     * Log payment
     */
    public static function logPayment($order, float $amount, string $method): void
    {
        self::createLog(
            'Payment',
            'Payment Received',
            $order->id,
            [],
            ['amount' => $amount, 'method' => $method],
            'success'
        );
    }

    /**
     * Get log statistics
     */
    public static function getLogStatistics(): array
    {
        $totalLogs = Log::count();
        $todayLogs = Log::whereDate('created_at', today())->count();
        $thisWeekLogs = Log::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $thisMonthLogs = Log::whereMonth('created_at', now()->month)->count();

        $logsByModule = Log::selectRaw('module_name, COUNT(*) as count')
            ->groupBy('module_name')
            ->orderBy('count', 'desc')
            ->get();

        $logsByAction = Log::selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->get();

        return [
            'total' => $totalLogs,
            'today' => $todayLogs,
            'this_week' => $thisWeekLogs,
            'this_month' => $thisMonthLogs,
            'by_module' => $logsByModule,
            'by_action' => $logsByAction,
        ];
    }
}
