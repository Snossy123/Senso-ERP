<?php

namespace Tests\Support;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RoleProvisioningService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @phpstan-require-extends \Tests\TestCase
 */
trait AdministrationFixtures
{
    use RefreshDatabase;

    protected function seedRoleTemplates(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createTenantWithClonedRoles(array $overrides = []): Tenant
    {
        $defaults = [
            'name' => 'Fixture Co',
            'slug' => 'fixture-co',
            // `trial` fails Tenant::isActive(); middleware then never binds TenantManager (needed for settings / tenant scope).
            'status' => 'active',
            'is_active' => true,
            'trial_ends_at' => now()->addDays(14),
            'currency' => 'USD',
            'language' => 'en',
            'timezone' => 'UTC',
        ];

        $tenant = Tenant::create(array_merge($defaults, $overrides));
        app(RoleProvisioningService::class)->cloneDefaultRolesForTenant($tenant);

        return $tenant;
    }

    protected function makePlatformOperator(array $overrides = []): User
    {
        $role = Role::withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->where('slug', 'admin')
            ->firstOrFail();

        return User::factory()->create(array_merge([
            'tenant_id' => null,
            'role_id' => $role->id,
            'email' => 'platform-'.uniqid().'@test.local',
        ], $overrides));
    }

    protected function makeTenantAdmin(Tenant $tenant, array $overrides = []): User
    {
        $role = Role::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'admin')
            ->firstOrFail();

        return User::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'role_id' => $role->id,
            'email' => 'admin-'.uniqid().'@test.local',
        ], $overrides));
    }

    protected function makeTenantCashier(Tenant $tenant, array $overrides = []): User
    {
        $role = Role::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'cashier')
            ->firstOrFail();

        return User::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'role_id' => $role->id,
            'email' => 'cashier-'.uniqid().'@test.local',
        ], $overrides));
    }

    /**
     * Form POSTs in tests (users, settings) need CSRF disabled in Feature tests.
     */
    protected function withoutCsrf(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }
}
