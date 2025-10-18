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
            // Add separate fields for payments in USD and IQD
            $table->decimal('total_payments_received_usd', 15, 2)->default(0)->after('total_payments_received');
            $table->decimal('total_payments_received_iqd', 15, 2)->default(0)->after('total_payments_received_usd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_accounts', function (Blueprint $table) {
            $table->dropColumn(['total_payments_received_usd', 'total_payments_received_iqd']);
        });
    }
};

