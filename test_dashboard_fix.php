<?php
/**
 * اختبار Dashboard بعد الإصلاح
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

echo "\n========================================\n";
echo "   اختبار Dashboard بعد الإصلاح\n";
echo "========================================\n\n";

try {
    echo "🧪 اختبار استعلام الأدوار (الطريقة الجديدة)...\n";
    echo "════════════════════════════════════════\n\n";
    
    // الطريقة الجديدة المُصلحة
    $roles = Role::select('roles.*')
        ->selectSub(function ($query) {
            $query->from('model_has_roles')
                ->whereColumn('model_has_roles.role_id', 'roles.id')
                ->where('model_has_roles.model_type', \App\Models\User::class)
                ->selectRaw('COUNT(*)');
        }, 'users_count')
        ->get();
    
    echo "✅ الاستعلام نجح!\n\n";
    
    echo "📊 النتائج:\n";
    echo "════════════\n";
    
    foreach ($roles as $role) {
        echo sprintf("%-20s → %d مستخدم\n", 
            $role->name, 
            $role->users_count ?? 0
        );
    }
    
    echo "\n";
    
    // اختبار أن البيانات صحيحة
    echo "🔍 التحقق من الدقة:\n";
    echo "════════════════════\n";
    
    foreach ($roles as $role) {
        $actualCount = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->where('model_type', \App\Models\User::class)
            ->count();
        
        $match = ($actualCount == $role->users_count) ? '✓' : '✗';
        echo sprintf("%-20s %s (استعلام: %d, فعلي: %d)\n", 
            $role->name,
            $match,
            $role->users_count ?? 0,
            $actualCount
        );
    }
    
    echo "\n";
    echo "════════════════════════════════════════\n";
    echo "✅ Dashboard جاهز للعمل!\n";
    echo "════════════════════════════════════════\n\n";
    
    echo "💡 الآن يمكنك فتح:\n";
    echo "   http://127.0.0.1:8000/dashboard\n\n";
    
} catch (\Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
