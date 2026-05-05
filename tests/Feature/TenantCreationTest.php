<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TenantService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_create_tenant_provisions_administrator_settings_and_usage(): void
    {
        $service = app(TenantService::class);

        $result = $service->createTenant([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'settings' => ['onboarding' => true],
            'trial_days' => 14,
            'currency' => 'USD',
            'language' => 'en',
            'timezone' => 'UTC',
            'create_support_user' => true,
            'support_name' => 'Acme Admin',
        ]);

        /** @var Tenant $tenant */
        $tenant = $result['tenant'];
        $tenant->refresh();

        $this->assertSame(['onboarding' => true], $tenant->settings);

        $this->assertNotNull($result['support_password']);
        $this->assertGreaterThanOrEqual(12, strlen($result['support_password']));

        $this->assertSame(1, $tenant->users()->count());
        $admin = $tenant->users()->first();
        $this->assertSame('Acme Admin', $admin->name);
        $this->assertSame($tenant->id, $admin->tenant_id);
        $this->assertTrue($admin->must_change_password);
        $this->assertSame('admin', $admin->role?->slug);
        $this->assertSame($tenant->id, $admin->role?->tenant_id);

        $usage = $tenant->getUsage('users');
        $this->assertNotNull($usage);
        $this->assertSame(1, (int) $usage->current_usage);
    }

    public function test_create_tenant_without_support_user_leaves_no_users_and_null_password(): void
    {
        $service = app(TenantService::class);

        $result = $service->createTenant([
            'name' => 'Solo Org',
            'slug' => 'solo-org',
            'trial_days' => 14,
            'currency' => 'USD',
            'language' => 'en',
            'timezone' => 'UTC',
            'create_support_user' => false,
        ]);

        $tenant = $result['tenant'];
        $this->assertNull($result['support_password']);
        $this->assertSame(0, $tenant->users()->count());
    }
}
