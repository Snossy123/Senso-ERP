<?php

namespace Tests\Feature\Platform;

use App\Models\Plan;
use App\Models\PlatformModule;
use Database\Seeders\PlanSeeder;
use Database\Seeders\PlatformModuleSeeder;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

#[Group('platform')]
class PlanManagementTest extends TestCase
{
    use AdministrationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoleTemplates();
        $this->seed(PlanSeeder::class);
        $this->seed(PlatformModuleSeeder::class);
    }

    public function test_platform_operator_can_view_subscriptions_hub(): void
    {
        $platform = $this->makePlatformOperator();

        $this->actingAs($platform)
            ->get(route('platform.plans.index'))
            ->assertOk()
            ->assertSee(__('platform.subscriptions.title'));
    }

    public function test_platform_operator_can_create_plan_with_modules(): void
    {
        $this->withoutCsrf();
        $platform = $this->makePlatformOperator();

        $response = $this->actingAs($platform)->post(route('platform.plans.store'), [
            'name' => 'Business Test',
            'price' => 79.99,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'max_users' => 10,
            'max_products' => 500,
            'max_orders_per_month' => 200,
            'is_active' => 1,
            'modules' => [
                'pos' => [
                    'enabled' => 1,
                    'limits' => json_encode(['users' => 5, 'devices' => 2]),
                ],
            ],
        ]);

        $response->assertRedirect(route('platform.plans.index'));

        $plan = Plan::where('name', 'Business Test')->first();
        $this->assertNotNull($plan);
        $this->assertTrue($plan->planModules()->where('module_key', 'pos')->where('enabled', true)->exists());
        $this->assertContains('pos', $plan->features ?? []);
    }

    public function test_company_user_cannot_access_plan_create(): void
    {
        $tenant = $this->createTenantWithClonedRoles(['slug' => 'plan-block-'.uniqid()]);
        $user = $this->makeTenantAdmin($tenant);

        $this->actingAs($user)
            ->get(route('platform.plans.create'))
            ->assertForbidden();
    }
}
