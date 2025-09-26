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
        Schema::table('decorations', function (Blueprint $table) {
            $table->enum('currency', ['dinar', 'dollar'])->default('dinar')->after('base_price');
            $table->decimal('base_price_dollar', 10, 2)->nullable()->after('currency');
            $table->decimal('base_price_dinar', 10, 2)->nullable()->after('base_price_dollar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('decorations', function (Blueprint $table) {
            $table->dropColumn(['currency', 'base_price_dollar', 'base_price_dinar']);
        });
    }
};
