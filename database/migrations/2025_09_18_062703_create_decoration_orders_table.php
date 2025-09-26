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
        Schema::create('decoration_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decoration_id')->constrained()->onDelete('cascade'); // الديكور المطلوب
            $table->string('customer_name'); // اسم العميل
            $table->string('customer_phone'); // رقم هاتف العميل
            $table->string('customer_email')->nullable(); // إيميل العميل
            $table->text('event_address'); // عنوان الحدث
            $table->datetime('event_date'); // تاريخ الحدث
            $table->time('event_time'); // وقت الحدث
            $table->integer('guest_count')->default(50); // عدد الضيوف
            $table->text('special_requests')->nullable(); // طلبات خاصة
            $table->json('selected_items')->nullable(); // العناصر المختارة
            $table->decimal('base_price', 10, 2); // السعر الأساسي
            $table->decimal('additional_cost', 10, 2)->default(0); // التكلفة الإضافية
            $table->decimal('discount', 10, 2)->default(0); // الخصم
            $table->decimal('total_price', 10, 2); // السعر الإجمالي
            $table->enum('status', [
                'pending', 'confirmed', 'in_progress', 
                'completed', 'cancelled', 'paid'
            ])->default('pending'); // حالة الطلب
            $table->foreignId('assigned_team_id')->nullable()->constrained('decoration_teams')->onDelete('set null'); // الفريق المكلف
            $table->text('notes')->nullable(); // ملاحظات
            $table->datetime('completed_at')->nullable(); // تاريخ الإنجاز
            $table->datetime('paid_at')->nullable(); // تاريخ الدفع
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decoration_orders');
    }
};
