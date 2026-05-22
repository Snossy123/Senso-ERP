<?php

namespace App\Services\Platform;

use App\Models\Plan;
use App\Models\PlanModule;
use App\Models\PlatformModule;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SubscriptionStatsService
{
    public function kpis(): array
    {
        $totalTenants = Tenant::count();
        $tenantsThisMonth = Tenant::where('created_at', '>=', now()->startOfMonth())->count();

        $activeSubscriptions = $this->activeSubscriptionQuery()->count();

        $monthlyRevenue = $this->activeSubscriptionQuery()
            ->join('plans', 'tenants.plan_id', '=', 'plans.id')
            ->selectRaw(
                'SUM(CASE COALESCE(tenants.billing_cycle, plans.billing_cycle)
                    WHEN ? THEN COALESCE(tenants.price, plans.price) / 12
                    ELSE COALESCE(tenants.price, plans.price)
                END) as monthly_revenue',
                ['yearly']
            )
            ->value('monthly_revenue');

        $activePlans = Plan::where('is_active', true)->count();
        $totalPlans = Plan::count();

        return [
            'total_tenants' => $totalTenants,
            'tenants_this_month' => $tenantsThisMonth,
            'monthly_revenue' => (float) $monthlyRevenue,
            'active_plans' => $activePlans,
            'total_plans' => $totalPlans,
            'active_subscriptions' => $activeSubscriptions,
            'total_tenants_for_subs' => $totalTenants,
        ];
    }

    public function latestSubscriptions(int $limit = 8): Collection
    {
        return Tenant::with('plan')
            ->whereNotNull('plan_id')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    public function moduleUsageChart(): array
    {
        $modules = PlatformModule::where('is_active', true)->orderBy('sort_order')->get();
        $counts = PlanModule::query()
            ->where('enabled', true)
            ->select('module_key', DB::raw('count(distinct plan_id) as plan_count'))
            ->groupBy('module_key')
            ->pluck('plan_count', 'module_key');

        $tenantCounts = [];
        foreach ($modules as $module) {
            $planIds = PlanModule::where('module_key', $module->key)
                ->where('enabled', true)
                ->pluck('plan_id');

            $tenantCounts[$module->key] = Tenant::whereIn('plan_id', $planIds)
                ->where('status', 'active')
                ->count();
        }

        $labels = $modules->pluck('name')->all();
        $data = $modules->map(fn ($m) => $tenantCounts[$m->key] ?? 0)->all();
        $total = array_sum($data) ?: 1;

        return [
            'labels' => $labels,
            'data' => $data,
            'total' => array_sum($data),
            'percentages' => array_map(fn ($v) => round(($v / $total) * 100, 1), $data),
            'modules' => $modules,
        ];
    }

    protected function activeSubscriptionQuery()
    {
        return Tenant::query()
            ->where('status', 'active')
            ->whereNotNull('plan_id')
            ->where(function ($q) {
                $q->whereNull('subscription_ends_at')
                    ->orWhere('subscription_ends_at', '>', now());
            });
    }
}
