<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->string('company_name')->nullable();
            $table->string('whatsapp', 32)->nullable();
            $table->string('phone_country_code', 8)->default('964');
            $table->string('default_currency', 8)->default('USD');
            $table->timestamps();
        });

        Schema::create('shop_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('bundle_quantity')->nullable();
            $table->decimal('bundle_price', 12, 2)->nullable();
            $table->string('bundle_currency', 8)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shop_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_category_id')->nullable()->constrained('shop_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 8)->default('USD');
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->string('video_url')->nullable();
            $table->json('youtube_links')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shop_cart_promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('min_cart_total', 12, 2)->default(0);
            $table->enum('discount_type', ['fixed', 'percent'])->default('fixed');
            $table->decimal('discount_value', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shop_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->decimal('min_cart_total', 12, 2)->default(0);
            $table->enum('discount_type', ['fixed', 'percent'])->default('fixed');
            $table->decimal('discount_value', 12, 2);
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shop_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number', 32)->unique();
            $table->string('customer_phone', 32);
            $table->text('customer_notes')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('bundle_discount', 12, 2)->default(0);
            $table->decimal('promotion_discount', 12, 2)->default(0);
            $table->decimal('coupon_discount', 12, 2)->default(0);
            $table->string('coupon_code', 64)->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('USD');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->timestamp('whatsapp_sent_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('shop_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_order_id')->constrained('shop_orders')->cascadeOnDelete();
            $table->foreignUuid('shop_product_id')->nullable()->constrained('shop_products')->nullOnDelete();
            $table->string('product_name');
            $table->string('category_name')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_order_items');
        Schema::dropIfExists('shop_orders');
        Schema::dropIfExists('shop_coupons');
        Schema::dropIfExists('shop_cart_promotions');
        Schema::dropIfExists('shop_products');
        Schema::dropIfExists('shop_categories');
        Schema::dropIfExists('shop_settings');
    }
};
