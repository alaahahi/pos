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
        Schema::table('monthly_accounts', function (Blueprint $table) {
            $table->decimal('total_commissions_usd', 15, 2)->default(0)->after('total_payments_received_iqd');
            $table->decimal('total_commissions_iqd', 15, 2)->default(0)->after('total_commissions_usd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_accounts', function (Blueprint $table) {
            $table->dropColumn(['total_commissions_usd', 'total_commissions_iqd']);
        });
    }
};

