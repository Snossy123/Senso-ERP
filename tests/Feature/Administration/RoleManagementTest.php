<?php

namespace Tests\Feature\Administration;

use App\Models\Role;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

#[Group('administration')]
class RoleManagementTest extends TestCase
{
    use AdministrationFixtures;

    protected \App\Models\Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoleTemplates();
        $this->tenant = $this->createTenantWithClonedRoles([
            'slug' => 'r-'.str_replace('.', '', uniqid('', true)),
        ]);
    }

    public function test_tenant_admin_can_list_roles(): void
    {
        $admin = $this->makeTenantAdmin($this->tenant);

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk();
    }

    public function test_tenant_admin_can_create_role_even_when_name_slug_matches_existing_clone(): void
    {
        $admin = $this->makeTenantAdmin($this->tenant);
        $permIds = \App\Models\Permission::query()->limit(2)->pluck('id')->all();

        $response = $this->actingAs($admin)->postJson(route('admin.roles.store'), [
            'name' => 'Cashier',
            'description' => 'Extra cashier-like role',
            'permissions' => $permIds,
            'is_active' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('roles', [
            'name' => 'Cashier',
            'slug' => 'cashier-2',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_tenant_admin_can_create_role_via_json(): void
    {
        $admin = $this->makeTenantAdmin($this->tenant);
        $permIds = \App\Models\Permission::query()->limit(3)->pluck('id')->all();

        $name = 'Custom Role '.uniqid();

        $response = $this->actingAs($admin)->postJson(route('admin.roles.store'), [
            'name' => $name,
            'description' => 'Test role',
            'permissions' => $permIds,
            'is_active' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('roles', [
            'name' => $name,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_tenant_admin_can_update_role_via_json(): void
    {
        $admin = $this->makeTenantAdmin($this->tenant);
        $role = Role::withoutGlobalScopes()->create([
            'name' => 'Updatable '.uniqid(),
            'slug' => 'updatable-'.uniqid(),
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $newName = 'Renamed '.uniqid();

        $response = $this->actingAs($admin)->putJson(route('admin.roles.update', $role), [
            'name' => $newName,
            'description' => 'Updated',
            'permissions' => [],
            'is_active' => true,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame($newName, $role->fresh()->name);
    }

    public function test_tenant_admin_can_delete_unused_custom_role(): void
    {
        $admin = $this->makeTenantAdmin($this->tenant);
        $role = Role::withoutGlobalScopes()->create([
            'name' => 'Deletable '.uniqid(),
            'slug' => 'deletable-'.uniqid(),
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->deleteJson(route('admin.roles.destroy', $role));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_cannot_resolve_other_tenant_role_for_edit(): void
    {
        $admin = $this->makeTenantAdmin($this->tenant);
        $otherTenant = $this->createTenantWithClonedRoles([
            'slug' => 'other-'.str_replace('.', '', uniqid('', true)),
            'name' => 'Other Org',
        ]);
        $foreignRole = Role::withoutGlobalScopes()
            ->where('tenant_id', $otherTenant->id)
            ->where('slug', 'admin')
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.roles.edit', $foreignRole))
            ->assertForbidden();
    }

    public function test_cashier_gets_403_on_roles_index(): void
    {
        $cashier = $this->makeTenantCashier($this->tenant);

        $this->actingAs($cashier)
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }
}
