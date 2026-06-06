<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->decimal('exchange_rate', 14, 2)->nullable()->after('default_currency');
            $table->string('logo')->nullable()->after('company_name');
            $table->string('primary_color', 16)->nullable()->after('logo');
            $table->string('tagline', 500)->nullable()->after('primary_color');
            $table->string('seo_title')->nullable()->after('tagline');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->string('seo_keywords', 500)->nullable()->after('seo_description');
        });
    }

    public function down(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropColumn([
                'exchange_rate',
                'logo',
                'primary_color',
                'tagline',
                'seo_title',
                'seo_description',
                'seo_keywords',
            ]);
        });
    }
};
