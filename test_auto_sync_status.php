<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "═══════════════════════════════════════════════════════════\n";
echo "        اختبار حالة المزامنة التلقائية\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// 1. فحص البيئة
echo "1️⃣ البيئة:\n";
echo "────────────────────────────────────\n";
echo "APP_ENV: " . env('APP_ENV', 'production') . "\n";
echo "APP_URL: " . env('APP_URL', 'غير محدد') . "\n";
$isLocal = str_starts_with(env('APP_URL', ''), 'http://127.0.0.1') || 
           str_starts_with(env('APP_URL', ''), 'http://localhost');
echo "هل Local؟ " . ($isLocal ? '✅ نعم' : '❌ لا') . "\n\n";

// 2. فحص إعدادات المزامنة التلقائية
echo "2️⃣ إعدادات المزامنة التلقائية:\n";
echo "────────────────────────────────────\n";
$autoSyncEnabled = filter_var(env('AUTO_SYNC_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
$autoSyncInterval = (int) env('AUTO_SYNC_INTERVAL', 300);
echo "AUTO_SYNC_ENABLED: " . (env('AUTO_SYNC_ENABLED') ?? 'غير محدد (افتراضي: true)') . "\n";
echo "القيمة المفسرة: " . ($autoSyncEnabled ? '✅ مفعّل' : '❌ معطّل') . "\n";
echo "AUTO_SYNC_INTERVAL: " . $autoSyncInterval . " ثانية (" . ($autoSyncInterval / 60) . " دقيقة)\n\n";

// 3. فحص قاعدة البيانات SQLite
echo "3️⃣ قاعدة البيانات SQLite:\n";
echo "────────────────────────────────────\n";
try {
    $sqlitePath = config('database.connections.sync_sqlite.database');
    echo "المسار: " . $sqlitePath . "\n";
    echo "موجود؟ " . (file_exists($sqlitePath) ? '✅ نعم' : '❌ لا') . "\n";
    
    if (file_exists($sqlitePath)) {
        $db = DB::connection('sync_sqlite');
        $db->getPdo();
        echo "الاتصال: ✅ ناجح\n";
        
        // فحص جدول sync_metadata
        $hasTable = DB::connection('sync_sqlite')
            ->select("SELECT name FROM sqlite_master WHERE type='table' AND name='sync_metadata'");
        echo "جدول sync_metadata: " . (!empty($hasTable) ? '✅ موجود' : '❌ غير موجود') . "\n";
        
        if (!empty($hasTable)) {
            // جلب آخر مزامنة
            $lastSync = DB::connection('sync_sqlite')
                ->table('sync_metadata')
                ->where('table_name', 'auto_sync')
                ->orderBy('synced_at', 'desc')
                ->first();
            
            if ($lastSync) {
                echo "\n📊 آخر مزامنة:\n";
                echo "   الوقت: " . $lastSync->synced_at . "\n";
                echo "   السجلات: " . $lastSync->records_synced . "\n";
                echo "   الحالة: " . $lastSync->status . "\n";
                
                // حساب الوقت المتبقي
                $lastSyncTime = \Carbon\Carbon::parse($lastSync->synced_at);
                $nextSyncTime = $lastSyncTime->addMinutes(5);
                $now = \Carbon\Carbon::now();
                
                if ($nextSyncTime->isFuture()) {
                    $remaining = $now->diffInSeconds($nextSyncTime);
                    $mins = floor($remaining / 60);
                    $secs = $remaining % 60;
                    echo "   المزامنة القادمة: بعد " . sprintf('%02d:%02d', $mins, $secs) . "\n";
                } else {
                    echo "   المزامنة القادمة: ⏱️ الآن (متأخرة)\n";
                }
            } else {
                echo "\n⚠️ لم تتم أي مزامنة بعد\n";
                echo "   المزامنة القادمة: بعد 05:00 (افتراضي)\n";
            }
        }
        
        // فحص sync_queue
        $pendingCount = DB::connection('sync_sqlite')
            ->table('sync_queue')
            ->where('status', 'pending')
            ->count();
        echo "\n📦 ملفات معلقة: " . $pendingCount . "\n";
        
    }
} catch (\Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. فحص Laravel Scheduler
echo "4️⃣ Laravel Scheduler:\n";
echo "────────────────────────────────────\n";
try {
    $cacheFiles = glob(storage_path('framework/schedule-*'));
    echo "ملفات Schedule: " . count($cacheFiles) . "\n";
    if (!empty($cacheFiles)) {
        echo "✅ Scheduler يعمل\n";
        foreach ($cacheFiles as $file) {
            $modified = filemtime($file);
            $diff = time() - $modified;
            echo "   - " . basename($file) . " (منذ " . $diff . " ثانية)\n";
        }
    } else {
        echo "⚠️ Scheduler لا يعمل\n";
        echo "   قم بتشغيل: php artisan schedule:work\n";
    }
} catch (\Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. فحص API
echo "5️⃣ اختبار API:\n";
echo "────────────────────────────────────\n";
try {
    $url = env('APP_URL', 'http://127.0.0.1:8000') . '/api/sync-monitor/auto-sync-status';
    echo "URL: " . $url . "\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'X-Requested-With: XMLHttpRequest'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: " . $httpCode . "\n";
    
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if ($data && isset($data['status'])) {
            $status = $data['status'];
            echo "\n📊 حالة API:\n";
            echo "   enabled: " . ($status['enabled'] ? '✅ نعم' : '❌ لا') . "\n";
            echo "   is_local: " . ($status['is_local'] ? '✅ نعم' : '❌ لا') . "\n";
            echo "   next_sync_in: " . ($status['next_sync_in'] ?? 'غير محدد') . " ثانية\n";
            echo "   is_running: " . ($status['is_running'] ? '✅ نعم' : '❌ لا') . "\n";
            if (isset($status['schedule_running'])) {
                echo "   schedule_running: " . ($status['schedule_running'] ? '✅ نعم' : '❌ لا') . "\n";
            }
        }
    } else {
        echo "❌ فشل: " . ($response ?: 'لا يوجد رد') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "✅ الخلاصة:\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if ($autoSyncEnabled && $isLocal) {
    echo "✅ المزامنة التلقائية يجب أن تعمل\n";
    echo "   - البيئة: Local ✓\n";
    echo "   - الإعدادات: مفعّلة ✓\n";
    echo "   - قاعدة البيانات: متاحة ✓\n";
    
    $scheduleRunning = !empty($cacheFiles ?? []);
    if (!$scheduleRunning) {
        echo "\n⚠️ لكن Laravel Scheduler لا يعمل!\n";
        echo "   قم بتشغيل في Terminal منفصل:\n";
        echo "   → php artisan schedule:work\n";
    } else {
        echo "   - Laravel Scheduler: يعمل ✓\n";
        echo "\n🎉 كل شيء يعمل بشكل صحيح!\n";
    }
} else {
    echo "❌ المزامنة التلقائية لن تعمل\n";
    if (!$isLocal) {
        echo "   - السبب: ليست بيئة Local\n";
    }
    if (!$autoSyncEnabled) {
        echo "   - السبب: غير مفعّلة في .env\n";
        echo "   - الحل: أضف AUTO_SYNC_ENABLED=true في .env\n";
    }
}

echo "\n";
