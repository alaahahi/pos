<?php
/**
 * إنشاء جدول jobs في SQLite للمزامنة في الخلفية
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "   إنشاء جدول jobs في SQLite\n";
echo "========================================\n\n";

try {
    $connection = 'sync_sqlite';
    
    // التحقق من وجود الجدول
    if (Schema::connection($connection)->hasTable('jobs')) {
        echo "✅ جدول jobs موجود بالفعل في SQLite\n\n";
        exit(0);
    }
    
    echo "جاري إنشاء جدول jobs...\n";
    
    // إنشاء جدول jobs
    DB::connection($connection)->statement("
        CREATE TABLE IF NOT EXISTS jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            queue TEXT NOT NULL,
            payload TEXT NOT NULL,
            attempts INTEGER NOT NULL DEFAULT 0,
            reserved_at INTEGER NULL,
            available_at INTEGER NOT NULL,
            created_at INTEGER NOT NULL
        )
    ");
    
    // إنشاء index على queue
    DB::connection($connection)->statement("
        CREATE INDEX IF NOT EXISTS idx_jobs_queue ON jobs(queue)
    ");
    
    echo "✅ تم إنشاء جدول jobs بنجاح\n\n";
    
    // التحقق من الإنشاء
    if (Schema::connection($connection)->hasTable('jobs')) {
        $count = DB::connection($connection)->table('jobs')->count();
        echo "✅ التحقق: جدول jobs موجود وبه {$count} سجل(ات)\n\n";
    } else {
        echo "❌ فشل التحقق: جدول jobs غير موجود\n\n";
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "========================================\n\n";

