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
        Schema::table('daily_closes', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_closes', 'is_auto_closed')) {
                $table->boolean('is_auto_closed')->default(false)->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_closes', function (Blueprint $table) {
            if (Schema::hasColumn('daily_closes', 'is_auto_closed')) {
                $table->dropColumn('is_auto_closed');
            }
        });
    }
};
