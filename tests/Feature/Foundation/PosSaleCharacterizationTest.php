<?php

namespace Tests\Feature\Foundation;

use App\Events\Domain\Sales\SaleRecorded;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

class PosSaleCharacterizationTest extends TestCase
{
    use FoundationBaselineFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundationTenantAndStaff();
    }

    public function test_pos_sale_creates_sale_items_and_decrements_current_stock_baseline(): void
    {
        $shift = \App\Models\PosShift::factory()->create([
            'user_id' => $this->foundationUser->id,
            'tenant_id' => $this->foundationTenantId,
            'warehouse_id' => null,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 10,
            'selling_price' => 50,
            'purchase_price' => 20,
        ]);

        $response = $this->actingAs($this->foundationUser)->postJson(route('pos.sale.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 3, 'price' => 50, 'discount_pct' => 0],
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 150,
            'shift_id' => $shift->id,
            'tax_rate' => 0,
            'discount' => 0,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'sale_id',
                'change_due',
                'sale_number',
            ])
            ->assertJson([
                'success' => true,
            ]);

        $saleId = $response->json('sale_id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'tenant_id' => $this->foundationTenantId,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $saleId,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->assertSame(7, $product->fresh()->stock_quantity);

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $this->foundationTenantId,
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 3,
        ]);
    }

    public function test_pos_sale_decrements_product_warehouse_stock_when_shift_has_warehouse_id_baseline(): void
    {
        $warehouse = \App\Models\Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Main DC',
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
            'selling_price' => 40,
            'purchase_price' => 15,
        ]);

        ProductWarehouseStock::create([
            'tenant_id' => $this->foundationTenantId,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
        ]);

        $this->actingAs($this->foundationUser)->postJson(route('pos.sale.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 4, 'price' => 40, 'discount_pct' => 0],
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 160,
            'shift_id' => $shift->id,
            'tax_rate' => 0,
            'discount' => 0,
        ])->assertOk();

        $this->assertSame(6, $product->fresh()->stock_quantity);

        $this->assertSame(
            6,
            (int) ProductWarehouseStock::where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->value('quantity')
        );

        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 4,
        ]);
    }

    public function test_pos_sale_creates_single_journal_entry_after_accounting_extraction(): void
    {
        $shift = \App\Models\PosShift::factory()->create([
            'user_id' => $this->foundationUser->id,
            'tenant_id' => $this->foundationTenantId,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 5,
            'selling_price' => 100,
        ]);

        $response = $this->actingAs($this->foundationUser)->postJson(route('pos.sale.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 1, 'price' => 100, 'discount_pct' => 0],
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 100,
            'shift_id' => $shift->id,
            'tax_rate' => 0,
            'discount' => 0,
        ]);

        $response->assertOk();
        $saleId = $response->json('sale_id');

        $journalRowsForSale = JournalEntry::query()
            ->where('source_type', Sale::class)
            ->where('source_id', $saleId)
            ->count();

        $this->assertSame(1, $journalRowsForSale);
    }

    public function test_sale_recorded_listener_is_idempotent(): void
    {
        $shift = \App\Models\PosShift::factory()->create([
            'user_id' => $this->foundationUser->id,
            'tenant_id' => $this->foundationTenantId,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 5,
            'selling_price' => 100,
        ]);

        $response = $this->actingAs($this->foundationUser)->postJson(route('pos.sale.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 1, 'price' => 100, 'discount_pct' => 0],
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 100,
            'shift_id' => $shift->id,
            'tax_rate' => 0,
            'discount' => 0,
        ]);

        $response->assertOk();
        $saleId = $response->json('sale_id');

        $this->assertSame(
            1,
            JournalEntry::query()->where('source_type', Sale::class)->where('source_id', $saleId)->count()
        );

        event(new SaleRecorded(saleId: $saleId, tenantId: $this->foundationTenantId));
        event(new SaleRecorded(saleId: $saleId, tenantId: $this->foundationTenantId));

        $this->assertSame(
            1,
            JournalEntry::query()->where('source_type', Sale::class)->where('source_id', $saleId)->count()
        );
    }

    public function test_db_after_commit_callbacks_not_run_when_transaction_rolls_back(): void
    {
        $ran = false;
        try {
            DB::transaction(function () use (&$ran) {
                DB::afterCommit(function () use (&$ran) {
                    $ran = true;
                });
                throw new \RuntimeException('forced rollback');
            });
        } catch (\RuntimeException) {
        }

        $this->assertFalse($ran);
    }

    public function test_pos_sale_idempotency_key_prevents_duplicate_sale(): void
    {
        if (! Schema::hasColumn('sales', 'client_idempotency_key')) {
            $this->markTestSkipped('sales.client_idempotency_key column not present in this schema.');
        }

        $shift = \App\Models\PosShift::factory()->create([
            'user_id' => $this->foundationUser->id,
            'tenant_id' => $this->foundationTenantId,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 20,
            'selling_price' => 10,
        ]);

        $payload = [
            'items' => [
                ['id' => $product->id, 'qty' => 2, 'price' => 10, 'discount_pct' => 0],
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 20,
            'shift_id' => $shift->id,
            'tax_rate' => 0,
            'discount' => 0,
            'client_idempotency_key' => 'idem-test-'.uniqid('', true),
        ];

        $first = $this->actingAs($this->foundationUser)->postJson(route('pos.sale.store'), $payload);
        $first->assertOk()->assertJson(['success' => true])->assertJsonMissing(['duplicate' => true]);

        $saleCountAfterFirst = Sale::where('tenant_id', $this->foundationTenantId)->count();

        $second = $this->actingAs($this->foundationUser)->postJson(route('pos.sale.store'), $payload);
        $second->assertOk()
            ->assertJson([
                'success' => true,
                'duplicate' => true,
                'sale_id' => $first->json('sale_id'),
            ]);

        $this->assertSame($saleCountAfterFirst, Sale::where('tenant_id', $this->foundationTenantId)->count());

        $stockAfter = $product->fresh()->stock_quantity;
        $this->assertSame(
            18,
            $stockAfter,
            'Duplicate response must not decrement stock again.'
        );

        $movementsForSale = StockMovement::query()
            ->where('tenant_id', $this->foundationTenantId)
            ->where('product_id', $product->id)
            ->where('type', 'out')
            ->count();
        $this->assertSame(1, $movementsForSale);

        $this->assertSame(
            1,
            JournalEntry::query()
                ->where('source_type', Sale::class)
                ->where('source_id', $first->json('sale_id'))
                ->count(),
            'Idempotent duplicate HTTP response must not create a second journal entry.'
        );
    }
}
