<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

echo "🔧 إصلاح صلاحيات الديكور...\n\n";

try {
    $permission = Permission::where('name', 'create decoration')->first();
    
    if (!$permission) {
        echo "✗ الصلاحية غير موجودة\n";
        exit(1);
    }
    
    // إضافة الصلاحية لـ superadmin و admin
    $superadmin = Role::where('name', 'superadmin')->first();
    $admin = Role::where('name', 'admin')->first();
    
    if ($superadmin) {
        $superadmin->givePermissionTo($permission);
        echo "✓ تم إضافة الصلاحية لـ superadmin\n";
    }
    
    if ($admin) {
        $admin->givePermissionTo($permission);
        echo "✓ تم إضافة الصلاحية لـ admin\n";
    }
    
    echo "\n✅ تم إصلاح الصلاحيات بنجاح!\n";
    
} catch (\Exception $e) {
    echo "✗ خطأ: " . $e->getMessage() . "\n";
}
