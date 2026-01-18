<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // فقط لـ SQLite: إعادة إنشاء الجدول بدون foreign key وجعل by_user_id nullable
        if (DB::connection()->getDriverName() === 'sqlite') {
            // نسخ البيانات الموجودة
            $logs = DB::table('logs')->get();
            
            // حذف الجدول القديم
            Schema::dropIfExists('logs');
            
            // إنشاء الجدول الجديد
            Schema::create('logs', function (Blueprint $table) {
                $table->id();
                $table->string('action')->nullable();
                $table->string('table_name')->nullable();
                $table->unsignedBigInteger('record_id')->nullable();
                $table->unsignedBigInteger('by_user_id')->nullable(); // جعله nullable
                $table->text('details')->nullable();
                $table->timestamps();
                
                // إضافة index بدلاً من foreign key
                $table->index('by_user_id');
            });
            
            // إعادة البيانات
            foreach ($logs as $log) {
                DB::table('logs')->insert((array) $log);
            }
        } else {
            // لـ MySQL: استخدام الطريقة العادية
            Schema::table('logs', function (Blueprint $table) {
                // حذف foreign key إذا كان موجود
                try {
                    $table->dropForeign(['by_user_id']);
                } catch (\Exception $e) {
                    // Foreign key غير موجود
                }
                
                // جعل الحقل nullable
                $table->unsignedBigInteger('by_user_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite: إعادة الجدول للوضع السابق
            $logs = DB::table('logs')->get();
            
            Schema::dropIfExists('logs');
            
            Schema::create('logs', function (Blueprint $table) {
                $table->id();
                $table->string('action')->nullable();
                $table->string('table_name')->nullable();
                $table->unsignedBigInteger('record_id')->nullable();
                $table->unsignedBigInteger('by_user_id'); // بدون nullable
                $table->text('details')->nullable();
                $table->timestamps();
                
                $table->index('by_user_id');
            });
            
            foreach ($logs as $log) {
                DB::table('logs')->insert((array) $log);
            }
        } else {
            // MySQL
            Schema::table('logs', function (Blueprint $table) {
                $table->unsignedBigInteger('by_user_id')->nullable(false)->change();
            });
        }
    }
};
