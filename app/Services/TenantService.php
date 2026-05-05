<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\UsageTracking;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantService
{
    public function __construct(
        protected RoleProvisioningService $roleProvisioning,
        protected BranchProvisioningService $branchProvisioning
    ) {}

    /**
     * @return array{tenant: Tenant, support_password: ?string}
     */
    public function createTenant(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $settings = [];
            if (! empty($data['settings']) && is_array($data['settings'])) {
                $settings = $data['settings'];
            }

            $tenant = Tenant::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? str($data['name'])->slug(),
                'domain' => $data['domain'] ?? null,
                'settings' => $settings,
                'status' => 'trial',
                'is_active' => true,
                'trial_ends_at' => now()->addDays((int) ($data['trial_days'] ?? 14)),
                'currency' => $data['currency'] ?? 'USD',
                'language' => $data['language'] ?? 'en',
                'timezone' => $data['timezone'] ?? 'UTC',
            ]);

            if (! empty($data['plan_id'])) {
                $plan = Plan::find($data['plan_id']);
                if ($plan) {
                    $this->assignPlan($tenant, $plan);
                }
            }

            $this->initializeUsageTracking($tenant);

            $this->roleProvisioning->cloneDefaultRolesForTenant($tenant);

            $this->branchProvisioning->ensureDefaultBranchesForTenant($tenant);

            $supportPassword = null;
            $createSupport = filter_var($data['create_support_user'] ?? true, FILTER_VALIDATE_BOOLEAN);
            if ($createSupport) {
                $supportPassword = $this->provisionTenantAdministrator($tenant, $data);
            }

            $this->syncUsage($tenant);

            return [
                'tenant' => $tenant,
                'support_password' => $supportPassword,
            ];
        });
    }

    /**
     * Creates the primary tenant administrator (admin role) with explicit tenant_id.
     */
    protected function provisionTenantAdministrator(Tenant $tenant, array $data): string
    {
        $role = Role::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'admin')
            ->first();

        if (! $role) {
            throw new \RuntimeException('Tenant administrator role is missing after role provisioning.');
        }

        $plainPassword = Str::password(16);

        $email = $data['support_email'] ?? null;
        if (! $email) {
            $domain = config('tenants.support_email_domain', 'tenants.invalid');
            $email = sprintf('support.t%d@%s', $tenant->id, $domain);
        }

        $name = $data['support_name'] ?? ($tenant->name.' Admin');

        User::withoutGlobalScopes()->create([
            'name' => $name,
            'email' => $email,
            'password' => $plainPassword,
            'tenant_id' => $tenant->id,
            'role_id' => $role->id,
            'is_active' => true,
            'must_change_password' => true,
            'created_by' => Auth::id(),
        ]);

        return $plainPassword;
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
        $userCount = $tenant->users()->count();
        $productCount = $tenant->products()->count();
        $orderCount = $tenant->orders()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $this->syncUsageForResource($tenant, 'users', $userCount);
        $this->syncUsageForResource($tenant, 'products', $productCount);
        $this->syncUsageForResource($tenant, 'orders', $orderCount);
    }

    protected function syncUsageForResource(Tenant $tenant, string $resource, int $count): void
    {
        $usage = $tenant->getUsage($resource);

        if ($usage) {
            $usage->update(['current_usage' => $count]);
        }
    }

    public function suspendTenant(Tenant $tenant, ?string $reason = null): void
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

        if (! $usage) {
            return true;
        }

        return ! $usage->isAtLimit();
    }

    public function getDaysUntilTrialEnds(Tenant $tenant): ?int
    {
        if (! $tenant->trial_ends_at) {
            return null;
        }

        return now()->diffInDays($tenant->trial_ends_at, false);
    }

    public function getDaysUntilSubscriptionEnds(Tenant $tenant): ?int
    {
        if (! $tenant->subscription_ends_at) {
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
