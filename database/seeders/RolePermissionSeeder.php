<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = Permission::getDefaultPermissions();
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['slug' => $permission['slug']], $permission);
        }

        $allPermissions = Permission::pluck('id', 'slug');

        $roles = [
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Full system access with all permissions',
                'permissions' => $allPermissions->values()->toArray(),
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Manage daily operations, view reports',
                'permissions' => Permission::where('group', '!=', 'roles')->pluck('id')->toArray(),
            ],
            [
                'name' => 'Cashier',
                'slug' => 'cashier',
                'description' => 'Process sales and handle transactions',
                'permissions' => [
                    $allPermissions['dashboard.view'] ?? null,
                    $allPermissions['pos.view'] ?? null,
                    $allPermissions['pos.create'] ?? null,
                    $allPermissions['orders.view'] ?? null,
                    $allPermissions['orders.create'] ?? null,
                    $allPermissions['customers.view'] ?? null,
                    $allPermissions['customers.create'] ?? null,
                ],
            ],
            [
                'name' => 'Inventory Manager',
                'slug' => 'inventory_manager',
                'description' => 'Manage products, stock, and suppliers',
                'permissions' => Permission::whereIn('group', ['products', 'categories', 'warehouses', 'suppliers', 'dashboard'])->pluck('id')->toArray(),
            ],
            [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'View-only access to reports and data',
                'permissions' => Permission::filter(fn($p) => str_ends_with($p['slug'], '.view'))->pluck('id')->toArray(),
            ],
        ];

        foreach ($roles as $roleData) {
            $permissions = array_filter($roleData['permissions']);
            unset($roleData['permissions']);

            $role = Role::firstOrCreate(['slug' => $roleData['slug']], array_merge($roleData, ['is_active' => true]));
            $role->permissions()->sync($permissions);
        }
    }
}
