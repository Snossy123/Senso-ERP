<?php

namespace Tests\Feature\Foundation;

use App\Models\CustomerPayment;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\Accounting\CommerceRevenueRecognition;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

class CustomerPaymentCharacterizationTest extends TestCase
{
    use FoundationBaselineFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundationTenantAndStaff();

        Permission::firstOrCreate(
            ['slug' => 'accounting.collect'],
            ['name' => 'Accounting Collect', 'group' => 'accounting', 'description' => 'Collect customer payments']
        );
        $this->foundationUser->role->givePermissionTo('accounting.collect');
    }

    public function test_cod_on_place_collect_posts_ar_relief_journal(): void
    {
        $tenant = Tenant::find($this->foundationTenantId);
        $settings = $tenant->settings ?? [];
        $settings['commerce']['revenue_recognition'] = CommerceRevenueRecognition::ON_PLACE;
        $tenant->update(['settings' => $settings]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 50,
            'selling_price' => 30,
            'purchase_price' => 10,
            'is_ecommerce' => true,
        ]);

        app(TenantManager::class)->setCurrent($tenant);

        $response = $this->withHeaders($this->tenantHeader())
            ->withSession([
                'cart' => [$product->id => ['qty' => 1]],
            ])
            ->post(route('store.checkout.place'), [
                'customer_name' => 'COD Buyer',
                'customer_email' => 'cod@test.com',
                'customer_phone' => null,
                'shipping_address' => '1 Test Lane',
                'city' => 'Testville',
                'payment_method' => 'cash_on_delivery',
                'notes' => null,
            ]);

        $response->assertRedirect(route('store.checkout.success'));

        $order = Order::withoutGlobalScopes()
            ->where('tenant_id', $this->foundationTenantId)
            ->where('customer_email', 'cod@test.com')
            ->latest('id')
            ->first();

        $this->assertNotNull($order, 'Checkout should persist an order for the tenant.');

        $revenueJe = JournalEntry::query()
            ->where('source_type', Order::class)
            ->where('source_id', $order->id)
            ->count();
        $this->assertSame(1, $revenueJe, 'on_place should post revenue at checkout');

        $this->actingAs($this->foundationUser)->post(route('admin.orders.mark-paid', $order), [
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);

        $payment = CustomerPayment::withoutGlobalScopes()
            ->where('order_id', $order->id)
            ->first();
        $this->assertNotNull($payment);

        $receiptJe = JournalEntry::query()
            ->where('source_type', CustomerPayment::class)
            ->where('source_id', $payment->id)
            ->count();

        $this->assertSame(1, $receiptJe);
    }
}
