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
        if (!Schema::hasTable('sync_queue')) {
            Schema::create('sync_queue', function (Blueprint $table) {
                $table->id();
                $table->string('table_name');
                $table->unsignedBigInteger('record_id');
                $table->enum('action', ['insert', 'update', 'delete']);
                $table->json('data')->nullable(); // البيانات الكاملة للسجل
                $table->json('changes')->nullable(); // الحقول التي تغيرت (للتحديثات)
                $table->enum('status', ['pending', 'synced', 'failed'])->default('pending');
                $table->unsignedInteger('retry_count')->default(0);
                $table->text('error_message')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();

                // Indexes for performance
                $table->index(['table_name', 'record_id', 'status']);
                $table->index('status');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_queue');
    }
};

