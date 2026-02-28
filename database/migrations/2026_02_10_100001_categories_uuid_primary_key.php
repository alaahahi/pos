<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (!Schema::hasTable('categories')) {
            return;
        }

        // إذا كان الجدول مُهاجراً مسبقاً (لا يوجد عمود uuid ونوع id هو string) نتخطى
        if (!Schema::hasColumn('categories', 'uuid') && Schema::hasColumn('categories', 'id')) {
            $idType = Schema::getColumnType('categories', 'id');
            if (in_array(strtolower($idType ?? ''), ['string', 'text', 'guid'], true)) {
                return;
            }
        }

        // تجنب إضافة العمود مرتين (عند التشغيل على SQLite أو إعادة تشغيل الهجرة)
        if (!Schema::hasColumn('categories', 'uuid')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('uuid', 36)->unique()->nullable()->after('id');
            });
        }

        foreach (DB::table('categories')->get() as $row) {
            $currentUuid = $row->uuid ?? null;
            if ($currentUuid === null || $currentUuid === '') {
                DB::table('categories')->where('id', $row->id)->update([
                    'uuid' => (string) Str::uuid(),
                ]);
            }
        }

        if (Schema::hasColumn('categories', 'uuid')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('uuid', 36)->nullable(false)->change();
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'category_id') && !Schema::hasColumn('products', 'category_uuid')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('category_uuid', 36)->nullable()->after('category_id');
            });
            $map = DB::table('categories')->pluck('uuid', 'id');
            foreach (DB::table('products')->whereNotNull('category_id')->get() as $p) {
                $uuid = $map[$p->category_id] ?? null;
                if ($uuid) {
                    DB::table('products')->where('id', $p->id)->update(['category_uuid' => $uuid]);
                }
            }
            // SQLite لا يدعم dropForeign؛ نُسقِط العمود فقط
            if ($driver === 'mysql') {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropForeign(['category_id']);
                });
            }
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('category_id');
            });
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE categories DROP PRIMARY KEY');
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('id');
            });
            Schema::table('categories', function (Blueprint $table) {
                $table->renameColumn('uuid', 'id');
            });
            DB::statement('ALTER TABLE categories ADD PRIMARY KEY (id)');
        } else {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropPrimary();
            });
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('id');
            });
            Schema::table('categories', function (Blueprint $table) {
                $table->renameColumn('uuid', 'id');
            });
            Schema::table('categories', function (Blueprint $table) {
                $table->primary('id');
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'category_uuid')) {
            Schema::table('products', function (Blueprint $table) {
                $table->renameColumn('category_uuid', 'category_id');
            });
            // SQLite لا يدعم إضافة foreign key لجدول موجود بسهولة
            if ($driver === 'mysql') {
                Schema::table('products', function (Blueprint $table) {
                    $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        throw new \RuntimeException('Rollback of UUID migration not supported. Restore from backup if needed.');
    }
};
