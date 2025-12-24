<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Artisan;

class ReportPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Report permissions
        Permission::firstOrCreate(['name' => 'read reports']);
        Permission::firstOrCreate(['name' => 'view reports']);

        // Get roles
        $superAdminRole = Role::where('name', 'superadmin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $staffRole = Role::where('name', 'staff')->first();

        // Assign permissions to superadmin
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo([
                'read reports',
                'view reports'
            ]);
        }

        // Assign permissions to admin
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'read reports',
                'view reports'
            ]);
        }

        // Assign read and view permissions to staff
        if ($staffRole) {
            $staffRole->givePermissionTo([
                'read reports',
                'view reports'
            ]);
        }

        // Clear permission cache
        try {
            Artisan::call('permission:cache-reset');
        } catch (\Exception $e) {
            // If command doesn't exist, use alternative method
            Artisan::call('cache:forget', ['key' => 'spatie.permission.cache']);
        }
    }
}

