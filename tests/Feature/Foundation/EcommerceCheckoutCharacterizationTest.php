<?php

namespace Tests\Feature\Foundation;

use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

class EcommerceCheckoutCharacterizationTest extends TestCase
{
    use FoundationBaselineFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundationTenantAndStaff();
    }

    public function test_ecommerce_checkout_creates_order_items_and_stock_movements_baseline(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 12,
            'selling_price' => 33,
            'purchase_price' => 10,
            'is_ecommerce' => true,
        ]);

        $response = $this->withHeaders($this->tenantHeader())
            ->withSession([
                'cart' => [
                    $product->id => ['qty' => 3],
                ],
            ])
            ->post(route('store.checkout.place'), [
                'customer_name' => 'Guest Buyer',
                'customer_email' => 'guest@example.org',
                'customer_phone' => null,
                'shipping_address' => '1 Test Lane',
                'city' => 'Testville',
                'payment_method' => 'cash_on_delivery',
                'notes' => null,
            ]);

        $response->assertRedirect(route('store.checkout.success'));

        $order = Order::where('tenant_id', $this->foundationTenantId)->latest('id')->first();
        $this->assertNotNull($order);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->assertSame(9, $product->fresh()->stock_quantity);

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $this->foundationTenantId,
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 3,
            'notes' => 'Ecommerce Order',
        ]);
    }

    public function test_ecommerce_orders_table_has_client_idempotency_key(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasColumn((new Order)->getTable(), 'client_idempotency_key'),
            'orders.client_idempotency_key enables duplicate checkout protection.'
        );
    }
}
