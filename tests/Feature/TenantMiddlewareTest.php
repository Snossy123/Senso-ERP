<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\TenantManager;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

#[Group('administration')]
class TenantMiddlewareTest extends TestCase
{
    use AdministrationFixtures;

    public function test_suspended_tenant_clears_stale_session_tenant_context(): void
    {
        $this->seedRoleTemplates();

        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'suspend-'.str_replace('.', '', uniqid('', true)),
        ]);
        $otherTenant = $this->createTenantWithClonedRoles([
            'slug' => 'other-'.str_replace('.', '', uniqid('', true)),
        ]);

        Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Mine']);
        Product::factory()->create(['tenant_id' => $otherTenant->id, 'name' => 'Other']);

        $user = $this->makeTenantAdmin($tenant);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('inventory.products.index'))
            ->assertOk();

        $this->assertSame($tenant->id, session('tenant_id'));
        $this->assertSame(1, Product::count());

        $tenant->update(['status' => 'suspended', 'suspended_at' => now()]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('inventory.products.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNull(session('tenant_id'));
        $this->assertNull(app(TenantManager::class)->getCurrentId());

        $this->actingAs($user);
        app(TenantManager::class)->clear();
        $this->assertSame(0, Product::count());
    }

    public function test_expired_trial_clears_stale_session_tenant_context(): void
    {
        $this->seedRoleTemplates();

        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'trial-exp-'.str_replace('.', '', uniqid('', true)),
            'status' => 'trial',
            'trial_ends_at' => now()->addDay(),
        ]);

        $user = $this->makeTenantAdmin($tenant);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('inventory.products.index'))
            ->assertOk();

        $this->assertSame($tenant->id, session('tenant_id'));

        $tenant->update(['trial_ends_at' => now()->subDay()]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('inventory.products.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNull(session('tenant_id'));
        $this->assertNull(app(TenantManager::class)->getCurrentId());
        $this->assertFalse($tenant->fresh()->allowsApplicationAccess());

        $this->actingAs($user);
        app(TenantManager::class)->clear();
        $this->assertSame(0, Product::count());
    }

    public function test_platform_impersonation_can_access_erp_for_suspended_tenant(): void
    {
        $this->seedRoleTemplates();

        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'imp-susp-'.str_replace('.', '', uniqid('', true)),
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);
        $user = $this->makeTenantAdmin($tenant);
        $platform = $this->makePlatformOperator();

        Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Suspended Product']);

        $this->actingAs($user)
            ->withSession([
                'platform_operator_id' => $platform->id,
                'admin_logged_in_as_tenant' => $tenant->id,
                'admin_logged_in_as_user' => $user->id,
            ])
            ->get(route('inventory.products.index'))
            ->assertOk();

        $this->assertSame($tenant->id, app(TenantManager::class)->getCurrentId());
        $this->assertSame(1, Product::count());
    }
}
