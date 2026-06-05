<?php

namespace Tests\Feature\Sales;

use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

class SalesInvoiceTest extends TestCase
{
    use FoundationBaselineFixtures, RefreshDatabase;

    protected Customer $customer;

    protected Product $product;

    protected Warehouse $warehouse;

    protected Account $arAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundationTenantAndStaff();

        $this->arAccount = Account::withoutGlobalScopes()
            ->where('tenant_id', $this->foundationTenantId)
            ->where('code', 'FB1300')
            ->firstOrFail();

        AccountSetting::updateOrCreate(
            ['tenant_id' => $this->foundationTenantId, 'key' => 'pos_bank'],
            ['account_id' => $this->foundationCashAccount->id]
        );

        foreach ([
            'sales_invoices.view', 'sales_invoices.create', 'sales_invoices.edit',
            'sales_invoices.confirm', 'sales_invoices.cancel', 'sales_invoices.pay',
        ] as $slug) {
            \App\Models\Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'group' => 'sales_invoices', 'description' => $slug]
            );
            $this->foundationUser->role->givePermissionTo($slug);
        }

        $this->customer = Customer::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Invoice Customer',
            'email' => 'inv@test.com',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Main WH',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'tenant_id' => $this->foundationTenantId,
            'sku' => 'INV-SKU-1',
            'name' => 'Invoice Product',
            'selling_price' => 100,
            'purchase_price' => 40,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);
    }

    protected function linePayload(): array
    {
        return [
            [
                'product_id' => $this->product->id,
                'quantity' => 2,
                'unit_price' => 100,
                'discount' => 0,
                'tax_rate' => 0,
            ],
        ];
    }

    public function test_draft_confirm_reduces_stock_and_posts_ar_journal(): void
    {
        $response = $this->actingAs($this->foundationUser)
            ->withHeaders($this->tenantHeader())
            ->post(route('sales.invoices.store'), [
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
                'payment_term' => 'credit',
                'lines' => $this->linePayload(),
                'confirm_now' => 1,
            ]);

        $response->assertRedirect(route('sales.invoices.index'));
        $invoice = SalesInvoice::first();
        $this->assertSame('confirmed', $invoice->status);
        $this->assertNotNull($invoice->invoice_number);
        $this->assertStringNotContainsString('DRAFT', $invoice->invoice_number);

        $this->product->refresh();
        $this->assertSame(48, (int) $this->product->stock_quantity);

        $this->assertTrue(
            JournalEntry::where('source_type', SalesInvoice::class)
                ->where('source_id', $invoice->id)
                ->exists()
        );
    }

    public function test_partial_payment_updates_balance(): void
    {
        $invoice = $this->createConfirmedInvoice(200);

        $this->actingAs($this->foundationUser)
            ->withHeaders($this->tenantHeader())
            ->post(route('sales.invoices.payments.store', $invoice), [
                'amount' => 80,
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame('partial', $invoice->payment_status);
        $this->assertEquals(80, (float) $invoice->paid_amount);
        $this->assertEquals(120, (float) $invoice->balance_due);
    }

    public function test_installment_schedule_and_pay_single_installment(): void
    {
        $invoice = $this->createConfirmedInvoice(300, 'installment', [
            'down_payment' => 0,
            'installment_count' => 3,
            'interval_days' => 30,
            'first_due_date' => now()->addMonth()->toDateString(),
        ]);

        $this->assertCount(3, $invoice->installments);

        $first = $invoice->installments()->orderBy('sequence')->first();
        $payAmount = $first->remainingAmount();

        $this->actingAs($this->foundationUser)
            ->withHeaders($this->tenantHeader())
            ->post(route('sales.invoices.payments.store', $invoice), [
                'amount' => $payAmount,
                'payment_method' => 'cash',
                'invoice_installment_id' => $first->id,
            ])
            ->assertRedirect();

        $first->refresh();
        $this->assertSame('paid', $first->status);
    }

    public function test_installment_down_payment_cannot_exceed_total_on_confirm(): void
    {
        $response = $this->actingAs($this->foundationUser)
            ->withHeaders($this->tenantHeader())
            ->from(route('sales.invoices.create'))
            ->post(route('sales.invoices.store'), [
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
                'payment_term' => 'installment',
                'lines' => $this->linePayload(),
                'down_payment' => 500,
                'installment_count' => 3,
                'interval_days' => 30,
                'first_due_date' => now()->addMonth()->toDateString(),
                'confirm_now' => 1,
            ]);

        $response->assertRedirect(route('sales.invoices.create'));
        $response->assertSessionHasErrors('down_payment');
        $this->assertSame(0, SalesInvoice::count());
    }

    public function test_installment_down_payment_cannot_exceed_total_when_confirming_draft_from_show(): void
    {
        $this->actingAs($this->foundationUser)
            ->withHeaders($this->tenantHeader())
            ->post(route('sales.invoices.store'), [
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
                'payment_term' => 'installment',
                'lines' => $this->linePayload(),
                'confirm_now' => 0,
            ]);

        $invoice = SalesInvoice::first();
        $this->assertSame('draft', $invoice->status);

        $response = $this->actingAs($this->foundationUser)
            ->withHeaders($this->tenantHeader())
            ->from(route('sales.invoices.show', $invoice))
            ->post(route('sales.invoices.confirm', $invoice), [
                'down_payment' => 500,
                'installment_count' => 3,
                'interval_days' => 30,
                'first_due_date' => now()->addMonth()->toDateString(),
            ]);

        $response->assertRedirect(route('sales.invoices.show', $invoice));
        $response->assertSessionHasErrors('down_payment');
        $this->assertSame('draft', $invoice->fresh()->status);
    }

    public function test_invoice_numbers_are_unique_per_tenant(): void
    {
        $this->createConfirmedInvoice(100);
        $second = $this->createConfirmedInvoice(50);

        $this->assertNotEquals(
            SalesInvoice::orderBy('id')->first()->invoice_number,
            $second->invoice_number
        );
    }

    protected function createConfirmedInvoice(
        float $unitPrice,
        string $term = 'credit',
        ?array $installmentPlan = null
    ): SalesInvoice {
        $this->actingAs($this->foundationUser)
            ->withHeaders($this->tenantHeader())
            ->post(route('sales.invoices.store'), [
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
                'payment_term' => $term,
                'lines' => [[
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => $unitPrice,
                    'discount' => 0,
                    'tax_rate' => 0,
                ]],
                'confirm_now' => 0,
            ]);

        $invoice = SalesInvoice::latest('id')->first();

        $payload = $installmentPlan ?? [];
        $this->actingAs($this->foundationUser)
            ->withHeaders($this->tenantHeader())
            ->post(route('sales.invoices.confirm', $invoice), $payload);

        return $invoice->fresh(['installments']);
    }
}
