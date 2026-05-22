<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Services\TenantManager;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

class TenantManagerTest extends TestCase
{
    use AdministrationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoleTemplates();
    }

    public function test_get_current_id_falls_back_to_session_when_not_bound_in_memory(): void
    {
        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'tm-sess-'.str_replace('.', '', uniqid('', true)),
        ]);

        $manager = app(TenantManager::class);
        $manager->clear();

        session(['tenant_id' => $tenant->id]);

        $this->assertSame($tenant->id, $manager->getCurrentId());
        $this->assertSame($tenant->id, $manager->getCurrent()?->id);
    }

    public function test_set_current_syncs_session_and_memory(): void
    {
        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'tm-bind-'.str_replace('.', '', uniqid('', true)),
        ]);

        $manager = app(TenantManager::class);
        $manager->setCurrent($tenant);

        $this->assertSame($tenant->id, $manager->getCurrentId());
        $this->assertSame($tenant->id, session('tenant_id'));
    }
}
