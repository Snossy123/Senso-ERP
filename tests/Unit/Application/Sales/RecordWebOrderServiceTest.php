<?php

namespace Tests\Unit\Application\Sales;

use App\Application\Inventory\InventoryPostingService;
use App\Application\Sales\RecordWebOrderService;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\StockMovement;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

class RecordWebOrderServiceTest extends TestCase
{
    use FoundationBaselineFixtures;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundationTenantAndStaff();
        app(TenantManager::class)->setCurrent($this->foundationTenant);
    }

    private function checkoutPayload(): array
    {
        return [
            'customer_name' => 'Unit Buyer',
            'customer_email' => 'unit@example.org',
            'customer_phone' => null,
            'shipping_address' => '1 Lane',
            'city' => 'City',
            'payment_method' => 'cash_on_delivery',
            'notes' => null,
        ];
    }

    public function test_record_creates_order_and_items(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 50,
            'selling_price' => 25,
            'is_ecommerce' => true,
        ]);

        $service = app(RecordWebOrderService::class);

        $result = $service->record(
            [$product->id => ['qty' => 2]],
            $this->checkoutPayload(),
            null,
        );

        $this->assertSame('pending', $result->order->status);
        $this->assertSame('pending', $result->paymentStatus);
        $this->assertTrue($result->inventoryPosted);
        $this->assertCount(1, $result->order->items);
        $this->assertSame(2, (int) $result->order->items->first()->quantity);
    }

    public function test_record_posts_inventory_out_movements(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 30,
            'selling_price' => 10,
            'is_ecommerce' => true,
        ]);

        $beforeStock = $product->stock_quantity;

        app(RecordWebOrderService::class)->record(
            [$product->id => ['qty' => 4]],
            $this->checkoutPayload(),
            null,
        );

        $this->assertSame($beforeStock - 4, $product->fresh()->stock_quantity);

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $this->foundationTenantId,
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 4,
            'notes' => 'Ecommerce Order',
        ]);

        $this->assertSame(
            1,
            StockMovement::where('product_id', $product->id)->where('type', 'out')->count()
        );
    }

    public function test_record_returns_low_stock_products_when_below_threshold(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 12,
            'selling_price' => 5,
            'min_stock_alert' => 10,
            'is_ecommerce' => true,
        ]);

        $result = app(RecordWebOrderService::class)->record(
            [$product->id => ['qty' => 3]],
            $this->checkoutPayload(),
            null,
        );

        $this->assertCount(1, $result->lowStockProducts);
        $this->assertTrue($result->lowStockProducts[0]->is($product));
        $this->assertSame(9, $product->fresh()->stock_quantity);
    }

    public function test_record_rolls_back_when_inventory_posting_fails(): void
    {
        $this->mock(InventoryPostingService::class)
            ->shouldReceive('postOutbound')
            ->once()
            ->andThrow(new \RuntimeException('simulated posting failure'));

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 10,
            'selling_price' => 20,
            'is_ecommerce' => true,
        ]);

        $service = app(RecordWebOrderService::class);

        try {
            $service->record(
                [$product->id => ['qty' => 1]],
                $this->checkoutPayload(),
                null,
            );
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated posting failure', $e->getMessage());
        }

        $this->assertSame(
            0,
            Order::query()->withoutGlobalScopes()->where('tenant_id', $this->foundationTenantId)->count(),
            'Order must not persist when posting rolls back the transaction.'
        );
        $this->assertSame(10, $product->fresh()->stock_quantity);
    }

    public function test_record_adds_city_shipping_rate_to_total(): void
    {
        ShippingRate::create([
            'tenant_id' => $this->foundationTenantId,
            'city' => 'City',
            'city_label' => 'City',
            'fee' => 15.5,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 10,
            'selling_price' => 20,
            'is_ecommerce' => true,
        ]);

        $result = app(RecordWebOrderService::class)->record(
            [$product->id => ['qty' => 2]],
            $this->checkoutPayload(),
            null,
        );

        $this->assertEquals(40, (float) $result->order->subtotal);
        $this->assertEquals(15.5, (float) $result->order->shipping_cost);
        $this->assertEquals(55.5, (float) $result->order->total);
    }

    public function test_record_rejects_empty_cart(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(RecordWebOrderService::class)->record([], $this->checkoutPayload(), null);
    }

    public function test_record_rejects_insufficient_stock(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 2,
            'selling_price' => 15,
            'is_ecommerce' => true,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient stock');

        try {
            app(RecordWebOrderService::class)->record(
                [$product->id => ['qty' => 5]],
                $this->checkoutPayload(),
                null,
            );
        } finally {
            $this->assertSame(
                0,
                Order::query()->withoutGlobalScopes()->where('tenant_id', $this->foundationTenantId)->count(),
                'Order must not persist when stock validation fails.'
            );
            $this->assertSame(2, $product->fresh()->stock_quantity);
        }
    }
}
