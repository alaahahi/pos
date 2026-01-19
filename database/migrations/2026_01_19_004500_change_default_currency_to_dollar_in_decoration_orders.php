<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تغيير القيمة الافتراضية للعملة إلى dollar
        Schema::table('decoration_orders', function (Blueprint $table) {
            $table->string('currency')->default('dollar')->change();
        });
        
        // تحديث السجلات الموجودة التي لا تحتوي على عملة محددة
        DB::table('decoration_orders')
            ->where('currency', 'dinar')
            ->orWhereNull('currency')
            ->update(['currency' => 'dollar']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('decoration_orders', function (Blueprint $table) {
            $table->string('currency')->default('dinar')->change();
        });
    }
};
