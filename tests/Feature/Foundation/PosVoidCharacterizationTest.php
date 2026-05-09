<?php

namespace Tests\Feature\Foundation;

use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

class PosVoidCharacterizationTest extends TestCase
{
    use FoundationBaselineFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundationTenantAndStaff();
    }

    public function test_void_restores_current_product_stock_baseline(): void
    {
        $warehouse = \App\Models\Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Void WH',
            'is_active' => true,
        ]);

        $shift = \App\Models\PosShift::factory()->create([
            'user_id' => $this->foundationUser->id,
            'tenant_id' => $this->foundationTenantId,
            'warehouse_id' => $warehouse->id,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 15,
            'selling_price' => 20,
        ]);

        ProductWarehouseStock::create([
            'tenant_id' => $this->foundationTenantId,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 15,
        ]);

        $saleResponse = $this->actingAs($this->foundationUser)->postJson(route('pos.sale.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 5, 'price' => 20, 'discount_pct' => 0],
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 100,
            'shift_id' => $shift->id,
            'tax_rate' => 0,
            'discount' => 0,
        ]);

        $saleResponse->assertOk();
        $sale = Sale::findOrFail($saleResponse->json('sale_id'));

        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertSame(10, (int) ProductWarehouseStock::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->value('quantity'));

        $void = $this->actingAs($this->foundationUser)->postJson(route('pos.sales.void', $sale), [
            'reason' => 'Cashier mistake',
        ]);

        $void->assertOk()->assertJson(['success' => true]);

        $sale->refresh();
        $this->assertSame('voided', $sale->status);

        $this->assertSame(15, $product->fresh()->stock_quantity);

        $voidMovement = StockMovement::query()
            ->where('product_id', $product->id)
            ->where('type', 'in')
            ->where('reference', 'VOID-'.$sale->sale_number)
            ->first();

        $this->assertNotNull($voidMovement);
        $this->assertSame($warehouse->id, (int) $voidMovement->warehouse_id);
        $this->assertNull($voidMovement->product_variant_id);
        $this->assertSame('Voided POS Sale', $voidMovement->notes);

        $this->assertSame(
            15,
            (int) ProductWarehouseStock::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->value('quantity'),
            'Void restores rolled-up stock and the warehouse slice used by the POS sale.'
        );
    }
}
