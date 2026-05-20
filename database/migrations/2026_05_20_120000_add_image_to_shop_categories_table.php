<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_categories', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
        });

        foreach (DB::table('shop_categories')->get() as $row) {
            $images = json_decode($row->images ?? '[]', true);
            if (!empty($images[0]) && empty($row->image)) {
                DB::table('shop_categories')->where('id', $row->id)->update(['image' => $images[0]]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('shop_categories', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
