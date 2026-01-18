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
        // التحقق من نوع قاعدة البيانات
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: التحقق من وجود index قبل إنشائه
            Schema::table('purchase_invoices', function (Blueprint $table) {
                // لا نفعل شيء - الـ index موجود مسبقاً
                // في SQLite، foreign keys اختيارية ولا نحتاجها في offline mode
            });
        } else {
            // MySQL: استخدام الطريقة العادية
            Schema::table('purchase_invoices', function (Blueprint $table) {
                // Drop the existing foreign key constraint
                try {
                    $table->dropForeign(['supplier_id']);
                } catch (\Exception $e) {
                    // Foreign key غير موجود
                }
                
                // Add the correct foreign key constraint to suppliers table
                $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: لا نفعل شيء
        } else {
            // MySQL
            Schema::table('purchase_invoices', function (Blueprint $table) {
                // Drop the correct foreign key constraint
                try {
                    $table->dropForeign(['supplier_id']);
                } catch (\Exception $e) {
                    // Already dropped
                }
                
                // Restore the old foreign key constraint to customers table
                $table->foreign('supplier_id')->references('id')->on('customers')->onDelete('set null');
            });
        }
    }
};