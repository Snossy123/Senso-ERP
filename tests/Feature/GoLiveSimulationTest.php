<?php

namespace Tests\Feature;

use App\Models\AccountSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\GoLiveChecklistService;
use App\Services\TenantService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

/**
 * End-to-end provisioning + channel smoke checks for a new tenant go-live path.
 */
class GoLiveSimulationTest extends TestCase
{
    use FoundationBaselineFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seedFoundationTenantAndStaff();
    }

    public function test_new_tenant_via_service_is_provisioned_for_operations(): void
    {
        $service = app(TenantService::class);

        $result = $service->createTenant([
            'name' => 'Go-Live Simulation Co',
            'slug' => 'go-live-'.str_replace('.', '', uniqid('', true)),
            'trial_days' => 14,
            'create_support_user' => true,
            'support_name' => 'Go Live Admin',
        ]);

        $tenant = $result['tenant'];

        $this->assertGreaterThanOrEqual(
            5,
            AccountSetting::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count()
        );
        $this->assertSame(
            1,
            Warehouse::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count()
        );

        $checklist = app(GoLiveChecklistService::class);
        $this->assertGreaterThan(0, $checklist->completionPercentage($tenant));
    }

    public function test_ecommerce_checkout_after_provisioning_decrements_stock(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 8,
            'selling_price' => 25,
            'is_ecommerce' => true,
        ]);

        $this->withHeaders($this->tenantHeader())
            ->withSession(['cart' => [$product->id => ['qty' => 2]]])
            ->post(route('store.checkout.place'), [
                'customer_name' => 'Sim Buyer',
                'customer_email' => 'sim@example.org',
                'customer_phone' => null,
                'shipping_address' => '1 Sim St',
                'city' => 'Sim City',
                'payment_method' => 'cash_on_delivery',
                'notes' => null,
                'client_idempotency_key' => 'sim-'.uniqid('', true),
            ])
            ->assertRedirect(route('store.checkout.success'));

        $this->assertSame(6, $product->fresh()->stock_quantity);
        $this->assertSame(1, Order::where('tenant_id', $this->foundationTenantId)->count());
    }
}
