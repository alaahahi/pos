<?php
/**
 * إعادة إنشاء جداول Spatie Permission في SQLite بشكل صحيح
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n═══════════════════════════════════════════════════════════\n";
echo "   إصلاح جداول Spatie Permission في SQLite\n";
echo "═══════════════════════════════════════════════════════════\n\n";

try {
    $connection = 'sync_sqlite';
    
    // 1. حذف الجداول القديمة (الخاطئة)
    echo "1️⃣ حذف الجداول القديمة...\n";
    $tablesToDrop = ['model_has_roles', 'model_has_permissions', 'role_has_permissions'];
    
    foreach ($tablesToDrop as $table) {
        if (Schema::connection($connection)->hasTable($table)) {
            Schema::connection($connection)->drop($table);
            echo "   ✓ حذف: $table\n";
        }
    }
    echo "\n";
    
    // 2. إنشاء الجداول بالبنية الصحيحة
    echo "2️⃣ إنشاء الجداول بالبنية الصحيحة...\n";
    
    // model_has_roles
    echo "   إنشاء: model_has_roles... ";
    Schema::connection($connection)->create('model_has_roles', function ($table) {
        $table->unsignedBigInteger('role_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
        
        $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
        $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
    });
    echo "✓\n";
    
    // model_has_permissions
    echo "   إنشاء: model_has_permissions... ";
    Schema::connection($connection)->create('model_has_permissions', function ($table) {
        $table->unsignedBigInteger('permission_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
        
        $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
        $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
    });
    echo "✓\n";
    
    // role_has_permissions
    echo "   إنشاء: role_has_permissions... ";
    Schema::connection($connection)->create('role_has_permissions', function ($table) {
        $table->unsignedBigInteger('permission_id');
        $table->unsignedBigInteger('role_id');
        
        $table->primary(['permission_id', 'role_id']);
    });
    echo "✓\n\n";
    
    // 3. نسخ البيانات من MySQL إلى SQLite
    echo "3️⃣ نسخ البيانات من MySQL...\n";
    
    // model_has_roles
    echo "   نسخ: model_has_roles... ";
    $modelHasRoles = DB::connection('mysql')->table('model_has_roles')->get();
    if ($modelHasRoles->count() > 0) {
        foreach ($modelHasRoles as $row) {
            DB::connection($connection)->table('model_has_roles')->insert((array)$row);
        }
        echo "✓ ({$modelHasRoles->count()} سجل)\n";
    } else {
        echo "⚠️ (لا توجد بيانات)\n";
    }
    
    // model_has_permissions
    echo "   نسخ: model_has_permissions... ";
    $modelHasPermissions = DB::connection('mysql')->table('model_has_permissions')->get();
    if ($modelHasPermissions->count() > 0) {
        foreach ($modelHasPermissions as $row) {
            DB::connection($connection)->table('model_has_permissions')->insert((array)$row);
        }
        echo "✓ ({$modelHasPermissions->count()} سجل)\n";
    } else {
        echo "⚠️ (لا توجد بيانات)\n";
    }
    
    // role_has_permissions
    echo "   نسخ: role_has_permissions... ";
    $roleHasPermissions = DB::connection('mysql')->table('role_has_permissions')->get();
    if ($roleHasPermissions->count() > 0) {
        foreach ($roleHasPermissions as $row) {
            DB::connection($connection)->table('role_has_permissions')->insert((array)$row);
        }
        echo "✓ ({$roleHasPermissions->count()} سجل)\n";
    } else {
        echo "⚠️ (لا توجد بيانات)\n";
    }
    
    echo "\n";
    
    // 4. التحقق من النتيجة
    echo "4️⃣ التحقق من النتيجة...\n";
    echo str_repeat("─", 60) . "\n";
    
    $tables = ['model_has_roles', 'model_has_permissions', 'role_has_permissions'];
    foreach ($tables as $table) {
        $mysqlCount = DB::connection('mysql')->table($table)->count();
        $sqliteCount = DB::connection($connection)->table($table)->count();
        $icon = $mysqlCount == $sqliteCount ? '✓' : '⚠️';
        
        echo sprintf("%-30s MySQL: %3d  SQLite: %3d  %s\n", 
            $table,
            $mysqlCount,
            $sqliteCount,
            $icon
        );
    }
    
    echo "\n";
    
    // 5. اختبار استعلام الأدوار
    echo "5️⃣ اختبار استعلام الأدوار...\n";
    echo str_repeat("─", 60) . "\n\n";
    
    try {
        $sql = "SELECT roles.*, 
            (SELECT COUNT(*) 
             FROM users 
             INNER JOIN model_has_roles 
               ON users.id = model_has_roles.model_id 
             WHERE roles.id = model_has_roles.role_id 
               AND model_has_roles.model_type = 'App\\\\Models\\\\User') as users_count
        FROM roles";
        
        $roles = DB::connection($connection)->select($sql);
        foreach ($roles as $role) {
            echo sprintf("   %-20s → %d مستخدم\n", $role->name, $role->users_count);
        }
        echo "\n✅ الاستعلام يعمل الآن!\n";
    } catch (\Exception $e) {
        echo "   ❌ الاستعلام ما زال يفشل: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("═", 60) . "\n";
    echo "✅ تم الإصلاح بنجاح!\n";
    echo str_repeat("═", 60) . "\n\n";
    
    echo "💡 الآن يمكنك:\n";
    echo "   1. فتح Dashboard: http://127.0.0.1:8000/dashboard\n";
    echo "   2. يجب أن يعمل بدون أخطاء!\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ خطأ: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
