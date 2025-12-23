<?php

/**
 * التحقق من إعدادات الاتصال بـ MySQL
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

echo "========================================\n";
echo "   التحقق من إعدادات MySQL\n";
echo "========================================\n\n";

// 1. عرض إعدادات الاتصال
echo "1. إعدادات الاتصال:\n";
$mysqlConfig = Config::get('database.connections.mysql');
echo "   Host: " . ($mysqlConfig['host'] ?? 'N/A') . "\n";
echo "   Port: " . ($mysqlConfig['port'] ?? 'N/A') . "\n";
echo "   Database: " . ($mysqlConfig['database'] ?? 'N/A') . "\n";
echo "   Username: " . ($mysqlConfig['username'] ?? 'N/A') . "\n";
echo "   Password: " . (isset($mysqlConfig['password']) ? '***' : 'N/A') . "\n\n";

// 2. محاولة الاتصال
echo "2. محاولة الاتصال...\n";
try {
    $pdo = DB::connection('mysql')->getPdo();
    echo "   ✅ نجح الاتصال!\n\n";
    
    // 3. التحقق من قاعدة البيانات
    echo "3. التحقق من قاعدة البيانات...\n";
    $dbName = DB::connection('mysql')->getDatabaseName();
    echo "   قاعدة البيانات الحالية: {$dbName}\n\n";
    
    // 4. التحقق من الجداول
    echo "4. التحقق من الجداول...\n";
    $tables = DB::connection('mysql')->select('SHOW TABLES');
    echo "   عدد الجداول: " . count($tables) . "\n";
    
    // التحقق من جدول orders
    $ordersExists = DB::connection('mysql')->select("SHOW TABLES LIKE 'orders'");
    if (!empty($ordersExists)) {
        $ordersCount = DB::connection('mysql')->table('orders')->count();
        echo "   ✅ جدول orders موجود - عدد السجلات: {$ordersCount}\n";
    } else {
        echo "   ❌ جدول orders غير موجود\n";
    }
    
    // التحقق من جدول order_product
    $orderProductExists = DB::connection('mysql')->select("SHOW TABLES LIKE 'order_product'");
    if (!empty($orderProductExists)) {
        $orderProductCount = DB::connection('mysql')->table('order_product')->count();
        echo "   ✅ جدول order_product موجود - عدد السجلات: {$orderProductCount}\n";
    } else {
        echo "   ❌ جدول order_product غير موجود\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ فشل الاتصال: " . $e->getMessage() . "\n\n";
    
    echo "5. أسباب محتملة للفشل:\n";
    echo "   - السيرفر غير متاح (Offline)\n";
    echo "   - إعدادات الاتصال خاطئة\n";
    echo "   - Firewall يمنع الاتصال\n";
    echo "   - اسم المستخدم أو كلمة المرور خاطئة\n\n";
    
    echo "6. الحلول المقترحة:\n";
    echo "   - تحقق من أن السيرفر متاح (Online)\n";
    echo "   - تحقق من إعدادات .env (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)\n";
    echo "   - تحقق من أن Firewall يسمح بالاتصال\n";
    echo "   - جرب الاتصال يدوياً باستخدام MySQL Client\n";
}

echo "\n========================================\n";

