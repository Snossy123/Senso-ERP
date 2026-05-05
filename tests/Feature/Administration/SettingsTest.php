<?php

namespace Tests\Feature\Administration;

use App\Models\Setting;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

#[Group('administration')]
class SettingsTest extends TestCase
{
    use AdministrationFixtures;

    protected \App\Models\Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoleTemplates();
        $this->tenant = $this->createTenantWithClonedRoles([
            'slug' => 'settings-'.str_replace('.', '', uniqid('', true)),
        ]);
    }

    public function test_tenant_admin_can_view_settings_index(): void
    {
        $admin = $this->makeTenantAdmin($this->tenant);

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk();
    }

    public function test_tenant_admin_can_store_settings_for_current_tenant(): void
    {
        $this->withoutCsrf();

        $admin = $this->makeTenantAdmin($this->tenant);
        $companyName = 'Acme Fixture '.uniqid();

        $this->actingAs($admin)
            ->post(route('admin.settings.store'), [
                'group' => 'business',
                'company_name' => $companyName,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'tenant_id' => $this->tenant->id,
            'group' => 'business',
            'key' => 'company_name',
            'value' => $companyName,
        ]);

        $this->assertTrue(
            Setting::withoutGlobalScopes()
                ->where('tenant_id', $this->tenant->id)
                ->where('key', 'company_name')
                ->exists()
        );
    }
}
