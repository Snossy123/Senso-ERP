<?php

namespace Tests\Unit\Services;

use App\Models\Role;
use App\Services\UserManagementService;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

#[Group('administration')]
class UserManagementServiceRolesTest extends TestCase
{
    use AdministrationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoleTemplates();
    }

    public function test_get_roles_for_tenant_user_returns_only_tenant_scoped_roles(): void
    {
        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'ums-t-'.str_replace('.', '', uniqid('', true)),
        ]);
        $admin = $this->makeTenantAdmin($tenant);

        $this->actingAs($admin);

        $service = app(UserManagementService::class);
        $result = $service->getRoles();

        $tenantRoleIds = Role::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->pluck('id')
            ->all();

        $this->assertNotEmpty($tenantRoleIds);
        foreach ($result['roles'] as $role) {
            $this->assertSame((int) $tenant->id, (int) $role->tenant_id);
        }
        $this->assertEqualsCanonicalizing(
            $tenantRoleIds,
            $result['roles']->pluck('id')->all()
        );
    }

    public function test_get_roles_for_platform_user_returns_only_template_roles(): void
    {
        $platform = $this->makePlatformOperator();

        $this->actingAs($platform);

        $service = app(UserManagementService::class);
        $result = $service->getRoles();

        $templateIds = Role::withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->pluck('id')
            ->all();

        $this->assertNotEmpty($templateIds);
        foreach ($result['roles'] as $role) {
            $this->assertNull($role->tenant_id);
        }
        $this->assertEqualsCanonicalizing(
            $templateIds,
            $result['roles']->pluck('id')->all()
        );
    }
}
