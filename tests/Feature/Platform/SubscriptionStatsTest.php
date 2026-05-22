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
}
