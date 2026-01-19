<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إضافة عمود avatar_url إلى جدول users
     * هذا العمود يحتوي على الـ URL الكامل للأفاتار (من accessor)
     */
    public function up(): void
    {
        // التحقق من نوع قاعدة البيانات
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            // MySQL: إضافة العمود إذا لم يكن موجوداً
            Schema::connection('mysql')->table('users', function (Blueprint $table) {
                if (!Schema::connection('mysql')->hasColumn('users', 'avatar_url')) {
                    $table->string('avatar_url', 500)->nullable()->after('avatar')
                        ->comment('Full URL to user avatar (generated from accessor)');
                }
            });
            
            // تحديث القيم الحالية من accessor
            $this->updateExistingAvatarUrls('mysql');
        } 
        
        if ($driver === 'sqlite' || in_array(DB::getDefaultConnection(), ['sync_sqlite', 'sqlite'])) {
            // SQLite: إضافة العمود إذا لم يكن موجوداً
            Schema::connection('sync_sqlite')->table('users', function (Blueprint $table) {
                if (!Schema::connection('sync_sqlite')->hasColumn('users', 'avatar_url')) {
                    $table->string('avatar_url', 500)->nullable();
                }
            });
            
            // تحديث القيم الحالية من accessor
            $this->updateExistingAvatarUrls('sync_sqlite');
        }
    }

    /**
     * تحديث avatar_url للسجلات الحالية
     */
    protected function updateExistingAvatarUrls(string $connection): void
    {
        try {
            $users = DB::connection($connection)->table('users')->get();
            
            foreach ($users as $user) {
                // بناء avatar_url من avatar
                $avatarUrl = $this->buildAvatarUrl($user->avatar);
                
                DB::connection($connection)->table('users')
                    ->where('id', $user->id)
                    ->update(['avatar_url' => $avatarUrl]);
            }
        } catch (\Exception $e) {
            // تجاهل الأخطاء - العمود قد لا يكون موجوداً بعد
        }
    }

    /**
     * بناء avatar_url من avatar path
     */
    protected function buildAvatarUrl(?string $avatar): string
    {
        if (empty($avatar)) {
            $avatar = 'avatars/default_avatar.png';
        }
        
        $appUrl = env('APP_URL', 'http://127.0.0.1:8000');
        return $appUrl . '/storage/' . $avatar;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف العمود من MySQL
        if (Schema::hasColumn('users', 'avatar_url')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('avatar_url');
            });
        }
        
        // حذف العمود من SQLite
        try {
            if (Schema::connection('sync_sqlite')->hasColumn('users', 'avatar_url')) {
                Schema::connection('sync_sqlite')->table('users', function (Blueprint $table) {
                    $table->dropColumn('avatar_url');
                });
            }
        } catch (\Exception $e) {
            // تجاهل الخطأ إذا لم يكن sync_sqlite موجود
        }
    }
};
