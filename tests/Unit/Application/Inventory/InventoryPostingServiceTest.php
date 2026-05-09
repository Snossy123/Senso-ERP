<?php

namespace Tests\Unit\Application\Inventory;

use App\Application\Inventory\InventoryPostingService;
use App\Application\Inventory\StockMovementReason;
use App\Application\Inventory\StockPostingData;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductWarehouseStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

class InventoryPostingServiceTest extends TestCase
{
    use FoundationBaselineFixtures;
    use RefreshDatabase;

    private InventoryPostingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundationTenantAndStaff();
        $this->service = app(InventoryPostingService::class);
    }

    public function test_post_inbound_creates_movement_and_updates_warehouse_and_rolled_up_stock(): void
    {
        $warehouse = Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Unit WH',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 100,
        ]);

        $movement = $this->service->postInbound(
            StockPostingData::forGoodsReceipt(
                tenantId: $this->foundationTenantId,
                productId: $product->id,
                productVariantId: null,
                warehouseId: $warehouse->id,
                quantity: 7,
                unitCost: 12.5,
                totalValue: 87.5,
                reference: 'PO-UNIT-1',
                notes: 'Received from PO',
                userId: $this->foundationUser->id,
                purchaseOrderId: null,
            )
        );

        $this->assertSame(107, $product->fresh()->stock_quantity);

        $this->assertSame(
            7,
            (int) ProductWarehouseStock::query()->withoutGlobalScopes()
                ->where('tenant_id', $this->foundationTenantId)
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->value('quantity')
        );

        $this->assertSame('in', $movement->type);
        $this->assertSame(100, (int) $movement->before_quantity);
        $this->assertSame(107, (int) $movement->after_quantity);
        $this->assertNull($movement->purchase_order_id);
        $this->assertSame('Received from PO', $movement->notes);
    }

    public function test_post_inbound_variant_scoped_warehouse_row(): void
    {
        $warehouse = Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Variant WH',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 0,
            'has_variants' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Size M',
            'sku' => 'SKU-M',
            'is_active' => true,
        ]);

        $this->service->postInbound(
            StockPostingData::forGoodsReceipt(
                tenantId: $this->foundationTenantId,
                productId: $product->id,
                productVariantId: $variant->id,
                warehouseId: $warehouse->id,
                quantity: 3,
                unitCost: 10,
                totalValue: 30,
                reference: 'PO-VAR',
                notes: 'Received from PO',
                userId: null,
                purchaseOrderId: null,
            )
        );

        $this->assertSame(3, $product->fresh()->stock_quantity);

        $countVariantScoped = ProductWarehouseStock::query()->withoutGlobalScopes()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant->id)
            ->where('warehouse_id', $warehouse->id)
            ->count();
        $this->assertSame(1, $countVariantScoped);
    }

    public function test_post_outbound_decrements_warehouse_and_product_and_records_movement(): void
    {
        $warehouse = Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Out WH',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 50,
            'purchase_price' => 5,
        ]);

        ProductWarehouseStock::query()->withoutGlobalScopes()->create([
            'tenant_id' => $this->foundationTenantId,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'warehouse_id' => $warehouse->id,
            'quantity' => 20,
        ]);

        $data = new StockPostingData(
            tenantId: $this->foundationTenantId,
            productId: $product->id,
            productVariantId: null,
            warehouseId: $warehouse->id,
            quantity: 4,
            type: 'out',
            unitCost: (float) $product->purchase_price,
            totalValue: 20,
            reference: 'OUT-1',
            notes: 'Test outbound',
            userId: $this->foundationUser->id,
            purchaseOrderId: null,
            stockTransferId: null,
            reason: StockMovementReason::PosSale,
        );

        $movement = $this->service->postOutbound($data);

        $this->assertSame(46, $product->fresh()->stock_quantity);
        $this->assertSame(
            16,
            (int) ProductWarehouseStock::query()->withoutGlobalScopes()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->value('quantity')
        );
        $this->assertSame('out', $movement->type);
        $this->assertSame(50, (int) $movement->before_quantity);
        $this->assertSame(46, (int) $movement->after_quantity);
    }

    public function test_post_adjustment_sets_absolute_rolled_up_stock_like_legacy_movements_controller(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 40,
        ]);

        $data = new StockPostingData(
            tenantId: $this->foundationTenantId,
            productId: $product->id,
            productVariantId: null,
            warehouseId: null,
            quantity: 12,
            type: 'adjustment',
            unitCost: null,
            totalValue: null,
            reference: 'ADJ-1',
            notes: 'Cycle count',
            userId: $this->foundationUser->id,
            purchaseOrderId: null,
            stockTransferId: null,
            reason: StockMovementReason::Adjustment,
            absoluteTargetStock: 12,
        );

        $movement = $this->service->postAdjustment($data);

        $this->assertSame(12, $product->fresh()->stock_quantity);
        $this->assertSame('adjustment', $movement->type);
        $this->assertSame(40, (int) $movement->before_quantity);
        $this->assertSame(12, (int) $movement->after_quantity);
        $this->assertSame(12, (int) $movement->quantity);
    }

    public function test_post_pos_sale_line_without_warehouse_only_decrements_rolled_up_stock(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 10,
            'purchase_price' => 8,
        ]);

        $movement = $this->service->postOutbound(
            StockPostingData::forPosSaleLine(
                tenantId: $this->foundationTenantId,
                productId: $product->id,
                productVariantId: null,
                warehouseId: null,
                quantity: 3,
                unitCost: 8,
                totalValue: 24,
                saleNumber: 'SL-POS-01',
                userId: $this->foundationUser->id,
            )
        );

        $this->assertSame(7, $product->fresh()->stock_quantity);
        $this->assertNull($movement->warehouse_id);
        $this->assertNull($movement->product_variant_id);
        $this->assertSame('POS Sale', $movement->notes);
        $this->assertSame($this->foundationUser->id, $movement->user_id);
        $this->assertSame(10, (int) $movement->before_quantity);
        $this->assertSame(7, (int) $movement->after_quantity);
    }

    public function test_post_pos_sale_line_with_warehouse_decrements_slice_after_rolled_up(): void
    {
        $warehouse = Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'POS WH',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 20,
            'purchase_price' => 5,
        ]);

        ProductWarehouseStock::query()->withoutGlobalScopes()->create([
            'tenant_id' => $this->foundationTenantId,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'warehouse_id' => $warehouse->id,
            'quantity' => 15,
        ]);

        $movement = $this->service->postOutbound(
            StockPostingData::forPosSaleLine(
                tenantId: $this->foundationTenantId,
                productId: $product->id,
                productVariantId: null,
                warehouseId: $warehouse->id,
                quantity: 2,
                unitCost: 5,
                totalValue: 10,
                saleNumber: 'SL-POS-02',
                userId: $this->foundationUser->id,
            )
        );

        $this->assertSame(18, $product->fresh()->stock_quantity);
        $this->assertSame(
            13,
            (int) ProductWarehouseStock::query()->withoutGlobalScopes()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->value('quantity')
        );
        $this->assertSame($warehouse->id, $movement->warehouse_id);
    }

    public function test_post_pos_sale_line_with_variant_sets_variant_on_movement_and_warehouse_row(): void
    {
        $warehouse = Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'POS Variant WH',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 5,
            'purchase_price' => 12,
            'has_variants' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Small',
            'sku' => 'SM-1',
            'is_active' => true,
        ]);

        ProductWarehouseStock::query()->withoutGlobalScopes()->create([
            'tenant_id' => $this->foundationTenantId,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 8,
        ]);

        $movement = $this->service->postOutbound(
            StockPostingData::forPosSaleLine(
                tenantId: $this->foundationTenantId,
                productId: $product->id,
                productVariantId: $variant->id,
                warehouseId: $warehouse->id,
                quantity: 2,
                unitCost: 12,
                totalValue: 24,
                saleNumber: 'SL-POS-03',
                userId: $this->foundationUser->id,
            )
        );

        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->assertSame($variant->id, $movement->product_variant_id);
        $this->assertSame($warehouse->id, $movement->warehouse_id);
        $this->assertSame(
            6,
            (int) ProductWarehouseStock::query()->withoutGlobalScopes()
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouse->id)
                ->value('quantity')
        );
    }

    public function test_post_inbound_for_pos_void_line_restores_warehouse_slice(): void
    {
        $warehouse = Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Void restore WH',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 10,
            'purchase_price' => 8,
        ]);

        ProductWarehouseStock::query()->withoutGlobalScopes()->create([
            'tenant_id' => $this->foundationTenantId,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'warehouse_id' => $warehouse->id,
            'quantity' => 7,
        ]);

        $payload = StockPostingData::forPosVoidLine(
            tenantId: $this->foundationTenantId,
            productId: $product->id,
            productVariantId: null,
            warehouseId: $warehouse->id,
            quantity: 3,
            unitCost: 8,
            totalValue: 24,
            reference: 'VOID-SN-99',
            userId: $this->foundationUser->id,
        );
        $this->assertSame(StockMovementReason::Void, $payload->reason);

        $m = $this->service->postInbound($payload);

        $this->assertSame('Voided POS Sale', $m->notes);
        $this->assertSame('VOID-SN-99', $m->reference);
        $this->assertSame($warehouse->id, $m->warehouse_id);
        $this->assertSame(13, $product->fresh()->stock_quantity);
        $this->assertSame(
            10,
            (int) ProductWarehouseStock::query()->withoutGlobalScopes()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->value('quantity')
        );
    }
}
