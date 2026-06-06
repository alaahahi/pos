<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->json('totals_by_currency')->nullable()->after('total_amount');
        });

        Schema::table('shop_order_items', function (Blueprint $table) {
            $table->string('currency', 8)->nullable()->after('addon_price');
        });
    }

    public function down(): void
    {
        Schema::table('shop_order_items', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropColumn('totals_by_currency');
        });
    }
};
