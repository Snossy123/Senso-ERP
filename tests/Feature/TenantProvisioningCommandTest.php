<?php

namespace Tests\Feature;

use App\Models\AccountSetting;
use App\Models\Tenant;
use App\Services\TenantService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantProvisioningCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_tenants_provision_command_backfills_existing_tenant(): void
    {
        $result = app(TenantService::class)->createTenant([
            'name' => 'Backfill Co',
            'slug' => 'backfill-'.uniqid('', true),
            'create_support_user' => false,
        ]);

        $tenant = $result['tenant'];
        AccountSetting::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();

        $this->artisan('tenants:provision', ['--tenant' => (string) $tenant->id])
            ->assertExitCode(0);

        $this->assertGreaterThanOrEqual(
            5,
            AccountSetting::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count()
        );
    }
}
