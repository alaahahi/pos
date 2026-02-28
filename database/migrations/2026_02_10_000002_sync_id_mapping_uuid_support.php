<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sync_id_mapping')) {
            return;
        }
        Schema::table('sync_id_mapping', function (Blueprint $table) {
            $table->string('local_id', 36)->change();
            $table->string('server_id', 36)->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sync_id_mapping')) {
            return;
        }
        Schema::table('sync_id_mapping', function (Blueprint $table) {
            $table->unsignedBigInteger('local_id')->change();
            $table->unsignedBigInteger('server_id')->change();
        });
    }
};
