<?php

namespace Tests\Feature\Foundation;

use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

class PurchaseOrderReceiveCharacterizationTest extends TestCase
{
    use FoundationBaselineFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundationTenantAndStaff();
    }

    public function test_po_receive_posts_current_inventory_rows_baseline(): void
    {
        $warehouse = Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Receiving WH',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Supplier Baseline',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 20,
            'selling_price' => 50,
            'purchase_price' => 25,
        ]);

        $response = $this->actingAs($this->foundationUser)->post(route('inventory.purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 6,
                    'unit_cost' => 25,
                ],
            ],
        ]);

        $response->assertRedirect(route('inventory.purchase-orders.index'));

        $order = PurchaseOrder::where('supplier_id', $supplier->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame('draft', $order->status);

        $receive = $this->actingAs($this->foundationUser)->post(
            route('inventory.purchase-orders.receive', $order)
        );

        $receive->assertRedirect(route('inventory.purchase-orders.show', $order));

        $order->refresh();
        $this->assertSame('received', $order->status);

        $this->assertSame(26, $product->fresh()->stock_quantity);

        $this->assertSame(
            6,
            (int) ProductWarehouseStock::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->value('quantity')
        );

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $this->foundationTenantId,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'purchase_order_id' => $order->id,
            'type' => 'in',
            'quantity' => 6,
            'notes' => 'Received from PO',
        ]);

        $movementRows = StockMovement::query()
            ->where('purchase_order_id', $order->id)
            ->where('type', 'in')
            ->count();
        $this->assertSame(1, $movementRows);
    }

    public function test_po_receive_current_accounting_journal_entry_count_baseline(): void
    {
        $warehouse = Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'WH JE',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Supplier JE',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 1,
            'purchase_price' => 10,
        ]);

        $this->actingAs($this->foundationUser)->post(route('inventory.purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_cost' => 10,
                ],
            ],
        ]);

        $order = PurchaseOrder::where('supplier_id', $supplier->id)->latest('id')->first();

        $journalBefore = JournalEntry::query()
            ->where('source_type', PurchaseOrder::class)
            ->where('source_id', $order->id)
            ->count();

        $this->assertSame(0, $journalBefore);

        $this->actingAs($this->foundationUser)->post(route('inventory.purchase-orders.receive', $order));

        $journalAfter = JournalEntry::query()
            ->where('source_type', PurchaseOrder::class)
            ->where('source_id', $order->id)
            ->count();

        $this->assertSame(
            0,
            $journalAfter,
            'Current behavior: PO receive sets status to received; AccountingObserver listens for status completed, so no automated GRNI/AP journal from observer.'
        );
    }
}
