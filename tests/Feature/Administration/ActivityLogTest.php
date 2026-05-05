<?php

namespace Tests\Feature\Administration;

use App\Models\Activity;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

#[Group('administration')]
class ActivityLogTest extends TestCase
{
    use AdministrationFixtures;

    public function test_tenant_admin_can_view_activity_index(): void
    {
        $this->seedRoleTemplates();
        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'act-idx-'.str_replace('.', '', uniqid('', true)),
        ]);
        $admin = $this->makeTenantAdmin($tenant);

        $this->actingAs($admin)
            ->get(route('admin.activity.index'))
            ->assertOk();
    }

    public function test_tenant_admin_does_not_see_other_tenant_activity_descriptions(): void
    {
        $this->seedRoleTemplates();
        $tenantA = $this->createTenantWithClonedRoles([
            'slug' => 'act-a-'.str_replace('.', '', uniqid('', true)),
        ]);
        $tenantB = $this->createTenantWithClonedRoles([
            'slug' => 'act-b-'.str_replace('.', '', uniqid('', true)),
        ]);

        $markerA = 'FIXTURE_ACTIVITY_A_'.uniqid();
        $markerB = 'FIXTURE_ACTIVITY_B_'.uniqid();

        Activity::create([
            'tenant_id' => $tenantA->id,
            'user_id' => null,
            'type' => 'fixture',
            'action' => 'seed',
            'description' => $markerA,
            'severity' => 'info',
        ]);
        Activity::create([
            'tenant_id' => $tenantB->id,
            'user_id' => null,
            'type' => 'fixture',
            'action' => 'seed',
            'description' => $markerB,
            'severity' => 'info',
        ]);

        $adminA = $this->makeTenantAdmin($tenantA);

        $response = $this->actingAs($adminA)
            ->get(route('admin.activity.index'));

        $response->assertOk();
        $response->assertSee($markerA, false);
        $response->assertDontSee($markerB, false);
    }

    public function test_tenant_admin_gets_403_for_activity_in_another_tenant(): void
    {
        $this->seedRoleTemplates();
        $tenantA = $this->createTenantWithClonedRoles([
            'slug' => 'act-403a-'.str_replace('.', '', uniqid('', true)),
        ]);
        $tenantB = $this->createTenantWithClonedRoles([
            'slug' => 'act-403b-'.str_replace('.', '', uniqid('', true)),
        ]);

        $otherActivity = Activity::create([
            'tenant_id' => $tenantB->id,
            'user_id' => null,
            'type' => 'fixture',
            'action' => 'seed',
            'description' => 'other tenant row',
            'severity' => 'info',
        ]);

        $adminA = $this->makeTenantAdmin($tenantA);

        $this->actingAs($adminA)
            ->get(route('admin.activity.show', $otherActivity))
            ->assertForbidden();
    }

    public function test_platform_operator_can_view_activity_index(): void
    {
        $this->seedRoleTemplates();
        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'act-plat-'.str_replace('.', '', uniqid('', true)),
        ]);

        Activity::create([
            'tenant_id' => $tenant->id,
            'user_id' => null,
            'type' => 'fixture',
            'action' => 'seed',
            'description' => 'PLATFORM_INDEX_MARKER_'.uniqid(),
            'severity' => 'info',
        ]);

        $platform = $this->makePlatformOperator();

        $this->actingAs($platform)
            ->get(route('admin.activity.index'))
            ->assertOk();
    }
}
