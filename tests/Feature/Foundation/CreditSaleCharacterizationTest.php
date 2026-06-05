<?php

namespace Tests\Feature\Foundation;

use App\Application\Sales\RecordCustomerPaymentService;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\Sale;
use App\Models\Tenant;
use App\Services\Accounting\TenantAccountingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

class CreditSaleCharacterizationTest extends TestCase
{
    use FoundationBaselineFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundationTenantAndStaff();
    }

    public function test_credit_pos_sale_posts_ar_and_collection_clears_it(): void
    {
        $warehouse = \App\Models\Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Credit WH',
            'is_active' => true,
        ]);

        $shift = \App\Models\PosShift::factory()->create([
            'user_id' => $this->foundationUser->id,
            'tenant_id' => $this->foundationTenantId,
            'warehouse_id' => $warehouse->id,
        ]);

        $customer = Customer::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Credit Customer',
            'email' => 'credit-'.uniqid('', true).'@test.com',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 10,
            'selling_price' => 50,
        ]);

        ProductWarehouseStock::create([
            'tenant_id' => $this->foundationTenantId,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
        ]);

        $response = $this->actingAs($this->foundationUser)->postJson(route('pos.sale.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 1, 'price' => 50, 'discount_pct' => 0],
            ],
            'payment_method' => 'credit',
            'customer_id' => $customer->id,
            'amount_tendered' => 0,
            'shift_id' => $shift->id,
            'tax_rate' => 0,
            'discount' => 0,
        ]);

        $response->assertOk();
        $sale = Sale::findOrFail($response->json('sale_id'));
        $this->assertSame('credit', $sale->payment_method);
        $this->assertSame('pending', $sale->payment_status);

        $saleJe = JournalEntry::query()
            ->where('source_type', Sale::class)
            ->where('source_id', $sale->id)
            ->count();
        $this->assertSame(1, $saleJe);

        app(RecordCustomerPaymentService::class)->recordForSale($sale, [
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ], $this->foundationUser->id);

        $sale->refresh();
        $this->assertSame('paid', $sale->payment_status);

        $payment = CustomerPayment::withoutGlobalScopes()
            ->where('sale_id', $sale->id)
            ->first();
        $this->assertNotNull($payment);

        $receiptJe = JournalEntry::query()
            ->where('source_type', CustomerPayment::class)
            ->where('source_id', $payment->id)
            ->count();
        $this->assertSame(1, $receiptJe);
    }

    public function test_card_sale_with_fee_posts_net_card_and_fee_entry(): void
    {
        $tenant = Tenant::find($this->foundationTenantId);
        TenantAccountingSettings::setCardFeePercent($tenant, 2.5);

        $card = Account::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Card Clearing',
            'code' => 'FB1050',
            'type' => 'asset',
            'is_active' => true,
        ]);
        $fees = Account::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Payment Fees',
            'code' => 'FB5100',
            'type' => 'expense',
            'is_active' => true,
        ]);
        AccountSetting::create(['tenant_id' => $this->foundationTenantId, 'key' => 'pos_card', 'account_id' => $card->id]);
        AccountSetting::create(['tenant_id' => $this->foundationTenantId, 'key' => 'payment_fees', 'account_id' => $fees->id]);

        $warehouse = \App\Models\Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Fee WH',
            'is_active' => true,
        ]);

        $shift = \App\Models\PosShift::factory()->create([
            'user_id' => $this->foundationUser->id,
            'tenant_id' => $this->foundationTenantId,
            'warehouse_id' => $warehouse->id,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 5,
            'selling_price' => 100,
        ]);

        ProductWarehouseStock::create([
            'tenant_id' => $this->foundationTenantId,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
        ]);

        $response = $this->actingAs($this->foundationUser)->postJson(route('pos.sale.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 1, 'price' => 100, 'discount_pct' => 0],
            ],
            'payment_method' => 'card',
            'amount_tendered' => 100,
            'shift_id' => $shift->id,
            'tax_rate' => 0,
            'discount' => 0,
        ]);

        $response->assertOk();
        $sale = Sale::findOrFail($response->json('sale_id'));
        $this->assertEqualsWithDelta(2.5, (float) $sale->fresh()->payment_fee_amount, 0.01);

        $feeJe = JournalEntry::query()
            ->where('reference', 'FEE-SALE-'.$sale->sale_number)
            ->count();
        $this->assertSame(1, $feeJe);
    }
}
