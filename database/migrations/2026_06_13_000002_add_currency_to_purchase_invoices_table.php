<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->string('currency', 3)->default('IQD')->after('total_amount');
        });

        DB::table('cashbox_transactions')
            ->where('reference_type', 'App\Models\PurchaseInvoice')
            ->select('reference_id', 'currency')
            ->orderBy('id')
            ->get()
            ->each(function ($transaction) {
                DB::table('purchase_invoices')
                    ->where('id', $transaction->reference_id)
                    ->update(['currency' => $transaction->currency]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
