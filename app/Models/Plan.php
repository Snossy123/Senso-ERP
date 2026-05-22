<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_cycle',
        'max_users',
        'max_products',
        'max_orders_per_month',
        'features',
        'sort_order',
        'is_active',
        'is_featured',
        'trial_ends_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function planModules(): HasMany
    {
        return $this->hasMany(PlanModule::class);
    }

    public function addons(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(PlatformAddon::class, 'plan_addon', 'plan_id', 'platform_addon_id');
    }

    public function moduleLimits(string $moduleKey): ?array
    {
        $row = $this->planModules()->where('module_key', $moduleKey)->first();

        return $row?->limits;
    }

    public function enabledPlanModules(): HasMany
    {
        return $this->planModules()->where('enabled', true);
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function isFree(): bool
    {
        return $this->price <= 0;
    }

    public function getFormattedPriceAttribute(): string
    {
        $symbol = match ($this->currency ?? 'USD') {
            'USD' => '$',
            'EUR' => '€',
            'SAR' => '﷼',
            default => $this->currency.' ',
        };

        return $symbol.number_format((float) $this->price, 2);
    }
}
