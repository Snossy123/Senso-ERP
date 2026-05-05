<?php

namespace Tests\Feature\Administration;

use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

#[Group('administration')]
class TenantManagementTest extends TestCase
{
    use AdministrationFixtures;

    public function test_company_user_gets_403_on_tenants_index(): void
    {
        $this->seedRoleTemplates();
        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'tm-company-'.str_replace('.', '', uniqid('', true)),
        ]);
        $companyUser = $this->makeTenantAdmin($tenant);

        $this->actingAs($companyUser)
            ->get(route('tenants.index'))
            ->assertForbidden();
    }

    public function test_platform_operator_can_open_tenants_index(): void
    {
        $this->seedRoleTemplates();
        $platform = $this->makePlatformOperator();

        $this->actingAs($platform)
            ->get(route('tenants.index'))
            ->assertOk();
    }

    public function test_platform_operator_can_open_create_form(): void
    {
        $this->seedRoleTemplates();
        $platform = $this->makePlatformOperator();

        $this->actingAs($platform)
            ->get(route('tenants.create'))
            ->assertOk();
    }

    public function test_platform_operator_can_store_tenant_without_support_user(): void
    {
        $this->withoutCsrf();
        $this->seedRoleTemplates();
        $platform = $this->makePlatformOperator();

        $name = 'HTTP Tenant '.uniqid();

        $response = $this->actingAs($platform)->post(route('tenants.store'), [
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

        $response = $this->actingAs($companyUser)->post(route('tenants.store'), [
            'name' => 'Blocked',
            'language' => 'en',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'create_support_user' => '0',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('tenants', ['name' => 'Blocked']);
    }
}
