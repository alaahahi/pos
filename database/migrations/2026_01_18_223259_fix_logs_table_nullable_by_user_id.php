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
        // فقط لـ SQLite: إعادة إنشاء الجدول بنفس أعمدة create_logs_table لكن بدون foreign key وجعل by_user_id nullable
        if (DB::connection()->getDriverName() === 'sqlite') {
            if (!Schema::hasTable('logs')) {
                Schema::create('logs', function (Blueprint $table) {
                    $table->id();
                    $table->string('module_name');
                    $table->string('action');
                    $table->string('badge');
                    $table->unsignedBigInteger('affected_record_id');
                    $table->json('original_data')->nullable();
                    $table->json('updated_data')->nullable();
                    $table->unsignedBigInteger('by_user_id')->nullable();
                    $table->timestamps();
                    $table->index('by_user_id');
                });
                return;
            }
            // إذا كان الجدول يحتوي module_name (البنية المتوافقة مع التطبيق) و by_user_id nullable نتخطى
            if (Schema::hasColumn('logs', 'module_name') && Schema::hasColumn('logs', 'affected_record_id')) {
                return;
            }
            // جدول ببنية خاطئة (table_name, record_id): إعادة إنشاء بالبنية الصحيحة
            $logs = DB::table('logs')->get();
            Schema::dropIfExists('logs');
            Schema::create('logs', function (Blueprint $table) {
                $table->id();
                $table->string('module_name');
                $table->string('action');
                $table->string('badge');
                $table->unsignedBigInteger('affected_record_id');
                $table->json('original_data')->nullable();
                $table->json('updated_data')->nullable();
                $table->unsignedBigInteger('by_user_id')->nullable();
                $table->timestamps();
                $table->index('by_user_id');
            });
            foreach ($logs as $log) {
                $row = (array) $log;
                DB::table('logs')->insert([
                    'id' => $row['id'] ?? null,
                    'module_name' => $row['module_name'] ?? $row['table_name'] ?? 'Unknown',
                    'action' => $row['action'] ?? null,
                    'badge' => $row['badge'] ?? 'info',
                    'affected_record_id' => $row['affected_record_id'] ?? $row['record_id'] ?? 0,
                    'original_data' => $row['original_data'] ?? null,
                    'updated_data' => $row['updated_data'] ?? null,
                    'by_user_id' => $row['by_user_id'] ?? null,
                    'created_at' => $row['created_at'] ?? null,
                    'updated_at' => $row['updated_at'] ?? null,
                ]);
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
            $logs = DB::table('logs')->get();
            Schema::dropIfExists('logs');
            Schema::create('logs', function (Blueprint $table) {
                $table->id();
                $table->string('module_name');
                $table->string('action');
                $table->string('badge');
                $table->unsignedBigInteger('affected_record_id');
                $table->json('original_data')->nullable();
                $table->json('updated_data')->nullable();
                $table->unsignedBigInteger('by_user_id');
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
