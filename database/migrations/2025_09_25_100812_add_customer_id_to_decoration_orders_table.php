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
        // تسجيل enum type في Doctrine لتجنب الأخطاء
        try {
            $platform = DB::connection()->getDoctrineSchemaManager()->getDatabasePlatform();
            $platform->registerDoctrineTypeMapping('enum', 'string');
        } catch (\Exception $e) {
            // Ignore if already registered
        }
        
        Schema::table('decoration_orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('assigned_employee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->datetime('scheduled_date')->nullable();
            $table->datetime('received_at')->nullable();
            $table->datetime('executing_at')->nullable();
            $table->datetime('partial_payment_at')->nullable();
            $table->datetime('full_payment_at')->nullable();
        });
        
        // تحديث enum باستخدام SQL مباشر للـ MySQL فقط
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE decoration_orders MODIFY COLUMN status ENUM('created', 'received', 'executing', 'partial_payment', 'full_payment', 'completed', 'cancelled') DEFAULT 'created'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('decoration_orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['assigned_employee_id']);
            $table->dropColumn([
                'customer_id', 'assigned_employee_id', 'paid_amount',
                'scheduled_date', 'received_at', 'executing_at',
                'partial_payment_at', 'full_payment_at'
            ]);
        });
        
        // استعادة enum القديم للـ MySQL
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE decoration_orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'paid') DEFAULT 'pending'");
        }
    }
};
