<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TenantService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

class TenantClientLoginTest extends TestCase
{
    use AdministrationFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_client_can_login_with_admin_email_and_custom_password(): void
    {
        $this->withoutCsrf();

        $result = app(TenantService::class)->createTenant([
            'name' => 'Client Co',
            'slug' => 'client-co',
            'admin_email' => 'owner@client.test',
            'admin_password' => 'SecurePass1!',
            'create_support_user' => true,
        ]);

        $tenant = $result['tenant'];
        $this->assertTrue($tenant->allowsApplicationAccess());

        $this->post(route('login'), [
            'email' => 'owner@client.test',
            'password' => 'SecurePass1!',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_auto_password_requires_change_on_first_login(): void
    {
        $this->withoutCsrf();

        $result = app(TenantService::class)->createTenant([
            'name' => 'Auto Pass Co',
            'slug' => 'auto-pass',
            'admin_email' => 'auto@client.test',
            'create_support_user' => true,
        ]);

        $password = $result['support_password'];
        $this->assertNotNull($password);

        $this->post(route('login'), [
            'email' => 'auto@client.test',
            'password' => $password,
        ])->assertRedirect(route('password.change'));
    }
}
