<?php

namespace App\Models;

use App\Scopes\TenantScope;
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

    public function platformInvoices(): HasMany
    {
        return $this->hasMany(PlatformInvoice::class);
    }

    public function getSubscriptionBadgeAttribute(): string
    {
        if ($this->payment_status === 'overdue' || $this->isExpired()) {
            return 'overdue';
        }

        if ($this->subscription_ends_at && $this->subscription_ends_at->isFuture()
            && $this->subscription_ends_at->lte(now()->addDays(30))) {
            return 'expiring_soon';
        }

        if ($this->status === 'active' || $this->isOnTrial()) {
            return 'active';
        }

        return $this->status;
    }

    public function usageTrackings(): HasMany
    {
        return $this->hasMany(UsageTracking::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class)->withoutGlobalScope(TenantScope::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->withoutGlobalScope(TenantScope::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->withoutGlobalScope(TenantScope::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class)->withoutGlobalScope(TenantScope::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class)->withoutGlobalScope(TenantScope::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class)->withoutGlobalScope(TenantScope::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class)->withoutGlobalScope(TenantScope::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->withoutGlobalScope(TenantScope::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->withoutGlobalScope(TenantScope::class);
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->is_active;
    }

    /**
     * Whether ERP login and tenant context should work (trial or active, not suspended/expired).
     */
    public function allowsApplicationAccess(): bool
    {
        if (! $this->is_active || $this->isSuspended()) {
            return false;
        }

        if ($this->status === 'expired') {
            return false;
        }

        if ($this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isPast()) {
            return false;
        }

        return in_array($this->status, ['active', 'trial'], true);
    }

    /**
     * Tenants that can sign in and use the ERP (mirrors allowsApplicationAccess()).
     */
    public function scopeWithApplicationAccess($query)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('status', '!=', 'suspended')
                    ->where(function ($q2) {
                        $q2->whereNull('suspended_at')
                            ->orWhere('suspended_at', '<=', now());
                    });
            })
            ->where('status', '!=', 'expired')
            ->where(function ($q) {
                $q->where('status', '!=', 'trial')
                    ->orWhereNull('trial_ends_at')
                    ->orWhere('trial_ends_at', '>', now());
            })
            ->whereIn('status', ['active', 'trial']);
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
        return ! $this->subscription_ends_at || $this->subscription_ends_at->isFuture();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'trial' => 'Trial',
            'active' => 'Active',
            'expired' => 'Expired',
            'suspended' => 'Suspended',
            default => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
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
        if (! $usage) {
            return true;
        }

        return ! $usage->isAtLimit();
    }

    public function canAddProduct(): bool
    {
        $usage = $this->getProductsUsage();
        if (! $usage) {
            return true;
        }

        return ! $usage->isAtLimit();
    }

    public function suspend(?string $reason = null): void
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
        $isSamePlan = (int) $this->plan_id === (int) $plan->id;
        $preservePaidState = $isSamePlan && $this->payment_status === 'paid';

        $update = [
            'plan_id' => $plan->id,
            'price' => $plan->price,
            'billing_cycle' => $plan->billing_cycle,
            'status' => 'active',
        ];

        if (! $isSamePlan) {
            $endsAt = $plan->billing_cycle === 'yearly'
                ? now()->addYear()
                : now()->addMonth();

            $update['subscription_start_at'] = now();
            $update['subscription_ends_at'] = $endsAt;
            $update['next_billing_at'] = $endsAt;

            if ($plan->price > 0) {
                $update['payment_status'] = 'pending';
            }
        } elseif (! $preservePaidState && $plan->price > 0) {
            $update['payment_status'] = 'pending';
        }

        $this->update($update);

        foreach (['users', 'products', 'orders'] as $resource) {
            $this->usageTrackings()->updateOrCreate(
                ['resource' => $resource],
                [
                    'capacity_limit' => match ($resource) {
                        'users' => $plan->max_users,
                        'products' => $plan->max_products,
                        'orders' => $plan->max_orders_per_month,
                    },
                ]
            );
        }

        app(\App\Services\Platform\PlatformInvoiceService::class)
            ->createForPlanUpgradeIfNeeded($this->fresh(), $plan);
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
