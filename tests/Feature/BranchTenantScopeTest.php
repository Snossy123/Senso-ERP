<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Services\BranchProvisioningService;
use Database\Seeders\RolePermissionSeeder;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

#[Group('branches')]
class BranchTenantScopeTest extends TestCase
{
    use AdministrationFixtures;

    public function test_tenant_user_cannot_assign_branch_from_another_tenant(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $tenantA = $this->createTenantWithClonedRoles(['slug' => 'br-a-'.str_replace('.', '', uniqid('', true))]);
        $tenantB = $this->createTenantWithClonedRoles(['slug' => 'br-b-'.str_replace('.', '', uniqid('', true))]);

        app(BranchProvisioningService::class)->ensureDefaultBranchesForTenant($tenantA);
        app(BranchProvisioningService::class)->ensureDefaultBranchesForTenant($tenantB);

        $foreignBranch = Branch::withoutGlobalScopes()
            ->where('tenant_id', $tenantB->id)
            ->where('code', 'HQ')
            ->firstOrFail();

        $adminA = $this->makeTenantAdmin($tenantA);

        $this->withoutCsrf();
        $this->actingAs($adminA)
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'scope-test-'.uniqid().'@test.local',
                'password' => 'Password!123',
                'password_confirmation' => 'Password!123',
                'role_id' => $adminA->role_id,
                'branch_id' => $foreignBranch->id,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('branch_id');
    }

    public function test_get_branches_only_lists_current_tenant(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $tenantA = $this->createTenantWithClonedRoles(['slug' => 'br2-a-'.str_replace('.', '', uniqid('', true))]);
        $tenantB = $this->createTenantWithClonedRoles(['slug' => 'br2-b-'.str_replace('.', '', uniqid('', true))]);

        app(BranchProvisioningService::class)->ensureDefaultBranchesForTenant($tenantA);
        app(BranchProvisioningService::class)->ensureDefaultBranchesForTenant($tenantB);

        $adminA = $this->makeTenantAdmin($tenantA);

        $this->actingAs($adminA);

        $branches = app(\App\Services\UserManagementService::class)->getBranches()['branches'];

        $this->assertCount(3, $branches);
        foreach ($branches as $branch) {
            $this->assertSame($tenantA->id, (int) $branch->tenant_id);
        }
    }
}
