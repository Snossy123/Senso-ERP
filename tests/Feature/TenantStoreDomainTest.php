<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tenant;
use App\Services\TenantManager;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

class TenantStoreDomainTest extends TestCase
{
    use AdministrationFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_store_request_resolves_tenant_by_domain_host(): void
    {
        $this->seedRoleTemplates();

        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'shop-'.str_replace('.', '', uniqid('', true)),
            'domain' => 'shop.example.test',
        ]);

        Product::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Domain Product',
            'is_ecommerce' => true,
            'is_active' => true,
        ]);

        $other = $this->createTenantWithClonedRoles([
            'slug' => 'other-'.str_replace('.', '', uniqid('', true)),
            'domain' => 'other.example.test',
        ]);

        Product::factory()->create([
            'tenant_id' => $other->id,
            'name' => 'Other Product',
            'is_ecommerce' => true,
            'is_active' => true,
        ]);

        $this->get('http://shop.example.test/store')
            ->assertOk();

        $this->assertSame($tenant->id, app(TenantManager::class)->getCurrentId());
        $this->assertSame(1, Product::count());
        $this->assertSame('Domain Product', Product::first()->name);
    }

    public function test_store_request_without_tenant_context_returns_404(): void
    {
        $this->get('http://unknown-store.example.test/store')
            ->assertNotFound();
    }
}
