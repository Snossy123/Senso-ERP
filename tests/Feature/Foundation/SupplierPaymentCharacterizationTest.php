<?php

namespace Tests\Feature\Foundation;

use App\Models\JournalEntry;
use App\Models\Permission;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

class SupplierPaymentCharacterizationTest extends TestCase
{
    use FoundationBaselineFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundationTenantAndStaff();

        Permission::firstOrCreate(
            ['slug' => 'accounting.disburse'],
            ['name' => 'Accounting Disburse', 'group' => 'accounting', 'description' => 'Pay suppliers']
        );
        $this->foundationUser->role->givePermissionTo('accounting.disburse');
    }

    public function test_supplier_payment_posts_ap_relief_journal(): void
    {
        $warehouse = Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'WH Pay',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Pay Supplier',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 5,
            'purchase_price' => 20,
        ]);

        $this->actingAs($this->foundationUser)->post(route('inventory.purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => 20],
            ],
        ]);

        $order = PurchaseOrder::where('supplier_id', $supplier->id)->latest('id')->first();
        $this->actingAs($this->foundationUser)->post(route('inventory.purchase-orders.receive', $order));

        $order->refresh();
        $this->assertSame('received', $order->status);

        $grnJournal = JournalEntry::query()
            ->where('source_type', PurchaseOrder::class)
            ->where('source_id', $order->id)
            ->count();
        $this->assertSame(1, $grnJournal);

        $response = $this->actingAs($this->foundationUser)->post(route('inventory.purchase-orders.pay', $order), [
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertRedirect(route('inventory.purchase-orders.show', $order));

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);

        $payment = SupplierPayment::where('purchase_order_id', $order->id)->first();
        $this->assertNotNull($payment);

        $payJournal = JournalEntry::query()
            ->where('source_type', SupplierPayment::class)
            ->where('source_id', $payment->id)
            ->count();

        $this->assertSame(1, $payJournal);
    }
}
