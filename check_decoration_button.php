<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

echo "╔════════════════════════════════════════════════════════╗\n";
echo "║       🔍 فحص صلاحيات الديكور للمستخدمين             ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Check Permission exists
echo "✅ فحص الصلاحية: create decoration\n";
echo "─────────────────────────────────────────\n";

try {
    $permission = Permission::where('name', 'create decoration')->first();
    
    if ($permission) {
        echo "✓ الصلاحية موجودة (ID: {$permission->id})\n\n";
        
        // Check which roles have this permission
        echo "الأدوار التي تملك هذه الصلاحية:\n";
        $roles = Role::whereHas('permissions', function($q) use ($permission) {
            $q->where('permissions.id', $permission->id);
        })->get();
        
        foreach ($roles as $role) {
            echo "  ✓ {$role->name}\n";
        }
        
    } else {
        echo "✗ الصلاحية غير موجودة!\n";
        echo "  → يجب تشغيل: php artisan db:seed --class=UserRolePermissionSeeder\n";
    }
    
} catch (\Exception $e) {
    echo "✗ خطأ: " . $e->getMessage() . "\n";
}

echo "\n";

// Check Users with permission
echo "✅ المستخدمون الذين لديهم صلاحية create decoration:\n";
echo "─────────────────────────────────────────\n";

try {
    $users = DB::table('users')
        ->join('model_has_roles', function($join) {
            $join->on('users.id', '=', 'model_has_roles.model_id')
                 ->where('model_has_roles.model_type', '=', 'App\Models\User');
        })
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->whereIn('roles.name', ['superadmin', 'admin', 'staff'])
        ->select('users.id', 'users.name', 'users.email', 'roles.name as role_name')
        ->get();
    
    if ($users->count() > 0) {
        foreach ($users as $user) {
            echo "  ✓ {$user->name} ({$user->email}) - {$user->role_name}\n";
        }
    } else {
        echo "  ⚠ لا يوجد مستخدمون بهذه الصلاحية\n";
    }
    
} catch (\Exception $e) {
    echo "✗ خطأ: " . $e->getMessage() . "\n";
}

echo "\n";

// Check Route
echo "✅ فحص الـ Route:\n";
echo "─────────────────────────────────────────\n";

try {
    $route = route('decoration.orders.create', [], false);
    echo "✓ Route موجود: {$route}\n";
} catch (\Exception $e) {
    echo "✗ Route غير موجود\n";
}

echo "\n";

// Check Build
echo "✅ فحص الـ Build:\n";
echo "─────────────────────────────────────────\n";

$manifestPath = public_path('build/manifest.json');
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $simpleOrdersKey = null;
    
    foreach (array_keys($manifest) as $key) {
        if (strpos($key, 'SimpleOrders') !== false) {
            $simpleOrdersKey = $key;
            break;
        }
    }
    
    if ($simpleOrdersKey) {
        echo "✓ SimpleOrders.vue موجود في الـ build\n";
        echo "  └─ Key: {$simpleOrdersKey}\n";
        echo "  └─ File: {$manifest[$simpleOrdersKey]['file']}\n";
    } else {
        echo "⚠ SimpleOrders.vue غير موجود في الـ build\n";
        echo "  → يجب تشغيل: npm run build\n";
    }
} else {
    echo "✗ manifest.json غير موجود\n";
    echo "  → يجب تشغيل: npm run build\n";
}

echo "\n";
echo "🔧 الحل المقترح:\n";
echo "   1. تأكد من تشغيل: npm run build\n";
echo "   2. تأكد من تسجيل الدخول بمستخدم لديه صلاحية 'create decoration'\n";
echo "   3. امسح الكاش: php artisan cache:clear\n\n";
