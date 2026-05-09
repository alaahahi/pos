<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * إذا بقي عمود suppliers.id من نوع INTEGER في SQLite بينما النموذج يستخدم UUID نصي،
 * يفشل الإدراج بـ SQLSTATE[HY000]: datatype mismatch (20).
 * يُعاد تشغيل ترحيل UUID عند اكتشاف مفتاح أساسي صحيحي فقط.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        if (! Schema::hasTable('suppliers')) {
            return;
        }

        $cols = DB::select('PRAGMA table_info(suppliers)');
        $idRow = collect($cols)->firstWhere('name', 'id');
        if (! $idRow) {
            return;
        }

        $type = strtoupper((string) ($idRow->type ?? ''));
        $pk = (int) ($idRow->pk ?? 0);

        $integerPrimaryKey = $pk === 1
            && ($type === 'INTEGER' || str_contains($type, 'INT'));

        if (! $integerPrimaryKey) {
            return;
        }

        $migration = require database_path('migrations/2026_02_10_100002_suppliers_uuid_primary_key.php');
        $migration->up();
    }

    public function down(): void
    {
        //
    }
};
