<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sync_queue')) {
            return;
        }
        Schema::table('sync_queue', function (Blueprint $table) {
            $table->string('record_id', 36)->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sync_queue')) {
            return;
        }
        Schema::table('sync_queue', function (Blueprint $table) {
            $table->unsignedBigInteger('record_id')->change();
        });
    }
};
