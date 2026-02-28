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
        if (!Schema::hasTable('daily_closes')) {
            return;
        }
        Schema::table('daily_closes', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_closes', 'direct_deposits_usd')) {
                $table->decimal('direct_deposits_usd', 15, 2)->default(0)->after('total_sales_iqd');
            }
            if (!Schema::hasColumn('daily_closes', 'direct_deposits_iqd')) {
                $table->decimal('direct_deposits_iqd', 15, 2)->default(0)->after('direct_deposits_usd');
            }
            if (!Schema::hasColumn('daily_closes', 'direct_withdrawals_usd')) {
                $table->decimal('direct_withdrawals_usd', 15, 2)->default(0)->after('direct_deposits_iqd');
            }
            if (!Schema::hasColumn('daily_closes', 'direct_withdrawals_iqd')) {
                $table->decimal('direct_withdrawals_iqd', 15, 2)->default(0)->after('direct_withdrawals_usd');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_closes', function (Blueprint $table) {
            $table->dropColumn([
                'direct_deposits_usd',
                'direct_deposits_iqd',
                'direct_withdrawals_usd',
                'direct_withdrawals_iqd'
            ]);
        });
    }
};


