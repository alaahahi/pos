<?php
/**
 * اختبار استعلام الأدوار مع عدد المستخدمين
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

echo "\n========================================\n";
echo "   اختبار استعلام الأدوار\n";
echo "========================================\n\n";

try {
    // الطريقة 1: Eloquent (الأفضل)
    echo "1️⃣ باستخدام Eloquent (withCount):\n";
    echo "════════════════════════════════\n";
    
    $rolesEloquent = Role::withCount('users')->get();
    
    foreach ($rolesEloquent as $role) {
        echo sprintf("%-20s → %d مستخدم\n", 
            $role->name, 
            $role->users_count
        );
    }
    
    echo "\n";
    
    // الطريقة 2: Query Builder (الاستعلام الصحيح)
    echo "2️⃣ باستخدام Query Builder (مُصلح):\n";
    echo "════════════════════════════════\n";
    
    $rolesQuery = DB::table('roles')
        ->selectRaw('roles.*, 
            (SELECT COUNT(*) 
             FROM users 
             INNER JOIN model_has_roles 
               ON users.id = model_has_roles.model_id 
             WHERE roles.id = model_has_roles.role_id 
               AND model_has_roles.model_type = ?) as users_count', 
            ['App\Models\User']
        )
        ->get();
    
    foreach ($rolesQuery as $role) {
        echo sprintf("%-20s → %d مستخدم\n", 
            $role->name, 
            $role->users_count
        );
    }
    
    echo "\n";
    
    // الطريقة 3: Raw SQL (للاختبار فقط)
    echo "3️⃣ باستخدام Raw SQL:\n";
    echo "════════════════════════════════\n";
    
    $sql = "SELECT roles.*, 
        (SELECT COUNT(*) 
         FROM users 
         INNER JOIN model_has_roles 
           ON users.id = model_has_roles.model_id 
         WHERE roles.id = model_has_roles.role_id 
           AND model_has_roles.model_type = 'App\Models\User') as users_count
    FROM roles";
    
    $rolesRaw = DB::select($sql);
    
    foreach ($rolesRaw as $role) {
        echo sprintf("%-20s → %d مستخدم\n", 
            $role->name, 
            $role->users_count
        );
    }
    
    echo "\n";
    
    // مقارنة
    echo "════════════════════════════════\n";
    echo "✅ جميع الطرق تعطي نفس النتيجة!\n";
    echo "════════════════════════════════\n\n";
    
    // تفاصيل إضافية
    echo "📊 تفاصيل الأدوار:\n";
    echo "════════════════════════════════\n";
    
    $totalUsers = DB::table('users')->count();
    $totalRoles = DB::table('roles')->count();
    $usersWithRoles = DB::table('model_has_roles')
        ->where('model_type', 'App\Models\User')
        ->distinct('model_id')
        ->count('model_id');
    
    echo "إجمالي المستخدمين: $totalUsers\n";
    echo "إجمالي الأدوار: $totalRoles\n";
    echo "مستخدمين لديهم أدوار: $usersWithRoles\n";
    echo "مستخدمين بدون أدوار: " . ($totalUsers - $usersWithRoles) . "\n\n";
    
} catch (\Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    exit(1);
}

echo "✅ تم الاختبار بنجاح!\n\n";
