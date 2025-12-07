<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Artisan;

class PermissionSeederController extends Controller
{
    /**
     * Add category permissions via API
     */
    public function addCategoryPermissions()
    {
        try {
            // Create Category permissions
            $permissions = [
                'create category',
                'read category',
                'update category',
                'delete category',
                'view category'
            ];

            $createdPermissions = [];
            foreach ($permissions as $permissionName) {
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                $createdPermissions[] = $permission->name;
            }

            // Get roles
            $superAdminRole = Role::where('name', 'superadmin')->first();
            $adminRole = Role::where('name', 'admin')->first();
            $staffRole = Role::where('name', 'staff')->first();

            $rolesUpdated = [];

            // Assign permissions to superadmin
            if ($superAdminRole) {
                $superAdminRole->givePermissionTo($permissions);
                $rolesUpdated[] = 'superadmin';
            }

            // Assign permissions to admin
            if ($adminRole) {
                $adminRole->givePermissionTo($permissions);
                $rolesUpdated[] = 'admin';
            }

            // Assign read and view permissions to staff
            if ($staffRole) {
                $staffRole->givePermissionTo(['read category', 'view category']);
                $rolesUpdated[] = 'staff';
            }

            // Clear permission cache
            Artisan::call('cache:forget', ['key' => 'spatie.permission.cache']);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة صلاحيات التصنيفات بنجاح',
                'data' => [
                    'permissions_created' => $createdPermissions,
                    'roles_updated' => $rolesUpdated,
                    'total_permissions' => count($createdPermissions)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الصلاحيات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run CategoryPermissionSeeder via API
     */
    public function runCategoryPermissionSeeder()
    {
        try {
            Artisan::call('db:seed', ['--class' => 'CategoryPermissionSeeder']);

            return response()->json([
                'success' => true,
                'message' => 'تم تشغيل CategoryPermissionSeeder بنجاح',
                'output' => Artisan::output()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تشغيل Seeder',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

