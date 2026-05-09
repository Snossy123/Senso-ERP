<?php

namespace Tests\Feature\Foundation;

use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\Sale;
use App\Models\SaleRefund;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

class PosRefundCharacterizationTest extends TestCase
{
    use FoundationBaselineFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundationTenantAndStaff();
    }

    public function test_pos_refund_creates_refund_row_and_restock_baseline(): void
    {
        $shift = \App\Models\PosShift::factory()->create([
            'user_id' => $this->foundationUser->id,
            'tenant_id' => $this->foundationTenantId,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 10,
            'selling_price' => 100,
            'purchase_price' => 40,
        ]);

        $saleResponse = $this->actingAs($this->foundationUser)->postJson(route('pos.sale.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 2, 'price' => 100, 'discount_pct' => 0],
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 200,
            'shift_id' => $shift->id,
            'tax_rate' => 0,
            'discount' => 0,
        ]);

        $saleResponse->assertOk();
        $sale = Sale::findOrFail($saleResponse->json('sale_id'));
        $this->assertSame(8, $product->fresh()->stock_quantity);

        $outMovements = StockMovement::query()
            ->where('product_id', $product->id)
            ->where('type', 'out')
            ->count();
        $this->assertSame(1, $outMovements);

        $refund = $this->actingAs($this->foundationUser)->postJson(route('pos.sales.refund', $sale), [
            'amount' => 200,
            'reason' => 'Full return',
            'method' => 'cash',
            'restock' => true,
        ]);

        $refund->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('sale_refunds', [
            'sale_id' => $sale->id,
            'amount' => 200,
        ]);

        $sale->refresh();
        $this->assertSame('refunded', $sale->status);

        $this->assertSame(10, $product->fresh()->stock_quantity);

        $inMovements = StockMovement::query()
            ->where('product_id', $product->id)
            ->where('type', 'in')
            ->where('reference', 'like', 'REF-%')
            ->count();
        $this->assertSame(1, $inMovements);

        $refundModel = SaleRefund::where('sale_id', $sale->id)->first();
        $this->assertNotNull($refundModel);

        $refundJournals = JournalEntry::query()
            ->where('source_type', SaleRefund::class)
            ->where('source_id', $refundModel->id)
            ->count();
        $this->assertSame(
            1,
            $refundJournals,
            'Current behavior: refund posts a single journal entry from SaleController (no SaleRefund model observer in EventServiceProvider).'
        );
    }

    public function test_pos_refund_partial_restock_uses_prorated_quantity_current_baseline(): void
    {
        $shift = \App\Models\PosShift::factory()->create([
            'user_id' => $this->foundationUser->id,
            'tenant_id' => $this->foundationTenantId,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 20,
            'selling_price' => 50,
        ]);

        $this->actingAs($this->foundationUser)->postJson(route('pos.sale.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 3, 'price' => 50, 'discount_pct' => 0],
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 150,
            'shift_id' => $shift->id,
            'tax_rate' => 0,
            'discount' => 0,
        ])->assertOk();

        $sale = Sale::latest('id')->first();
        $this->assertSame(17, $product->fresh()->stock_quantity);

        $this->actingAs($this->foundationUser)->postJson(route('pos.sales.refund', $sale), [
            'amount' => 75,
            'reason' => 'Partial',
            'method' => 'cash',
            'restock' => true,
        ])->assertOk();

        $ratio = 75 / 150;
        $expectedRestock = (int) round(3 * $ratio);
        $this->assertSame(2, $expectedRestock);

        $this->assertSame(19, $product->fresh()->stock_quantity);

        $sale->refresh();
        $this->assertSame(
            'refunded',
            $sale->status,
            'Legacy: totalRefunded uses sum(refunds)+current amount, double-counting the new refund so partial payment can mark sale as refunded.'
        );
    }

    public function test_pos_refund_full_restock_restores_warehouse_slice_when_shift_has_warehouse(): void
    {
        $warehouse = \App\Models\Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Refund WH',
            'is_active' => true,
        ]);

        $shift = \App\Models\PosShift::factory()->create([
            'user_id' => $this->foundationUser->id,
            'tenant_id' => $this->foundationTenantId,
            'warehouse_id' => $warehouse->id,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 10,
            'selling_price' => 100,
            'purchase_price' => 40,
        ]);

        ProductWarehouseStock::create([
            'tenant_id' => $this->foundationTenantId,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
        ]);

        $saleResponse = $this->actingAs($this->foundationUser)->postJson(route('pos.sale.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 2, 'price' => 100, 'discount_pct' => 0],
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 200,
            'shift_id' => $shift->id,
            'tax_rate' => 0,
            'discount' => 0,
        ]);

        $saleResponse->assertOk();
        $sale = Sale::findOrFail($saleResponse->json('sale_id'));

        $this->assertSame(8, $product->fresh()->stock_quantity);
        $this->assertSame(
            8,
            (int) ProductWarehouseStock::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->value('quantity')
        );

        $this->actingAs($this->foundationUser)->postJson(route('pos.sales.refund', $sale), [
            'amount' => 200,
            'reason' => 'Full return WH',
            'method' => 'cash',
            'restock' => true,
        ])->assertOk();

        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertSame(
            10,
            (int) ProductWarehouseStock::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->value('quantity')
        );

        $inMovement = \App\Models\StockMovement::query()
            ->where('product_id', $product->id)
            ->where('type', 'in')
            ->where('reference', 'like', 'REF-%')
            ->latest('id')
            ->first();

        $this->assertNotNull($inMovement);
        $this->assertSame($warehouse->id, (int) $inMovement->warehouse_id);
        $this->assertNull($inMovement->product_variant_id);
        $this->assertSame('POS Refund', $inMovement->notes);
    }
}
