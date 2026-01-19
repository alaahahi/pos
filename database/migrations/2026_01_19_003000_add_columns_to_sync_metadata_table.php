<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sync_metadata', function (Blueprint $table) {
            // إضافة الأعمدة المفقودة
            if (!Schema::hasColumn('sync_metadata', 'synced_at')) {
                $table->timestamp('synced_at')->nullable()->after('last_synced_at');
            }
            
            if (!Schema::hasColumn('sync_metadata', 'records_synced')) {
                $table->unsignedInteger('records_synced')->default(0)->after('total_synced');
            }
            
            if (!Schema::hasColumn('sync_metadata', 'status')) {
                $table->string('status', 50)->nullable()->after('records_synced');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sync_metadata', function (Blueprint $table) {
            if (Schema::hasColumn('sync_metadata', 'synced_at')) {
                $table->dropColumn('synced_at');
            }
            
            if (Schema::hasColumn('sync_metadata', 'records_synced')) {
                $table->dropColumn('records_synced');
            }
            
            if (Schema::hasColumn('sync_metadata', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
