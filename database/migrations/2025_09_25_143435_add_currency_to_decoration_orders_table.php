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
        Schema::table('decoration_orders', function (Blueprint $table) {
            $table->string('currency')->default('dinar')->after('total_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('decoration_orders', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
