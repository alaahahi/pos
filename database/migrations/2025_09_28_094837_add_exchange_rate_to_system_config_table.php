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
        Schema::table('system_config', function (Blueprint $table) {
            $table->decimal('exchange_rate', 10, 2)->default(1500)->after('third_title_kr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_config', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });
    }
};
