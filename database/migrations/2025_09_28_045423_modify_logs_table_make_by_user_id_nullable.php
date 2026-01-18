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
        // هذا Migration تم استبداله بـ fix_logs_table_nullable_by_user_id
        // الـ migration الجديد يدعم SQLite و MySQL
        
        // لا نفعل شيء هنا لأن الـ migration الجديد سيتولى الأمر
        // نحتفظ بهذا الملف فقط لتجنب مشاكل migrations table
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا نفعل شيء
    }
};
