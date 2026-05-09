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
        if (! Schema::hasTable('suppliers')) {
            return;
        }

        // إذا كان الجدول مُهاجراً مسبقاً (لا يوجد عمود uuid ونوع id هو string) نتخطى
        if (! Schema::hasColumn('suppliers', 'uuid') && Schema::hasColumn('suppliers', 'id')) {
            $idType = Schema::getColumnType('suppliers', 'id');
            if (in_array(strtolower($idType ?? ''), ['string', 'text', 'guid'], true)) {
                return;
            }
        }

        if (! Schema::hasColumn('suppliers', 'uuid')) {
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

        // بدون doctrine/dbal لا يعمل ->change()؛ نستخدم ALTER خام على MySQL (SQLite: الصفوف مكتملة والخطوات التالية كافية)
        if (Schema::hasColumn('suppliers', 'uuid')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE suppliers MODIFY uuid VARCHAR(36) NOT NULL');
            }
        }

        if ($driver === 'sqlite') {
            DB::connection()->getPdo()->exec('PRAGMA foreign_keys = OFF');
        }

        try {
            $childTables = [
                ['table' => 'purchase_invoices', 'fk' => 'supplier_id'],
                ['table' => 'supplier_balances', 'fk' => 'supplier_id'],
            ];
            foreach ($childTables as $child) {
                if (! Schema::hasTable($child['table']) || ! Schema::hasColumn($child['table'], $child['fk'])) {
                    continue;
                }
                if (! Schema::hasColumn($child['table'], $child['fk'].'_uuid')) {
                    Schema::table($child['table'], function (Blueprint $table) use ($child) {
                        $table->string($child['fk'].'_uuid', 36)->nullable()->after($child['fk']);
                    });
                }
                $map = DB::table('suppliers')->pluck('uuid', 'id');
                foreach (DB::table($child['table'])->whereNotNull($child['fk'])->get() as $row) {
                    $uuid = $map[$row->{$child['fk']}] ?? null;
                    if ($uuid) {
                        DB::table($child['table'])->where('id', $row->id)->update([$child['fk'].'_uuid' => $uuid]);
                    }
                }
                if ($driver === 'mysql') {
                    Schema::table($child['table'], function (Blueprint $table) use ($child) {
                        $table->dropForeign([$child['fk']]);
                    });
                    Schema::table($child['table'], function (Blueprint $table) use ($child) {
                        $table->dropColumn($child['fk']);
                    });
                } else {
                    // SQLite: Schema::dropColumn / ALTER DROP COLUMN يكسر تعريف FK الداخلي؛ نعيد بناء الجدول بدون العمود
                    $this->sqliteRecreateTableWithoutColumns($child['table'], [$child['fk']]);
                }
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
                // SQLite لا يدعم إسقاط عمود المفتاح الأساسي عبر ALTER؛ نعيد بناء الجدول بـ id نصي من عمود uuid
                $this->sqliteRebuildSuppliersUuidAsPrimaryKey();
            }

            foreach ($childTables as $child) {
                if (! Schema::hasTable($child['table']) || ! Schema::hasColumn($child['table'], $child['fk'].'_uuid')) {
                    continue;
                }
                if ($driver === 'mysql') {
                    Schema::table($child['table'], function (Blueprint $table) use ($child) {
                        $table->renameColumn($child['fk'].'_uuid', $child['fk']);
                    });
                    Schema::table($child['table'], function (Blueprint $table) use ($child) {
                        $table->foreign($child['fk'])->references('id')->on('suppliers')->onDelete($child['table'] === 'purchase_invoices' ? 'set null' : 'cascade');
                    });
                } else {
                    // SQLite: renameColumn يعتمد على DBAL؛ نستخدم إعادة بناء بسيطة لإعادة تسمية عمود uuid
                    $this->sqliteRenameColumnPreservingData($child['table'], $child['fk'].'_uuid', $child['fk']);
                }
            }
        } finally {
            if ($driver === 'sqlite') {
                DB::connection()->getPdo()->exec('PRAGMA foreign_keys = ON');
            }
        }
    }

    /**
     * إعادة إنشاء جدول SQLite بدون أعمدة محددة، مع الحفاظ على البيانات وبنية تقريبية من PRAGMA table_info.
     *
     * @param  array<int, string>  $omitColumns
     */
    protected function sqliteRecreateTableWithoutColumns(string $tableName, array $omitColumns): void
    {
        $omit = array_flip($omitColumns);
        $qTable = $this->sqliteQuoteIdent($tableName);
        $rows = DB::select("PRAGMA table_info({$qTable})");

        $defs = [];
        $selectParts = [];
        foreach ($rows as $col) {
            if (isset($omit[$col->name])) {
                continue;
            }
            $cn = $this->sqliteQuoteIdent($col->name);
            $selectParts[] = $cn;

            $type = $col->type !== '' && $col->type !== null ? $col->type : 'TEXT';
            $line = $cn.' '.$type;

            if ((int) $col->pk === 1) {
                $line .= ' PRIMARY KEY AUTOINCREMENT';
            } elseif ((int) $col->notnull === 1) {
                $line .= ' NOT NULL';
            }

            if ($col->dflt_value !== null && $col->dflt_value !== '') {
                $line .= ' DEFAULT '.$col->dflt_value;
            }

            $defs[] = $line;
        }

        $tmp = $tableName.'__uuid_rebuild_'.substr(sha1($tableName), 0, 8);
        $qTmp = $this->sqliteQuoteIdent($tmp);

        DB::statement('DROP TABLE IF EXISTS '.$qTmp);
        DB::statement('CREATE TABLE '.$qTmp.' ('.implode(', ', $defs).')');
        DB::statement('INSERT INTO '.$qTmp.' SELECT '.implode(', ', $selectParts).' FROM '.$qTable);
        DB::statement('DROP TABLE '.$qTable);
        DB::statement('ALTER TABLE '.$qTmp.' RENAME TO '.$qTable);
    }

    /**
     * إعادة تسمية عمود في SQLite بدون doctrine/dbal (نسخ جدول + بيانات).
     */
    protected function sqliteRenameColumnPreservingData(string $tableName, string $from, string $to): void
    {
        if (! Schema::hasColumn($tableName, $from)) {
            return;
        }
        if (Schema::hasColumn($tableName, $to)) {
            return;
        }

        $qTable = $this->sqliteQuoteIdent($tableName);
        $rows = DB::select("PRAGMA table_info({$qTable})");

        $defs = [];
        $targetCols = [];
        $sourceExprs = [];

        foreach ($rows as $col) {
            $targetName = $col->name === $from ? $to : $col->name;
            $qTarget = $this->sqliteQuoteIdent($targetName);
            $qSource = $this->sqliteQuoteIdent($col->name);

            $targetCols[] = $qTarget;
            $sourceExprs[] = $qSource;

            $type = $col->type !== '' && $col->type !== null ? $col->type : 'TEXT';
            $line = $qTarget.' '.$type;

            if ((int) $col->pk === 1) {
                $line .= ' PRIMARY KEY AUTOINCREMENT';
            } elseif ((int) $col->notnull === 1) {
                $line .= ' NOT NULL';
            }

            if ($col->dflt_value !== null && $col->dflt_value !== '') {
                $line .= ' DEFAULT '.$col->dflt_value;
            }

            $defs[] = $line;
        }

        $tmp = $tableName.'__rename_'.substr(sha1($from.$to), 0, 8);
        $qTmp = $this->sqliteQuoteIdent($tmp);

        DB::statement('DROP TABLE IF EXISTS '.$qTmp);
        DB::statement('CREATE TABLE '.$qTmp.' ('.implode(', ', $defs).')');
        DB::statement(
            'INSERT INTO '.$qTmp.' ('.implode(', ', $targetCols).') SELECT '.implode(', ', $sourceExprs).' FROM '.$qTable
        );
        DB::statement('DROP TABLE '.$qTable);
        DB::statement('ALTER TABLE '.$qTmp.' RENAME TO '.$qTable);
    }

    protected function sqliteQuoteIdent(string $name): string
    {
        return '"'.str_replace('"', '""', $name).'"';
    }

    /**
     * استبدال suppliers.id الصحيحي بـ UUID نصي: id الجديد = قيمة عمود uuid، حذف الأعمدة id السابق وuuid.
     */
    protected function sqliteRebuildSuppliersUuidAsPrimaryKey(): void
    {
        $qTable = $this->sqliteQuoteIdent('suppliers');
        $rows = DB::select("PRAGMA table_info({$qTable})");

        $defs = [];
        $selectParts = [];

        $defs[] = $this->sqliteQuoteIdent('id').' TEXT NOT NULL PRIMARY KEY';
        $selectParts[] = $this->sqliteQuoteIdent('uuid');

        foreach ($rows as $col) {
            if (in_array($col->name, ['id', 'uuid'], true)) {
                continue;
            }
            $cn = $this->sqliteQuoteIdent($col->name);
            $selectParts[] = $cn;

            $type = $col->type !== '' && $col->type !== null ? $col->type : 'TEXT';
            $line = $cn.' '.$type;
            if ((int) $col->notnull === 1) {
                $line .= ' NOT NULL';
            }
            if ($col->dflt_value !== null && $col->dflt_value !== '') {
                $line .= ' DEFAULT '.$col->dflt_value;
            }
            $defs[] = $line;
        }

        $tmp = 'suppliers__uuid_pk_'.substr(sha1('suppliers'), 0, 8);
        $qTmp = $this->sqliteQuoteIdent($tmp);

        DB::statement('DROP TABLE IF EXISTS '.$qTmp);
        DB::statement('CREATE TABLE '.$qTmp.' ('.implode(', ', $defs).')');
        DB::statement('INSERT INTO '.$qTmp.' SELECT '.implode(', ', $selectParts).' FROM '.$qTable);
        DB::statement('DROP TABLE '.$qTable);
        DB::statement('ALTER TABLE '.$qTmp.' RENAME TO '.$qTable);
    }

    public function down(): void
    {
        throw new \RuntimeException('Rollback of UUID migration not supported. Restore from backup if needed.');
    }
};
