<?php

namespace Tests\Feature;

use App\Services\TenantManager;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

#[Group('administration')]
class TenantAccessTest extends TestCase
{
    use AdministrationFixtures;

    public function test_active_tenant_with_past_subscription_cannot_access_application(): void
    {
        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'sub-exp-'.str_replace('.', '', uniqid('', true)),
            'status' => 'active',
            'is_active' => true,
            'subscription_ends_at' => now()->subDay(),
        ]);

        $this->assertTrue($tenant->isExpired());
        $this->assertFalse($tenant->allowsApplicationAccess());
    }

    public function test_tenant_with_ended_subscription_cannot_login(): void
    {
        $this->withoutCsrf();
        $this->seedRoleTemplates();

        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'sub-login-'.str_replace('.', '', uniqid('', true)),
            'status' => 'active',
            'is_active' => true,
            'subscription_ends_at' => now()->subDay(),
        ]);
        $user = $this->makeTenantAdmin($tenant, ['password' => 'password']);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_forged_platform_operator_session_does_not_bind_suspended_tenant(): void
    {
        $this->seedRoleTemplates();

        $tenant = $this->createTenantWithClonedRoles([
            'slug' => 'forge-'.str_replace('.', '', uniqid('', true)),
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);
        $user = $this->makeTenantAdmin($tenant);
        $platform = $this->makePlatformOperator();

        $this->actingAs($user)
            ->withSession(['platform_operator_id' => $platform->id])
            ->get(route('inventory.products.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNull(app(TenantManager::class)->getCurrentId());
    }
}
