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
        Schema::table('boxes', function (Blueprint $table) {
            // Make name nullable if it exists
            if (Schema::hasColumn('boxes', 'name')) {
                $table->string('name')->nullable()->change();
            }
            
            // Check and add missing columns
            if (!Schema::hasColumn('boxes', 'amount')) {
                $table->decimal('amount', 15, 2)->default(0)->after('balance_usd');
            }
            if (!Schema::hasColumn('boxes', 'type')) {
                $table->string('type')->nullable()->after('amount');
            }
            if (!Schema::hasColumn('boxes', 'description')) {
                $table->text('description')->nullable()->after('type');
            }
            if (!Schema::hasColumn('boxes', 'is_pay')) {
                $table->boolean('is_pay')->default(false)->after('description');
            }
            if (!Schema::hasColumn('boxes', 'morphed_id')) {
                $table->unsignedBigInteger('morphed_id')->nullable()->after('is_pay');
            }
            if (!Schema::hasColumn('boxes', 'morphed_type')) {
                $table->string('morphed_type')->nullable()->after('morphed_id');
            }
            if (!Schema::hasColumn('boxes', 'currency')) {
                $table->string('currency', 3)->default('IQD')->after('morphed_type');
            }
            if (!Schema::hasColumn('boxes', 'created')) {
                $table->date('created')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('boxes', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0)->after('created');
            }
            if (!Schema::hasColumn('boxes', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('discount');
            }
            if (!Schema::hasColumn('boxes', 'details')) {
                $table->json('details')->nullable()->after('parent_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boxes', function (Blueprint $table) {
            $columnsToCheck = [
                'amount', 'type', 'description', 'is_pay', 
                'morphed_id', 'morphed_type', 'currency', 
                'created', 'discount', 'parent_id', 'details'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('boxes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

