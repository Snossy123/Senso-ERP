<?php

namespace Tests\Feature\Administration;

use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

#[Group('administration')]
class UserManagementTest extends TestCase
{
    use AdministrationFixtures;

    protected \App\Models\Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoleTemplates();
        $this->tenant = $this->createTenantWithClonedRoles([
            'slug' => 'u-'.str_replace('.', '', uniqid('', true)),
        ]);
    }

    public function test_tenant_admin_can_list_users(): void
    {
        $admin = $this->makeTenantAdmin($this->tenant);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_tenant_admin_can_open_create_form(): void
    {
        $admin = $this->makeTenantAdmin($this->tenant);

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk();
    }

    public function test_tenant_admin_can_create_user(): void
    {
        $this->withoutCsrf();
        $admin = $this->makeTenantAdmin($this->tenant);
        $role = \App\Models\Role::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('slug', 'manager')
            ->firstOrFail();

        $email = 'new-user-'.uniqid().'@test.local';

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Staff',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_tenant_admin_can_view_user_in_same_tenant(): void
    {
        $admin = $this->makeTenantAdmin($this->tenant);
        $peer = $this->makeTenantCashier($this->tenant);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $peer))
            ->assertOk();
    }

    public function test_cashier_gets_403_on_users_index(): void
    {
        $cashier = $this->makeTenantCashier($this->tenant);

        $this->actingAs($cashier)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }
}
