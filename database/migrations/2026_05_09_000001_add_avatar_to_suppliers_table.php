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
        if (!Schema::hasTable('suppliers')) {
            return;
        }

        if (!Schema::hasColumn('suppliers', 'avatar')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->string('avatar')->nullable()->after('notes');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('suppliers') || !Schema::hasColumn('suppliers', 'avatar')) {
            return;
        }

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });
    }
};
