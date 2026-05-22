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
            ->assertOk();

        $this->assertNull(session('tenant_id'));
        $this->assertNull(app(TenantManager::class)->getCurrentId());
        $this->assertSame(2, Product::count());
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
            ->assertOk();

        $this->assertNull(session('tenant_id'));
        $this->assertNull(app(TenantManager::class)->getCurrentId());
        $this->assertFalse($tenant->fresh()->allowsApplicationAccess());
    }
}
