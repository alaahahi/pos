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
        Permission::create(['name' => 'create roles']);
        Permission::create(['name' => 'read roles']);
        Permission::create(['name' => 'update roles']);
        Permission::create(['name' => 'delete roles']);
        Permission::create(['name' => 'view roles']);

        Permission::create(['name' => 'create permissions']);
        Permission::create(['name' => 'read permissions']);
        Permission::create(['name' => 'update permissions']);
        Permission::create(['name' => 'delete permissions']);
        Permission::create(['name' => 'view permissions']);

        Permission::create(['name' => 'create users']);
        Permission::create(['name' => 'read users']);
        Permission::create(['name' => 'update users']);
        Permission::create(['name' => 'delete users']);
        Permission::create(['name' => 'view users']);

        Permission::create(['name' => 'create products']);
        Permission::create(['name' => 'read products']);
        Permission::create(['name' => 'update products']);
        Permission::create(['name' => 'delete products']);
        Permission::create(['name' => 'view products']);

        Permission::create(['name' => 'create logs']);
        Permission::create(['name' => 'read logs']);
        Permission::create(['name' => 'update logs']);
        Permission::create(['name' => 'delete logs']);
        Permission::create(['name' => 'view logs']);

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


    // Create Roles 
        $superAdminRole = Role::create(['name' => 'superadmin']); //as super-admin
        $adminRole = Role::create(['name' => 'admin']);
        $staffRole = Role::create(['name' => 'staff']);
        $userRole = Role::create(['name' => 'user']);
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
         
         // Let's give decoration permissions to staff role
         $staffRole->givePermissionTo(['read decoration', 'view decoration', 'create decoration']);
         $staffRole->givePermissionTo(['read payment', 'view payment', 'create payment']);
         $staffRole->givePermissionTo(['read monthly_accounting', 'view monthly_accounting']);
       
//php artisan cache:forget spatie.permission.cache 


        // Let's Create User and assign Role to it.

            // Create Super Admin user
            $superAdminUser = User::firstOrCreate(
                ['email' => 'mogahidgaffar@gmail.com'],
                [
                    'name' => 'Mogahid Gaffar',
                    'email' => 'mogahidgaffar@gmail.com',
                    'password' => Hash::make('12345678'),
                ]
            );
            $superAdminUser->assignRole('superadmin');

            // Create Admin user
            $adminUser = User::firstOrCreate(
                ['email' => 'admin@gmail.com'],
                [
                    'name' => 'Admin',
                    'email' => 'admin@gmail.com',
                    'password' => Hash::make('12345678'),
                ]
            );
            $adminUser->assignRole('admin');

            // Create Staff user
            $staffUser = User::firstOrCreate(
                ['email' => 'staff@gmail.com'],
                [
                    'name' => 'Staff',
                    'email' => 'staff@gmail.com',
                    'password' => Hash::make('12345678'),
                ]
            );
            $staffUser->assignRole('staff');
    }
}