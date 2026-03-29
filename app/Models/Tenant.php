<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'domain',
        'database',
        'settings',
        'is_active',
        'trial_ends_at',
        'subscription_ends_at',
        'plan_id',
        'status',
        'subscription_start_at',
        'price',
        'billing_cycle',
        'next_billing_at',
        'payment_status',
        'currency',
        'language',
        'timezone',
        'tax_settings',
        'notes',
        'suspended_at',
        'suspension_reason',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'subscription_start_at' => 'datetime',
        'price' => 'decimal:2',
        'tax_settings' => 'array',
        'suspended_at' => 'datetime',
        'next_billing_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function usageTrackings(): HasMany
    {
        return $this->hasMany(UsageTracking::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->is_active;
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->subscription_ends_at && $this->subscription_ends_at->isPast());
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended' || 
               ($this->suspended_at !== null && $this->suspended_at->isPast() === false);
    }

    public function isSubscriptionActive(): bool
    {
        return !$this->subscription_ends_at || $this->subscription_ends_at->isFuture();
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'trial' => 'Trial',
            'active' => 'Active',
            'expired' => 'Expired',
            'suspended' => 'Suspended',
            default => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'trial' => 'warning',
            'active' => 'success',
            'expired' => 'danger',
            'suspended' => 'secondary',
            default => 'secondary',
        };
    }

    public function getUsage(string $resource): ?UsageTracking
    {
        return $this->usageTrackings()->where('resource', $resource)->first();
    }

    public function getUsersUsage(): ?UsageTracking
    {
        return $this->getUsage('users');
    }

    public function getProductsUsage(): ?UsageTracking
    {
        return $this->getUsage('products');
    }

    public function getOrdersUsage(): ?UsageTracking
    {
        return $this->getUsage('orders');
    }

    public function hasFeature(string $feature): bool
    {
        return $this->plan && $this->plan->hasFeature($feature);
    }

    public function canAddUser(): bool
    {
        $usage = $this->getUsersUsage();
        if (!$usage) {
            return true;
        }
        return !$usage->isAtLimit();
    }

    public function canAddProduct(): bool
    {
        $usage = $this->getProductsUsage();
        if (!$usage) {
            return true;
        }
        return !$usage->isAtLimit();
    }

    public function suspend(string $reason = null): void
    {
        $this->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $reason,
            'is_active' => false,
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspension_reason' => null,
            'is_active' => true,
        ]);
    }

    public function startTrial(int $days = 14): void
    {
        $this->update([
            'status' => 'trial',
            'trial_ends_at' => now()->addDays($days),
        ]);
    }

    public function upgradePlan(Plan $plan): void
    {
        $this->update([
            'plan_id' => $plan->id,
            'price' => $plan->price,
            'billing_cycle' => $plan->billing_cycle,
            'status' => 'active',
            'subscription_start_at' => now(),
            'subscription_ends_at' => $plan->billing_cycle === 'yearly' 
                ? now()->addYear() 
                : now()->addMonth(),
            'next_billing_at' => $plan->billing_cycle === 'yearly'
                ? now()->addYear()
                : now()->addMonth(),
            'payment_status' => 'pending',
        ]);

        foreach (['users', 'products', 'orders'] as $resource) {
            $this->usageTrackings()->updateOrCreate(
                ['resource' => $resource],
                [
                    'capacity_limit' => match($resource) {
                        'users' => $plan->max_users,
                        'products' => $plan->max_products,
                        'orders' => $plan->max_orders_per_month,
                    }
                ]
            );
        }
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->update(['settings' => $settings]);
    }
}
