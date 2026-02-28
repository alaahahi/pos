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
        if (!Schema::hasTable('suppliers')) {
            return;
        }

        // إذا كان الجدول مُهاجراً مسبقاً (لا يوجد عمود uuid ونوع id هو string) نتخطى
        if (!Schema::hasColumn('suppliers', 'uuid') && Schema::hasColumn('suppliers', 'id')) {
            $idType = Schema::getColumnType('suppliers', 'id');
            if (in_array(strtolower($idType ?? ''), ['string', 'text', 'guid'], true)) {
                return;
            }
        }

        if (!Schema::hasColumn('suppliers', 'uuid')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->string('uuid', 36)->unique()->nullable()->after('id');
            });
        }

        foreach (DB::table('suppliers')->get() as $row) {
            $currentUuid = $row->uuid ?? null;
            if ($currentUuid === null || $currentUuid === '') {
                DB::table('suppliers')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
            }
        }

        if (Schema::hasColumn('suppliers', 'uuid')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->string('uuid', 36)->nullable(false)->change();
            });
        }

        $childTables = [
            ['table' => 'purchase_invoices', 'fk' => 'supplier_id'],
            ['table' => 'supplier_balances', 'fk' => 'supplier_id'],
        ];
        foreach ($childTables as $child) {
            if (!Schema::hasTable($child['table']) || !Schema::hasColumn($child['table'], $child['fk'])) {
                continue;
            }
            if (!Schema::hasColumn($child['table'], $child['fk'] . '_uuid')) {
                Schema::table($child['table'], function (Blueprint $table) use ($child) {
                    $table->string($child['fk'] . '_uuid', 36)->nullable()->after($child['fk']);
                });
            }
            $map = DB::table('suppliers')->pluck('uuid', 'id');
            foreach (DB::table($child['table'])->whereNotNull($child['fk'])->get() as $row) {
                $uuid = $map[$row->{$child['fk']}] ?? null;
                if ($uuid) {
                    DB::table($child['table'])->where('id', $row->id)->update([$child['fk'] . '_uuid' => $uuid]);
                }
            }
            // SQLite لا يدعم dropForeign؛ نُسقِط العمود فقط (إعادة إنشاء الجدول عند الحاجة)
            if ($driver === 'mysql') {
                Schema::table($child['table'], function (Blueprint $table) use ($child) {
                    $table->dropForeign([$child['fk']]);
                });
            }
            Schema::table($child['table'], function (Blueprint $table) use ($child) {
                $table->dropColumn($child['fk']);
            });
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE suppliers DROP PRIMARY KEY');
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropColumn('id');
            });
            Schema::table('suppliers', function (Blueprint $table) {
                $table->renameColumn('uuid', 'id');
            });
            DB::statement('ALTER TABLE suppliers ADD PRIMARY KEY (id)');
        } else {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropPrimary();
            });
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropColumn('id');
            });
            Schema::table('suppliers', function (Blueprint $table) {
                $table->renameColumn('uuid', 'id');
            });
            Schema::table('suppliers', function (Blueprint $table) {
                $table->primary('id');
            });
        }

        foreach ($childTables as $child) {
            if (!Schema::hasTable($child['table']) || !Schema::hasColumn($child['table'], $child['fk'] . '_uuid')) {
                continue;
            }
            Schema::table($child['table'], function (Blueprint $table) use ($child) {
                $table->renameColumn($child['fk'] . '_uuid', $child['fk']);
            });
            // SQLite لا يدعم إضافة foreign key لجدول موجود بسهولة
            if ($driver === 'mysql') {
                Schema::table($child['table'], function (Blueprint $table) use ($child) {
                    $table->foreign($child['fk'])->references('id')->on('suppliers')->onDelete($child['table'] === 'purchase_invoices' ? 'set null' : 'cascade');
                });
            }
        }
    }

    public function down(): void
    {
        throw new \RuntimeException('Rollback of UUID migration not supported. Restore from backup if needed.');
    }
};
