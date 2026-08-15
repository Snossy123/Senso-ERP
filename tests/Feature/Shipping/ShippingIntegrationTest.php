<?php

namespace Tests\Feature\Shipping;

use App\Application\Sales\RecordWebOrderService;
use App\Application\Shipping\CreateShipmentService;
use App\Application\Shipping\SyncQpShipmentsService;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Shipment;
use App\Models\ShippingIntegration;
use App\Models\ShippingRate;
use App\Models\Warehouse;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

class ShippingIntegrationTest extends TestCase
{
    use FoundationBaselineFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundationTenantAndStaff();
        app(TenantManager::class)->setCurrent($this->foundationTenant);

        foreach (['orders.process', 'settings.view', 'settings.edit', 'sales_invoices.edit'] as $slug) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'group' => explode('.', $slug)[0], 'description' => $slug]
            );
            $this->foundationUser->role->givePermissionTo($slug);
        }
    }

    public function test_checkout_applies_city_rate_to_order_total(): void
    {
        ShippingRate::create([
            'tenant_id' => $this->foundationTenantId,
            'city' => 'القاهرة',
            'city_label' => 'Cairo',
            'fee' => 25,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 10,
            'selling_price' => 40,
            'is_ecommerce' => true,
        ]);

        $this->withHeaders($this->tenantHeader())
            ->withSession(['cart' => [$product->id => ['qty' => 2]]])
            ->post(route('store.checkout.place'), [
                'customer_name' => 'Guest Buyer',
                'customer_email' => 'guest@example.org',
                'customer_phone' => '01234567890',
                'shipping_address' => '1 Test Lane',
                'city' => 'القاهرة',
                'payment_method' => 'cash_on_delivery',
            ])
            ->assertRedirect(route('store.checkout.success'));

        $order = Order::query()->where('tenant_id', $this->foundationTenantId)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals(80, (float) $order->subtotal);
        $this->assertEquals(25, (float) $order->shipping_cost);
        $this->assertEquals(105, (float) $order->total);
    }

    public function test_unknown_city_keeps_shipping_free(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 5,
            'selling_price' => 10,
            'is_ecommerce' => true,
        ]);

        $result = app(RecordWebOrderService::class)->record(
            [$product->id => ['qty' => 1]],
            [
                'customer_name' => 'Unit Buyer',
                'customer_email' => 'unit@example.org',
                'customer_phone' => null,
                'shipping_address' => '1 Lane',
                'city' => 'Testville',
                'payment_method' => 'cash_on_delivery',
                'notes' => null,
            ]
        );

        $this->assertEquals(0, (float) $result->order->shipping_cost);
        $this->assertEquals(10, (float) $result->order->total);
    }

    public function test_admin_can_create_qp_shipment_for_order(): void
    {
        $this->fakeQpCreate(920178);

        $this->makeIntegration();
        $order = $this->makeOrder();

        $this->actingAs($this->foundationUser)
            ->withHeaders($this->tenantHeader())
            ->post(route('admin.orders.shipments.store', $order), [
                'notes' => 'Call first',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $shipment = Shipment::query()->first();
        $this->assertNotNull($shipment);
        $this->assertSame('920178', $shipment->carrier_serial);
        $this->assertSame('Pending', $shipment->status);
        $this->assertEquals(35, (float) $shipment->total_fees);
        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_sync_maps_delivered_to_order_status(): void
    {
        $this->makeIntegration();
        $order = $this->makeOrder(['status' => 'processing']);
        $shipment = Shipment::create([
            'tenant_id' => $this->foundationTenantId,
            'shippable_type' => $order->getMorphClass(),
            'shippable_id' => $order->id,
            'carrier' => 'qp',
            'carrier_serial' => '920178',
            'reference_id' => 'ORD-'.$order->id,
            'status' => 'Pending',
        ]);

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/integration/token')) {
                return Http::response(['token' => 'jwt-test'], 200);
            }
            if (str_contains($request->url(), 'get_order_update_history')) {
                return Http::response([
                    [
                        'serial' => 920178,
                        'field' => 'Order_Delivery_Status',
                        'old_value' => 'Pending',
                        'new_value' => 'Delivered',
                        'notes' => 'Left at door',
                    ],
                ], 200);
            }

            return Http::response([
                'serial' => 920178,
                'Order_Delivery_Status' => 'Delivered',
                'StatusNote' => 'Left at door',
                'total_fees' => '35.00',
            ], 200);
        });

        app(SyncQpShipmentsService::class)->syncAll();

        $this->assertSame('Delivered', $shipment->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->status);
    }

    public function test_invoice_shipment_does_not_change_invoice_totals(): void
    {
        $this->fakeQpCreate(445566);

        $this->makeIntegration();

        $customer = Customer::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Invoice Customer',
            'phone' => '01009969763',
            'address' => 'Zawya',
            'city' => 'قاهره',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Main WH',
            'is_active' => true,
        ]);

        $invoice = SalesInvoice::create([
            'tenant_id' => $this->foundationTenantId,
            'invoice_number' => 'INV-SHIP-1',
            'customer_id' => $customer->id,
            'user_id' => $this->foundationUser->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'confirmed',
            'payment_term' => 'credit',
            'subtotal' => 200,
            'total' => 200,
            'paid_amount' => 0,
            'balance_due' => 200,
            'payment_status' => 'unpaid',
            'invoice_date' => now()->toDateString(),
        ]);

        SalesInvoiceLine::create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => Product::factory()->create([
                'tenant_id' => $this->foundationTenantId,
                'stock_quantity' => 10,
                'selling_price' => 200,
            ])->id,
            'description' => 'Watch',
            'quantity' => 1,
            'unit_price' => 200,
            'line_total' => 200,
        ]);

        $this->actingAs($this->foundationUser)
            ->withHeaders($this->tenantHeader())
            ->post(route('sales.invoices.shipments.store', $invoice), [
                'full_name' => 'Invoice Customer',
                'phone' => '01009969763',
                'address' => 'Zawya',
                'city' => 'قاهره',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals(200, (float) $invoice->total);
        $this->assertEquals(200, (float) $invoice->balance_due);
        $this->assertSame('445566', $invoice->shipment->carrier_serial);
    }

    public function test_pending_shipment_can_be_updated(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/integration/token')) {
                return Http::response(['token' => 'jwt-test'], 200);
            }

            return Http::response([
                'serial' => 920178,
                'Order_Delivery_Status' => 'Pending',
                'total_fees' => '35.00',
                'full_name' => 'Updated Name',
                'phone' => '01000000000',
                'city' => 'الجيزة',
                'address' => 'New address',
            ], 200);
        });

        $this->makeIntegration();
        $order = $this->makeOrder();
        Shipment::create([
            'tenant_id' => $this->foundationTenantId,
            'shippable_type' => $order->getMorphClass(),
            'shippable_id' => $order->id,
            'carrier' => 'qp',
            'carrier_serial' => '920178',
            'status' => 'Pending',
            'full_name' => 'Ahmed',
            'phone' => '01234567890',
            'city' => 'القاهرة',
            'address' => 'Nasr City',
        ]);

        $this->actingAs($this->foundationUser)
            ->withHeaders($this->tenantHeader())
            ->put(route('admin.orders.shipments.update', $order), [
                'full_name' => 'Updated Name',
                'phone' => '01000000000',
                'address' => 'New address',
                'city' => 'الجيزة',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('Updated Name', $order->shipment->fresh()->full_name);
        $this->assertSame('الجيزة', $order->shipment->fresh()->city);
    }

    public function test_non_pending_shipment_cannot_be_updated(): void
    {
        $this->makeIntegration();
        $order = $this->makeOrder();
        Shipment::create([
            'tenant_id' => $this->foundationTenantId,
            'shippable_type' => $order->getMorphClass(),
            'shippable_id' => $order->id,
            'carrier' => 'qp',
            'carrier_serial' => '920178',
            'status' => 'Out For Delivery',
            'full_name' => 'Ahmed',
            'phone' => '01234567890',
            'city' => 'القاهرة',
            'address' => 'Nasr City',
        ]);

        $this->actingAs($this->foundationUser)
            ->withHeaders($this->tenantHeader())
            ->put(route('admin.orders.shipments.update', $order), [
                'full_name' => 'Updated Name',
                'phone' => '01000000000',
                'address' => 'New address',
                'city' => 'الجيزة',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_create_shipment_service_rejects_duplicate(): void
    {
        $this->fakeQpCreate(1);
        $this->makeIntegration();
        $order = $this->makeOrder();

        app(CreateShipmentService::class)->create($order);

        $this->expectException(\InvalidArgumentException::class);
        app(CreateShipmentService::class)->create($order);
    }

    private function makeIntegration(): ShippingIntegration
    {
        return ShippingIntegration::create([
            'tenant_id' => $this->foundationTenantId,
            'driver' => 'qp',
            'username' => 'qp-user',
            'password' => 'qp-pass',
            'is_active' => true,
            'default_weight' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'tenant_id' => $this->foundationTenantId,
            'order_number' => 'ORD-SHIP-'.uniqid(),
            'customer_name' => 'Ahmed',
            'customer_phone' => '01234567890',
            'shipping_address' => 'Nasr City',
            'city' => 'القاهرة',
            'subtotal' => 100,
            'shipping_cost' => 0,
            'total' => 100,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'status' => 'pending',
        ], $overrides));
    }

    private function fakeQpCreate(int $serial): void
    {
        Http::fake(function ($request) use ($serial) {
            if (str_contains($request->url(), '/integration/token')) {
                return Http::response(['token' => 'jwt-test'], 200);
            }

            return Http::response([
                'serial' => $serial,
                'Order_Delivery_Status' => 'Pending',
                'total_fees' => '35.00',
                'StatusNote' => null,
                'referenceID' => 'ORD-1',
                'weight' => '1.00',
            ], 200);
        });
    }
}
