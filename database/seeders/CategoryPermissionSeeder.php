<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CategoryPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Category permissions
        Permission::firstOrCreate(['name' => 'create category']);
        Permission::firstOrCreate(['name' => 'read category']);
        Permission::firstOrCreate(['name' => 'update category']);
        Permission::firstOrCreate(['name' => 'delete category']);
        Permission::firstOrCreate(['name' => 'view category']);

        // Get roles
        $superAdminRole = Role::where('name', 'superadmin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $staffRole = Role::where('name', 'staff')->first();

        if ($superAdminRole) {
            $superAdminRole->givePermissionTo([
                'create category', 
                'read category', 
                'update category', 
                'delete category', 
                'view category'
            ]);
        }

        if ($adminRole) {
            $adminRole->givePermissionTo([
                'create category', 
                'read category', 
                'update category', 
                'delete category', 
                'view category'
            ]);
        }

        if ($staffRole) {
            $staffRole->givePermissionTo([
                'read category', 
                'view category'
            ]);
        }
    }
}

