<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * إصلاح جدول logs على SQLite إذا كان ببنية خاطئة (table_name, record_id, details)
     * والتحويل للبنية المتوافقة مع التطبيق (module_name, action, badge, affected_record_id, original_data, updated_data)
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }
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
        if (Schema::hasColumn('logs', 'module_name') && Schema::hasColumn('logs', 'affected_record_id')) {
            return;
        }
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
    }

    public function down(): void
    {
        // لا تراجع لتجنب فقدان البنية الصحيحة
    }
};
