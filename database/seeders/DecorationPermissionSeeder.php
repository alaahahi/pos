<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DecorationPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Decoration permissions
        Permission::firstOrCreate(['name' => 'create decoration']);
        Permission::firstOrCreate(['name' => 'read decoration']);
        Permission::firstOrCreate(['name' => 'update decoration']);
        Permission::firstOrCreate(['name' => 'delete decoration']);
        Permission::firstOrCreate(['name' => 'view decoration']);

        // Get roles
        $superAdminRole = Role::where('name', 'superadmin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $staffRole = Role::where('name', 'staff')->first();

        if ($superAdminRole) {
            $superAdminRole->givePermissionTo(['create decoration', 'read decoration', 'update decoration', 'delete decoration', 'view decoration']);
        }

        if ($adminRole) {
            $adminRole->givePermissionTo(['create decoration', 'read decoration', 'update decoration', 'delete decoration', 'view decoration']);
        }

        if ($staffRole) {
            $staffRole->givePermissionTo(['read decoration', 'view decoration', 'create decoration']);
        }
    }
}
