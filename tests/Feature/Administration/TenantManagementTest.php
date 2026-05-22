<?php

namespace Tests\Feature\Administration;

use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

#[Group('administration')]
class TenantManagementTest extends TestCase
{
    use AdministrationFixtures;

    public function test_company_user_gets_403_on_platform_tenants_index(): void
    {
        $this->seedRoleTemplates();
        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'tm-company-'.str_replace('.', '', uniqid('', true)),
        ]);
        $companyUser = $this->makeTenantAdmin($tenant);

        $this->actingAs($companyUser)
            ->get(route('platform.tenants.index'))
            ->assertForbidden();
    }

    public function test_platform_operator_can_open_tenants_index(): void
    {
        $this->seedRoleTemplates();
        $platform = $this->makePlatformOperator();

        $this->actingAs($platform)
            ->get(route('platform.tenants.index'))
            ->assertOk();
    }

    public function test_platform_operator_can_open_create_form(): void
    {
        $this->seedRoleTemplates();
        $platform = $this->makePlatformOperator();

        $this->actingAs($platform)
            ->get(route('platform.tenants.create'))
            ->assertOk();
    }

    public function test_platform_operator_can_store_tenant_without_support_user(): void
    {
        $this->withoutCsrf();
        $this->seedRoleTemplates();
        $platform = $this->makePlatformOperator();

        $name = 'HTTP Tenant '.uniqid();

        $response = $this->actingAs($platform)->post(route('platform.tenants.store'), [
            'name' => $name,
            'language' => 'en',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'create_support_user' => '0',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenants', [
            'name' => $name,
        ]);
    }

    public function test_company_user_cannot_store_tenant(): void
    {
        $this->withoutCsrf();
        $this->seedRoleTemplates();
        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'tm-block-'.str_replace('.', '', uniqid('', true)),
        ]);
        $companyUser = $this->makeTenantAdmin($tenant);

        $response = $this->actingAs($companyUser)->post(route('platform.tenants.store'), [
            'name' => 'Blocked',
            'language' => 'en',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'create_support_user' => '0',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('tenants', ['name' => 'Blocked']);
    }

    public function test_platform_operator_is_redirected_from_erp_dashboard(): void
    {
        $this->seedRoleTemplates();
        $platform = $this->makePlatformOperator();

        $this->actingAs($platform)
            ->get(route('dashboard'))
            ->assertRedirect(route('platform.dashboard'));
    }

    public function test_platform_operator_is_redirected_from_inventory(): void
    {
        $this->seedRoleTemplates();
        $platform = $this->makePlatformOperator();

        $this->actingAs($platform)
            ->get(route('inventory.products.index'))
            ->assertRedirect(route('platform.dashboard'));
    }

    public function test_legacy_tenants_url_redirects_to_platform(): void
    {
        $this->seedRoleTemplates();
        $platform = $this->makePlatformOperator();

        $this->actingAs($platform)
            ->get('/tenants')
            ->assertRedirect('/platform/tenants');
    }

    public function test_platform_operator_login_redirects_to_platform_dashboard(): void
    {
        $this->withoutCsrf();
        $this->seedRoleTemplates();
        $platform = $this->makePlatformOperator(['password' => 'password']);

        $this->post(route('login'), [
            'email' => $platform->email,
            'password' => 'password',
        ])->assertRedirect(route('platform.dashboard'));
    }

    public function test_impersonation_and_stop_returns_to_platform(): void
    {
        $this->withoutCsrf();
        $this->seedRoleTemplates();
        $platform = $this->makePlatformOperator();
        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'tm-imp-'.str_replace('.', '', uniqid('', true)),
        ]);
        $tenantUser = $this->makeTenantAdmin($tenant);

        $this->actingAs($platform)
            ->post(route('platform.tenants.login-as', $tenant), ['user_id' => $tenantUser->id])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($tenantUser);
        $this->assertEquals($platform->id, session('platform_operator_id'));

        $this->post(route('platform.impersonation.stop'))
            ->assertRedirect(route('platform.dashboard'));

        $this->assertAuthenticatedAs($platform);
        $this->assertNull(session('platform_operator_id'));
    }
}
