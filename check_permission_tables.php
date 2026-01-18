<?php
/**
 * فحص بنية جداول Spatie Permission في SQLite
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n═══════════════════════════════════════════════════════════\n";
echo "   فحص بنية جداول Spatie Permission في SQLite\n";
echo "═══════════════════════════════════════════════════════════\n\n";

try {
    $permissionTables = [
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions'
    ];
    
    foreach ($permissionTables as $table) {
        echo "📋 جدول: $table\n";
        echo str_repeat("─", 60) . "\n";
        
        // فحص MySQL
        echo "\n1️⃣ MySQL:\n";
        try {
            $mysqlColumns = DB::connection('mysql')->select("SHOW COLUMNS FROM `{$table}`");
            foreach ($mysqlColumns as $col) {
                echo sprintf("   %-25s %s\n", $col->Field, $col->Type);
            }
        } catch (\Exception $e) {
            echo "   ❌ خطأ: " . $e->getMessage() . "\n";
        }
        
        // فحص SQLite
        echo "\n2️⃣ SQLite:\n";
        try {
            $sqliteColumns = DB::connection('sync_sqlite')->select("PRAGMA table_info(`{$table}`)");
            if (empty($sqliteColumns)) {
                echo "   ⚠️ الجدول غير موجود أو فارغ\n";
            } else {
                foreach ($sqliteColumns as $col) {
                    echo sprintf("   %-25s %s\n", $col->name, $col->type);
                }
            }
        } catch (\Exception $e) {
            echo "   ❌ خطأ: " . $e->getMessage() . "\n";
        }
        
        echo "\n" . str_repeat("═", 60) . "\n\n";
    }
    
    // اختبار استعلام roles مع users_count
    echo "🧪 اختبار استعلام الأدوار:\n";
    echo str_repeat("─", 60) . "\n\n";
    
    // على MySQL
    echo "1️⃣ على MySQL:\n";
    try {
        $sql = "SELECT roles.*, 
            (SELECT COUNT(*) 
             FROM users 
             INNER JOIN model_has_roles 
               ON users.id = model_has_roles.model_id 
             WHERE roles.id = model_has_roles.role_id 
               AND model_has_roles.model_type = 'App\\\\Models\\\\User') as users_count
        FROM roles";
        
        $mysqlRoles = DB::connection('mysql')->select($sql);
        foreach ($mysqlRoles as $role) {
            echo sprintf("   %-20s → %d مستخدم\n", $role->name, $role->users_count);
        }
    } catch (\Exception $e) {
        echo "   ❌ خطأ: " . $e->getMessage() . "\n";
    }
    
    echo "\n2️⃣ على SQLite:\n";
    try {
        $sql = "SELECT roles.*, 
            (SELECT COUNT(*) 
             FROM users 
             INNER JOIN model_has_roles 
               ON users.id = model_has_roles.model_id 
             WHERE roles.id = model_has_roles.role_id 
               AND model_has_roles.model_type = 'App\\\\Models\\\\User') as users_count
        FROM roles";
        
        $sqliteRoles = DB::connection('sync_sqlite')->select($sql);
        foreach ($sqliteRoles as $role) {
            echo sprintf("   %-20s → %d مستخدم\n", $role->name, $role->users_count);
        }
    } catch (\Exception $e) {
        echo "   ❌ خطأ SQLite: " . $e->getMessage() . "\n\n";
        echo "   💡 هذا يعني أن جداول Spatie Permission ناقصة أو فارغة!\n";
    }
    
    echo "\n" . str_repeat("═", 60) . "\n\n";
    
    // فحص محتوى الجداول
    echo "📊 فحص محتوى جداول Permission:\n";
    echo str_repeat("─", 60) . "\n\n";
    
    foreach ($permissionTables as $table) {
        try {
            $mysqlCount = DB::connection('mysql')->table($table)->count();
            $sqliteCount = DB::connection('sync_sqlite')->table($table)->count();
            $diff = $mysqlCount - $sqliteCount;
            $icon = $diff == 0 ? '✓' : '⚠️';
            
            echo sprintf("%-25s MySQL: %3d  SQLite: %3d  %s\n", 
                $table,
                $mysqlCount,
                $sqliteCount,
                $icon
            );
        } catch (\Exception $e) {
            echo sprintf("%-25s ❌ خطأ\n", $table);
        }
    }
    
    echo "\n" . str_repeat("═", 60) . "\n\n";
    
} catch (\Exception $e) {
    echo "❌ خطأ عام: " . $e->getMessage() . "\n\n";
    exit(1);
}
