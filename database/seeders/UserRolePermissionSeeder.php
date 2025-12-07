<?php

namespace  Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Create Permissions
        Permission::firstOrCreate(['name' => 'create roles']);
        Permission::firstOrCreate(['name' => 'read roles']);
        Permission::firstOrCreate(['name' => 'update roles']);
        Permission::firstOrCreate(['name' => 'delete roles']);
        Permission::firstOrCreate(['name' => 'view roles']);

        Permission::firstOrCreate(['name' => 'create permissions']);
        Permission::firstOrCreate(['name' => 'read permissions']);
        Permission::firstOrCreate(['name' => 'update permissions']);
        Permission::firstOrCreate(['name' => 'delete permissions']);
        Permission::firstOrCreate(['name' => 'view permissions']);

        Permission::firstOrCreate(['name' => 'create users']);
        Permission::firstOrCreate(['name' => 'read users']);
        Permission::firstOrCreate(['name' => 'update users']);
        Permission::firstOrCreate(['name' => 'delete users']);
        Permission::firstOrCreate(['name' => 'view users']);

        Permission::firstOrCreate(['name' => 'create products']);
        Permission::firstOrCreate(['name' => 'read products']);
        Permission::firstOrCreate(['name' => 'update products']);
        Permission::firstOrCreate(['name' => 'delete products']);
        Permission::firstOrCreate(['name' => 'view products']);

        Permission::firstOrCreate(['name' => 'create logs']);
        Permission::firstOrCreate(['name' => 'read logs']);
        Permission::firstOrCreate(['name' => 'update logs']);
        Permission::firstOrCreate(['name' => 'delete logs']);
        Permission::firstOrCreate(['name' => 'view logs']);

        // Decoration permissions
        Permission::firstOrCreate(['name' => 'create decoration']);
        Permission::firstOrCreate(['name' => 'read decoration']);
        Permission::firstOrCreate(['name' => 'update decoration']);
        Permission::firstOrCreate(['name' => 'delete decoration']);
        Permission::firstOrCreate(['name' => 'view decoration']);

        // Decoration payments permissions
        Permission::firstOrCreate(['name' => 'create payment']);
        Permission::firstOrCreate(['name' => 'read payment']);
        Permission::firstOrCreate(['name' => 'update payment']);
        Permission::firstOrCreate(['name' => 'delete payment']);
        Permission::firstOrCreate(['name' => 'view payment']);

        // Monthly accounting permissions
        Permission::firstOrCreate(['name' => 'create monthly_accounting']);
        Permission::firstOrCreate(['name' => 'read monthly_accounting']);
        Permission::firstOrCreate(['name' => 'update monthly_accounting']);
        Permission::firstOrCreate(['name' => 'delete monthly_accounting']);
        Permission::firstOrCreate(['name' => 'view monthly_accounting']);

        // Migration management permissions
        Permission::firstOrCreate(['name' => 'manage migrations']);

        // System config permissions
        Permission::firstOrCreate(['name' => 'read system_config']);
        Permission::firstOrCreate(['name' => 'update system_config']);

        // Expenses permissions
        Permission::firstOrCreate(['name' => 'create expenses']);
        Permission::firstOrCreate(['name' => 'read expenses']);
        Permission::firstOrCreate(['name' => 'update expenses']);
        Permission::firstOrCreate(['name' => 'delete expenses']);

        // Customer permissions
        Permission::firstOrCreate(['name' => 'create customer']);
        Permission::firstOrCreate(['name' => 'read customers']);
        Permission::firstOrCreate(['name' => 'update customer']);
        Permission::firstOrCreate(['name' => 'delete customer']);
        Permission::firstOrCreate(['name' => 'view customer']);

        // Supplier permissions
        Permission::firstOrCreate(['name' => 'create supplier']);
        Permission::firstOrCreate(['name' => 'read supplier']);
        Permission::firstOrCreate(['name' => 'update supplier']);
        Permission::firstOrCreate(['name' => 'delete supplier']);
        Permission::firstOrCreate(['name' => 'view supplier']);

        // Order permissions
        Permission::firstOrCreate(['name' => 'create order']);
        Permission::firstOrCreate(['name' => 'read order']);
        Permission::firstOrCreate(['name' => 'update order']);
        Permission::firstOrCreate(['name' => 'delete order']);
        Permission::firstOrCreate(['name' => 'view order']);

        // Box permissions
        Permission::firstOrCreate(['name' => 'create box']);
        Permission::firstOrCreate(['name' => 'read boxes']);
        Permission::firstOrCreate(['name' => 'update box']);
        Permission::firstOrCreate(['name' => 'delete box']);
        Permission::firstOrCreate(['name' => 'view box']);

        // Product permissions (corrected names)
        Permission::firstOrCreate(['name' => 'create product']);
        Permission::firstOrCreate(['name' => 'read product']);
        Permission::firstOrCreate(['name' => 'update product']);
        Permission::firstOrCreate(['name' => 'delete product']);
        Permission::firstOrCreate(['name' => 'view product']);

        // Category permissions
        Permission::firstOrCreate(['name' => 'create category']);
        Permission::firstOrCreate(['name' => 'read category']);
        Permission::firstOrCreate(['name' => 'update category']);
        Permission::firstOrCreate(['name' => 'delete category']);
        Permission::firstOrCreate(['name' => 'view category']);


    // Create Roles 
        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin']); //as super-admin
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $userRole = Role::firstOrCreate(['name' => 'user']);
         // Lets give all permission to super-admin role.
         $allPermissionNames = Permission::pluck('name')->toArray();

         $superAdminRole->givePermissionTo($allPermissionNames);
 
         // Let's give few permissions to admin role.
         $adminRole->givePermissionTo(['create roles','read roles', 'view roles', 'update roles']);
         $adminRole->givePermissionTo(['create permissions','read permissions', 'view permissions']);
         $adminRole->givePermissionTo(['create users', 'read users','view users', 'update users']);
         $adminRole->givePermissionTo(['create logs', 'read logs','view logs', 'update logs']);
         $adminRole->givePermissionTo(['create decoration', 'read decoration', 'view decoration', 'update decoration', 'delete decoration']);
         $adminRole->givePermissionTo(['create payment', 'read payment', 'view payment', 'update payment', 'delete payment']);
         $adminRole->givePermissionTo(['create monthly_accounting', 'read monthly_accounting', 'view monthly_accounting', 'update monthly_accounting', 'delete monthly_accounting']);
         $adminRole->givePermissionTo(['manage migrations']);
         $adminRole->givePermissionTo(['read system_config', 'update system_config']);
         $adminRole->givePermissionTo(['create expenses', 'read expenses', 'update expenses', 'delete expenses']);
         
         // Add missing permissions for admin role
         $adminRole->givePermissionTo(['create customer', 'read customers', 'update customer', 'delete customer', 'view customer']);
         $adminRole->givePermissionTo(['create supplier', 'read supplier', 'update supplier', 'delete supplier', 'view supplier']);
         $adminRole->givePermissionTo(['create order', 'read order', 'update order', 'delete order', 'view order']);
         $adminRole->givePermissionTo(['create category', 'read category', 'update category', 'delete category', 'view category']);
         $adminRole->givePermissionTo(['create product', 'read product', 'update product', 'delete product', 'view product']);
         $adminRole->givePermissionTo(['create box', 'read boxes', 'update box', 'delete box', 'view box']);
         $adminRole->givePermissionTo(['create product', 'read product', 'update product', 'delete product', 'view product']);
         
         // Let's give decoration permissions to staff role
         $staffRole->givePermissionTo(['read decoration', 'view decoration', 'create decoration']);
         $staffRole->givePermissionTo(['read payment', 'view payment', 'create payment']);
         $staffRole->givePermissionTo(['read monthly_accounting', 'view monthly_accounting']);
         
         // Add basic permissions for staff role
         $staffRole->givePermissionTo(['read product', 'view product']);
         $staffRole->givePermissionTo(['read order', 'view order']);
         $staffRole->givePermissionTo(['read customers', 'view customer']);
         $staffRole->givePermissionTo(['read supplier', 'view supplier']);
         $staffRole->givePermissionTo(['read category', 'view category']);
       
//php artisan cache:forget spatie.permission.cache 


        // Let's Create User and assign Role to it.

            // Create Super Admin user
            $superAdminUser = User::firstOrCreate(
                ['email' => 'mogahidgaffar@gmail.com'],
                [
                    'name' => 'Mogahid Gaffar',
                    'email' => 'mogahidgaffar@gmail.com',
                    'password' => Hash::make('12345678'),
                    'is_active' => true,
                ]
            );
            if (!$superAdminUser->hasRole('superadmin')) {
                $superAdminUser->assignRole('superadmin');
            }

            // Create Admin user
            $adminUser = User::firstOrCreate(
                ['email' => 'admin@gmail.com'],
                [
                    'name' => 'Admin',
                    'email' => 'admin@gmail.com',
                    'password' => Hash::make('12345678'),
                    'is_active' => true,
                ]
            );
            if (!$adminUser->hasRole('admin')) {
                $adminUser->assignRole('admin');
            }

            // Create Staff user
            $staffUser = User::firstOrCreate(
                ['email' => 'staff@gmail.com'],
                [
                    'name' => 'Staff',
                    'email' => 'staff@gmail.com',
                    'password' => Hash::make('12345678'),
                    'is_active' => true,
                ]
            );
            if (!$staffUser->hasRole('staff')) {
                $staffUser->assignRole('staff');
            }

            // Create additional admin user for migration management
            $migrationAdminUser = User::firstOrCreate(
                ['email' => 'migration@admin.com'],
                [
                    'name' => 'Migration Admin',
                    'email' => 'migration@admin.com',
                    'password' => Hash::make('12345678'),
                    'is_active' => true,
                ]
            );
            if (!$migrationAdminUser->hasRole('admin')) {
                $migrationAdminUser->assignRole('admin');
            }
    }
}