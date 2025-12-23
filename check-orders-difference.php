<?php
/**
 * فحص الفرق في عدد الفواتير بين اللوكل والسيرفر
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

echo "========================================\n";
echo "   فحص الفرق في عدد الفواتير\n";
echo "========================================\n\n";

try {
    // 1. عدد الفواتير في SQLite (Local)
    $localCount = 0;
    $localError = null;
    try {
        if (Schema::connection('sync_sqlite')->hasTable('orders')) {
            $localCount = DB::connection('sync_sqlite')->table('orders')->count();
        } else {
            $localError = 'جدول orders غير موجود في SQLite';
        }
    } catch (\Exception $e) {
        $localError = $e->getMessage();
    }
    
    echo "📊 عدد الفواتير في SQLite (Local):\n";
    if ($localError) {
        echo "   ❌ خطأ: {$localError}\n";
    } else {
        echo "   ✅ {$localCount} فاتورة\n";
    }
    echo "\n";
    
    // 2. عدد الفواتير في MySQL (Server)
    $serverCount = 0;
    $serverError = null;
    try {
        if (Schema::connection('mysql')->hasTable('orders')) {
            $serverCount = DB::connection('mysql')->table('orders')->count();
        } else {
            $serverError = 'جدول orders غير موجود في MySQL';
        }
    } catch (\Exception $e) {
        $serverError = $e->getMessage();
    }
    
    echo "🌐 عدد الفواتير في MySQL (Server):\n";
    if ($serverError) {
        echo "   ❌ خطأ: {$serverError}\n";
    } else {
        echo "   ✅ {$serverCount} فاتورة\n";
    }
    echo "\n";
    
    // 3. حساب الفرق
    if (!$localError && !$serverError) {
        $difference = $localCount - $serverCount;
        echo "📈 الفرق:\n";
        echo "   الفرق: {$difference} فاتورة\n";
        
        if ($difference > 0) {
            echo "   ⚠️  يوجد {$difference} فاتورة في اللوكل غير موجودة في السيرفر\n";
        } elseif ($difference < 0) {
            echo "   ⚠️  يوجد " . abs($difference) . " فاتورة في السيرفر غير موجودة في اللوكل\n";
        } else {
            echo "   ✅ العدد متطابق\n";
        }
        echo "\n";
    }
    
    // 4. فحص sync_queue للفواتير
    echo "📋 حالة sync_queue للفواتير:\n";
    try {
        $connection = config('database.default');
        if (Schema::connection($connection)->hasTable('sync_queue')) {
            $pendingOrders = DB::connection($connection)
                ->table('sync_queue')
                ->where('table_name', 'orders')
                ->where('status', 'pending')
                ->count();
            
            $syncedOrders = DB::connection($connection)
                ->table('sync_queue')
                ->where('table_name', 'orders')
                ->where('status', 'synced')
                ->count();
            
            $failedOrders = DB::connection($connection)
                ->table('sync_queue')
                ->where('table_name', 'orders')
                ->where('status', 'failed')
                ->count();
            
            echo "   Pending: {$pendingOrders}\n";
            echo "   Synced: {$syncedOrders}\n";
            echo "   Failed: {$failedOrders}\n";
            
            if ($pendingOrders > 0) {
                echo "   ⚠️  يوجد {$pendingOrders} فاتورة في انتظار المزامنة\n";
            }
            
            if ($failedOrders > 0) {
                echo "   ❌ يوجد {$failedOrders} فاتورة فاشلة في المزامنة\n";
                
                // عرض تفاصيل الفواتير الفاشلة
                $failedRecords = DB::connection($connection)
                    ->table('sync_queue')
                    ->where('table_name', 'orders')
                    ->where('status', 'failed')
                    ->limit(10)
                    ->get(['id', 'record_id', 'action', 'error_message', 'created_at']);
                
                if ($failedRecords->count() > 0) {
                    echo "\n   تفاصيل الفواتير الفاشلة:\n";
                    foreach ($failedRecords as $record) {
                        echo "   - ID: {$record->record_id}, Action: {$record->action}, Error: " . substr($record->error_message ?? 'غير محدد', 0, 50) . "...\n";
                    }
                }
            }
        } else {
            echo "   ⚠️  جدول sync_queue غير موجود\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ خطأ: {$e->getMessage()}\n";
    }
    echo "\n";
    
    // 5. فحص ID mappings للفواتير
    echo "🔗 ID Mappings للفواتير:\n";
    try {
        $connection = config('database.default');
        if (Schema::connection($connection)->hasTable('sync_id_mapping')) {
            $mappingsCount = DB::connection($connection)
                ->table('sync_id_mapping')
                ->where('table_name', 'orders')
                ->count();
            
            echo "   عدد الـ mappings: {$mappingsCount}\n";
            
            // فحص إذا كان هناك local_id != server_id
            $differentMappings = DB::connection($connection)
                ->table('sync_id_mapping')
                ->where('table_name', 'orders')
                ->whereColumn('local_id', '!=', 'server_id')
                ->count();
            
            if ($differentMappings > 0) {
                echo "   ⚠️  يوجد {$differentMappings} mapping مع ID مختلف\n";
            }
        } else {
            echo "   ⚠️  جدول sync_id_mapping غير موجود\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ خطأ: {$e->getMessage()}\n";
    }
    echo "\n";
    
    // 6. مقارنة IDs
    if (!$localError && !$serverError && $difference != 0) {
        echo "🔍 تحليل IDs:\n";
        
        try {
            // جلب IDs من SQLite
            $localIds = DB::connection('sync_sqlite')
                ->table('orders')
                ->pluck('id')
                ->toArray();
            
            // جلب IDs من MySQL
            $serverIds = DB::connection('mysql')
                ->table('orders')
                ->pluck('id')
                ->toArray();
            
            // IDs موجودة في Local لكن غير موجودة في Server
            $missingInServer = array_diff($localIds, $serverIds);
            
            // IDs موجودة في Server لكن غير موجودة في Local
            $missingInLocal = array_diff($serverIds, $localIds);
            
            if (count($missingInServer) > 0) {
                echo "   ⚠️  IDs موجودة في Local لكن غير موجودة في Server:\n";
                $sample = array_slice($missingInServer, 0, 10);
                echo "   " . implode(', ', $sample);
                if (count($missingInServer) > 10) {
                    echo " ... و " . (count($missingInServer) - 10) . " أخرى";
                }
                echo "\n";
            }
            
            if (count($missingInLocal) > 0) {
                echo "   ⚠️  IDs موجودة في Server لكن غير موجودة في Local:\n";
                $sample = array_slice($missingInLocal, 0, 10);
                echo "   " . implode(', ', $sample);
                if (count($missingInLocal) > 10) {
                    echo " ... و " . (count($missingInLocal) - 10) . " أخرى";
                }
                echo "\n";
            }
        } catch (\Exception $e) {
            echo "   ❌ خطأ في تحليل IDs: {$e->getMessage()}\n";
        }
        echo "\n";
    }
    
    // 7. التوصيات
    echo "💡 التوصيات:\n";
    if (!$localError && !$serverError) {
        if ($difference > 0) {
            echo "   1. قم بتشغيل المزامنة الذكية لمزامنة الفواتير المتبقية\n";
            echo "   2. تحقق من sync_queue للفواتير الفاشلة\n";
            echo "   3. إذا استمرت المشكلة، تحقق من الـ API endpoint على السيرفر\n";
        } elseif ($difference < 0) {
            echo "   1. قد تكون بعض الفواتير تم إنشاؤها مباشرة على السيرفر\n";
            echo "   2. قم بمزامنة من السيرفر إلى اللوكل (down sync)\n";
        }
    }
    
    echo "\n";
    echo "========================================\n\n";
    
} catch (\Exception $e) {
    echo "❌ خطأ عام: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}

