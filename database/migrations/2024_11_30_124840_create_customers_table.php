<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // الاسم إجباري
            $table->string('phone', 15)->nullable(); // الهاتف اختياري
            $table->text('address')->nullable(); // العنوان اختياري
            $table->boolean('is_active')->default(true); // الحالة افتراضيًا نشط
            $table->timestamp('last_purchase_date')->nullable(); // تاريخ آخر عملية شراء اختياري
            $table->text('notes')->nullable(); // ملاحظات العميل اختياري
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
