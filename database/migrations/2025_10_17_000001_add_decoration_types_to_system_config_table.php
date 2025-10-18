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
        Schema::table('system_config', function (Blueprint $table) {
            $table->json('decoration_types')->nullable()->after('exchange_rate');
        });
        
        // Add default decoration types
        $defaultTypes = [
            ['value' => 'birthday', 'label_ar' => 'عيد ميلاد', 'label_en' => 'Birthday'],
            ['value' => 'gender_reveal', 'label_ar' => 'تحديد جنس المولود', 'label_en' => 'Gender Reveal'],
            ['value' => 'baby_shower', 'label_ar' => 'حفلة الولادة', 'label_en' => 'Baby Shower'],
            ['value' => 'wedding', 'label_ar' => 'زفاف', 'label_en' => 'Wedding'],
            ['value' => 'graduation', 'label_ar' => 'تخرج', 'label_en' => 'Graduation'],
            ['value' => 'corporate', 'label_ar' => 'شركات', 'label_en' => 'Corporate'],
            ['value' => 'religious', 'label_ar' => 'ديني', 'label_en' => 'Religious'],
            ['value' => 'other', 'label_ar' => 'أخرى', 'label_en' => 'Other'],
        ];
        
        // Update first row if exists, otherwise create
        $config = DB::table('system_config')->first();
        if ($config) {
            DB::table('system_config')
                ->where('id', $config->id)
                ->update(['decoration_types' => json_encode($defaultTypes)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_config', function (Blueprint $table) {
            $table->dropColumn('decoration_types');
        });
    }
};

