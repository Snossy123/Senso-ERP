<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable, BelongsToTenant;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'avatar', 'is_active', 'tenant_id',
        'role_id', 'branch_id', 'created_by',
        'last_login_at', 'last_login_ip',
        'failed_login_attempts', 'locked_until',
        'password_changed_at', 'must_change_password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'password_changed_at' => 'datetime',
    ];

    protected $appends = ['permissions'];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdUsers(): HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('granted')
            ->withTimestamps();
    }

    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
        $this->attributes['password_changed_at'] = now();
    }

    public function isAdmin(): bool
    {
        return $this->role?->slug === 'admin' || $this->attributes['role'] === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role?->slug === 'manager' || $this->attributes['role'] === 'manager';
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function isPasswordExpired(int $days = 90): bool
    {
        if (!$this->password_changed_at) {
            return true;
        }
        return $this->password_changed_at->addDays($days)->isPast();
    }

    public function mustChangePassword(): bool
    {
        return $this->must_change_password || $this->isPasswordExpired();
    }

    public function incrementFailedAttempts(): void
    {
        $this->increment('failed_login_attempts');

        if ($this->failed_login_attempts >= 5) {
            $this->lockAccount(30);
        }
    }

    public function resetFailedAttempts(): void
    {
        $this->update(['failed_login_attempts' => 0, 'locked_until' => null]);
    }

    public function lockAccount(int $minutes = 30): void
    {
        $this->update(['locked_until' => now()->addMinutes($minutes)]);
    }

    public function unlockAccount(): void
    {
        $this->update(['locked_until' => null, 'failed_login_attempts' => 0]);
    }

    public function recordLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'failed_login_attempts' => 0,
        ]);
    }

    public function hasPermission(string $permission, bool $checkUserOverride = true): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($checkUserOverride) {
            $userPermission = $this->permissions()->where('slug', $permission)->first();
            if ($userPermission) {
                return $userPermission->pivot->granted;
            }
        }

        return $this->role?->hasPermission($permission) ?? false;
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    public function getAllPermissions(): array
    {
        $rolePermissions = $this->role?->permissions->pluck('slug')->toArray() ?? [];
        $userPermissions = $this->permissions->pluck('slug')->toArray();
        return array_unique(array_merge($rolePermissions, $userPermissions));
    }

    public function getPermissionsAttribute(): array
    {
        return $this->getAllPermissions();
    }

    public function grantPermission(string|array $permissions): void
    {
        $permissions = Permission::whereIn('slug', is_array($permissions) ? $permissions : [$permissions])->pluck('id');
        $this->permissions()->syncWithoutDetaching($permissions->mapWithKeys(fn($id) => [$id => ['granted' => true]])->toArray());
    }

    public function revokePermission(string|array $permissions): void
    {
        $permissions = Permission::whereIn('slug', is_array($permissions) ? $permissions : [$permissions])->pluck('id');
        $this->permissions()->detach($permissions);
    }

    public function denyPermission(string|array $permissions): void
    {
        $permissions = Permission::whereIn('slug', is_array($permissions) ? $permissions : [$permissions])->pluck('id');
        $this->permissions()->syncWithoutDetaching($permissions->mapWithKeys(fn($id) => [$id => ['granted' => false]])->toArray());
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLocked($query)
    {
        return $query->whereNotNull('locked_until')->where('locked_until', '>', now());
    }
}
