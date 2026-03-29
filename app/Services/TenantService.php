<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\UsageTracking;
use Illuminate\Support\Facades\DB;

class TenantService
{
    public function createTenant(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? str($data['name'])->slug(),
                'domain' => $data['domain'] ?? null,
                'status' => 'trial',
                'is_active' => true,
                'trial_ends_at' => now()->addDays($data['trial_days'] ?? 14),
                'currency' => $data['currency'] ?? 'USD',
                'language' => $data['language'] ?? 'en',
                'timezone' => $data['timezone'] ?? 'UTC',
            ]);

            if (!empty($data['plan_id'])) {
                $plan = Plan::find($data['plan_id']);
                if ($plan) {
                    $this->assignPlan($tenant, $plan);
                }
            }

            $this->initializeUsageTracking($tenant);

            return $tenant;
        });
    }

    public function assignPlan(Tenant $tenant, Plan $plan): void
    {
        DB::transaction(function () use ($tenant, $plan) {
            $tenant->upgradePlan($plan);
        });
    }

    public function initializeUsageTracking(Tenant $tenant): void
    {
        $plan = $tenant->plan;
        $limits = [
            'users' => $plan?->max_users ?? 2,
            'products' => $plan?->max_products ?? 50,
            'orders' => $plan?->max_orders_per_month ?? 30,
        ];

        foreach ($limits as $resource => $limit) {
            UsageTracking::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'resource' => $resource,
                ],
                [
                    'current_usage' => 0,
                    'capacity_limit' => $limit,
                    'reset_at' => now()->addMonth(),
                ]
            );
        }
    }

    public function updateUsage(Tenant $tenant, string $resource, int $count = 1): void
    {
        $usage = $tenant->getUsage($resource);
        
        if ($usage) {
            $usage->increment('current_usage', $count);
        }
    }

    public function decrementUsage(Tenant $tenant, string $resource, int $count = 1): void
    {
        $usage = $tenant->getUsage($resource);
        
        if ($usage) {
            $usage->decrement('current_usage', $count);
        }
    }

    public function syncUsage(Tenant $tenant): void
    {
        $tenant->load(['users', 'products', 'orders']);

        $this->syncUsageForResource($tenant, 'users', $tenant->users()->count());
        $this->syncUsageForResource($tenant, 'products', $tenant->products()->count());
        $this->syncUsageForResource($tenant, 'orders', $tenant->orders()->whereMonth('created_at', now()->month)->count());
    }

    protected function syncUsageForResource(Tenant $tenant, string $resource, int $count): void
    {
        $usage = $tenant->getUsage($resource);
        
        if ($usage) {
            $usage->update(['current_usage' => $count]);
        }
    }

    public function suspendTenant(Tenant $tenant, string $reason = null): void
    {
        $tenant->suspend($reason);
        $tenant->users()->update(['is_active' => false]);
    }

    public function activateTenant(Tenant $tenant): void
    {
        $tenant->activate();
        $tenant->users()->update(['is_active' => true]);
    }

    public function checkLimits(Tenant $tenant): array
    {
        $results = [];
        
        foreach (['users', 'products', 'orders'] as $resource) {
            $usage = $tenant->getUsage($resource);
            if ($usage) {
                $results[$resource] = [
                    'current' => $usage->current_usage,
                    'limit' => $usage->capacity_limit,
                    'percentage' => $usage->percentage,
                    'remaining' => $usage->remaining,
                    'at_limit' => $usage->isAtLimit(),
                ];
            }
        }

        return $results;
    }

    public function isWithinLimits(Tenant $tenant, string $resource): bool
    {
        $usage = $tenant->getUsage($resource);
        
        if (!$usage) {
            return true;
        }

        return !$usage->isAtLimit();
    }

    public function getDaysUntilTrialEnds(Tenant $tenant): ?int
    {
        if (!$tenant->trial_ends_at) {
            return null;
        }

        return now()->diffInDays($tenant->trial_ends_at, false);
    }

    public function getDaysUntilSubscriptionEnds(Tenant $tenant): ?int
    {
        if (!$tenant->subscription_ends_at) {
            return null;
        }

        return now()->diffInDays($tenant->subscription_ends_at, false);
    }

    public function processExpiration(Tenant $tenant): void
    {
        if ($tenant->subscription_ends_at && $tenant->subscription_ends_at->isPast()) {
            $tenant->update(['status' => 'expired']);
        }
    }

    public function processTrialExpiration(Tenant $tenant): void
    {
        if ($tenant->isOnTrial() && $tenant->trial_ends_at && $tenant->trial_ends_at->isPast()) {
            $freePlan = Plan::where('slug', 'free')->first();
            if ($freePlan) {
                $this->assignPlan($tenant, $freePlan);
            } else {
                $tenant->update(['status' => 'expired', 'is_active' => false]);
            }
        }
    }
}
