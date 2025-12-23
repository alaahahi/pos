<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SQLite Status ===\n\n";

try {
    // عدد الجداول
    $tables = DB::connection('sync_sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
    echo "📊 عدد الجداول: " . count($tables) . "\n";
    
    // قائمة الجداول
    if (count($tables) > 0) {
        echo "\n📋 الجداول:\n";
        foreach ($tables as $table) {
            $count = DB::connection('sync_sqlite')->table($table->name)->count();
            echo "  - {$table->name}: {$count} سجل\n";
        }
    }
    
    // التحقق من جدول users
    $usersExists = DB::connection('sync_sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    if (count($usersExists) > 0) {
        $usersCount = DB::connection('sync_sqlite')->table('users')->count();
        echo "\n✅ جدول users موجود: {$usersCount} مستخدم\n";
        
        // أول مستخدم
        $firstUser = DB::connection('sync_sqlite')->table('users')->first();
        if ($firstUser) {
            echo "   - أول مستخدم: {$firstUser->name} ({$firstUser->email})\n";
        }
    } else {
        echo "\n❌ جدول users غير موجود!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
}

echo "\n=== Done ===\n";


