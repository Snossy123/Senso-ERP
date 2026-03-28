<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name', 'slug', 'group', 'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withPivot('granted')
            ->withTimestamps();
    }

    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public static function getGroups(): array
    {
        return [
            'Dashboard' => 'dashboard',
            'POS' => 'pos',
            'Products' => 'products',
            'Categories' => 'categories',
            'Suppliers' => 'suppliers',
            'Warehouses' => 'warehouses',
            'Orders' => 'orders',
            'Customers' => 'customers',
            'Reports' => 'reports',
            'Users' => 'users',
            'Roles' => 'roles',
            'Settings' => 'settings',
        ];
    }

    public static function getDefaultPermissions(): array
    {
        $groups = [
            'dashboard' => [
                'dashboard.view',
            ],
            'pos' => [
                'pos.view',
                'pos.create',
                'pos.edit',
                'pos.delete',
            ],
            'products' => [
                'products.view',
                'products.create',
                'products.edit',
                'products.delete',
            ],
            'categories' => [
                'categories.view',
                'categories.create',
                'categories.edit',
                'categories.delete',
            ],
            'suppliers' => [
                'suppliers.view',
                'suppliers.create',
                'suppliers.edit',
                'suppliers.delete',
            ],
            'warehouses' => [
                'warehouses.view',
                'warehouses.create',
                'warehouses.edit',
                'warehouses.delete',
            ],
            'orders' => [
                'orders.view',
                'orders.create',
                'orders.edit',
                'orders.delete',
                'orders.process',
            ],
            'customers' => [
                'customers.view',
                'customers.create',
                'customers.edit',
                'customers.delete',
            ],
            'reports' => [
                'reports.view',
                'reports.export',
            ],
            'users' => [
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',
            ],
            'roles' => [
                'roles.view',
                'roles.create',
                'roles.edit',
                'roles.delete',
            ],
            'settings' => [
                'settings.view',
                'settings.edit',
            ],
        ];

        $permissions = [];
        foreach ($groups as $group => $slugs) {
            foreach ($slugs as $slug) {
                $permissions[] = [
                    'name' => ucfirst(str_replace('.', ' ', $slug)),
                    'slug' => $slug,
                    'group' => $group,
                ];
            }
        }

        return $permissions;
    }
}
