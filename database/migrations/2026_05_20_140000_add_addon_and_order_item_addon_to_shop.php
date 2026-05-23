<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->string('addon_name')->nullable()->after('price');
            $table->decimal('addon_price', 12, 2)->nullable()->after('addon_name');
        });

        Schema::table('shop_order_items', function (Blueprint $table) {
            $table->boolean('with_addon')->default(false)->after('category_name');
            $table->string('addon_name')->nullable()->after('with_addon');
            $table->decimal('addon_price', 12, 2)->nullable()->after('addon_name');
        });
    }

    public function down(): void
    {
        Schema::table('shop_order_items', function (Blueprint $table) {
            $table->dropColumn(['with_addon', 'addon_name', 'addon_price']);
        });

        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropColumn(['addon_name', 'addon_price']);
        });
    }
};
