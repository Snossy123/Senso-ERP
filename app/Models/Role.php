<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Role extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name', 'slug', 'description', 'guard_name', 'is_active', 'tenant_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $slug): bool
    {
        return $this->permissions()->where('slug', $slug)->exists();
    }

    public function hasPermissionTo(string|array $permissions, string $operator = 'any'): bool
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        if ($operator === 'all') {
            return $this->permissions()->whereIn('slug', $permissions)->count() === count($permissions);
        }

        return $this->permissions()->whereIn('slug', $permissions)->exists();
    }

    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    public function givePermissionTo(string|array $permissions): void
    {
        $permissions = Permission::whereIn('slug', is_array($permissions) ? $permissions : [$permissions])->pluck('id');
        $this->permissions()->syncWithoutDetaching($permissions);
    }

    public function revokePermissionTo(string|array $permissions): void
    {
        $permissions = Permission::whereIn('slug', is_array($permissions) ? $permissions : [$permissions])->pluck('id');
        $this->permissions()->detach($permissions);
    }

    public static function getDefaultRoles(): array
    {
        return [
            'admin' => [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Full system access',
            ],
            'manager' => [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Manage operations, view reports',
            ],
            'cashier' => [
                'name' => 'Cashier',
                'slug' => 'cashier',
                'description' => 'Process sales and transactions',
            ],
            'inventory' => [
                'name' => 'Inventory Manager',
                'slug' => 'inventory_manager',
                'description' => 'Manage products and stock',
            ],
            'viewer' => [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'View-only access',
            ],
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
