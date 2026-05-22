<?php

namespace Tests\Feature\Platform;

use App\Models\Plan;
use App\Models\Tenant;
use App\Services\Platform\SubscriptionStatsService;
use Database\Seeders\PlanSeeder;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

#[Group('platform')]
class SubscriptionStatsTest extends TestCase
{
    use AdministrationFixtures;

    public function test_subscription_badge_expiring_soon(): void
    {
        $this->seed(PlanSeeder::class);
        $plan = Plan::first();

        $tenant = Tenant::create([
            'name' => 'Expiring Co',
            'slug' => 'expiring-'.uniqid(),
            'status' => 'active',
            'is_active' => true,
            'plan_id' => $plan->id,
            'subscription_ends_at' => now()->addDays(10),
            'payment_status' => 'paid',
        ]);

        $this->assertSame('expiring_soon', $tenant->subscription_badge);
    }

    public function test_kpis_include_tenant_counts(): void
    {
        $this->seed(PlanSeeder::class);
        Tenant::create([
            'name' => 'KPI Tenant',
            'slug' => 'kpi-'.uniqid(),
            'status' => 'active',
            'is_active' => true,
            'plan_id' => Plan::first()->id,
        ]);

        $kpis = app(SubscriptionStatsService::class)->kpis();

        $this->assertGreaterThanOrEqual(1, $kpis['total_tenants']);
    }

    public function test_monthly_revenue_normalizes_yearly_plans_and_excludes_expired_subscriptions(): void
    {
        $monthlyPlan = Plan::create([
            'name' => 'Monthly MRR',
            'slug' => 'monthly-mrr-'.uniqid(),
            'price' => 100,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $yearlyPlan = Plan::create([
            'name' => 'Yearly MRR',
            'slug' => 'yearly-mrr-'.uniqid(),
            'price' => 1200,
            'currency' => 'USD',
            'billing_cycle' => 'yearly',
            'is_active' => true,
        ]);

        Tenant::create([
            'name' => 'Monthly Active',
            'slug' => 'monthly-active-'.uniqid(),
            'status' => 'active',
            'is_active' => true,
            'plan_id' => $monthlyPlan->id,
        ]);

        Tenant::create([
            'name' => 'Yearly Active',
            'slug' => 'yearly-active-'.uniqid(),
            'status' => 'active',
            'is_active' => true,
            'plan_id' => $yearlyPlan->id,
        ]);

        Tenant::create([
            'name' => 'Expired Active Status',
            'slug' => 'expired-active-'.uniqid(),
            'status' => 'active',
            'is_active' => true,
            'plan_id' => $monthlyPlan->id,
            'subscription_ends_at' => now()->subDay(),
        ]);

        $kpis = app(SubscriptionStatsService::class)->kpis();

        $this->assertSame(2, $kpis['active_subscriptions']);
        $this->assertEqualsWithDelta(200.0, $kpis['monthly_revenue'], 0.01);
    }
}
