<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class POSTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected int $tenantId;

    protected Account $cashAccount;

    protected Account $revenueAccount;

    protected Account $taxAccount;

    protected Account $varianceAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create([
            'name' => 'POS Test Co',
            'slug' => 'pos-test-'.str_replace('.', '', uniqid('', true)),
            'status' => 'active',
            'is_active' => true,
            'trial_ends_at' => now()->addMonth(),
            'currency' => 'USD',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);
        $this->tenantId = $tenant->id;

        $this->user = User::factory()->create(['tenant_id' => $this->tenantId]);

        $tid = $this->tenantId;

        // Create Required Accounts for POS Mapping
        $this->cashAccount = Account::create([
            'tenant_id' => $tid, 'name' => 'POS Cash', 'code' => '1001', 'type' => 'asset', 'is_active' => true,
        ]);
        $this->revenueAccount = Account::create([
            'tenant_id' => $tid, 'name' => 'Sales Revenue', 'code' => '4001', 'type' => 'revenue', 'is_active' => true,
        ]);
        $this->taxAccount = Account::create([
            'tenant_id' => $tid, 'name' => 'Tax Payable', 'code' => '2001', 'type' => 'liability', 'is_active' => true,
        ]);
        $this->varianceAccount = Account::create([
            'tenant_id' => $tid, 'name' => 'POS Variance', 'code' => '5001', 'type' => 'expense', 'is_active' => true,
        ]);

        // Create Account Mappings
        \App\Models\AccountSetting::create(['tenant_id' => $tid, 'key' => 'pos_cash', 'account_id' => $this->cashAccount->id]);
        \App\Models\AccountSetting::create(['tenant_id' => $tid, 'key' => 'sales_revenue', 'account_id' => $this->revenueAccount->id]);
        \App\Models\AccountSetting::create(['tenant_id' => $tid, 'key' => 'tax_payable', 'account_id' => $this->taxAccount->id]);
        \App\Models\AccountSetting::create(['tenant_id' => $tid, 'key' => 'pos_variance', 'account_id' => $this->varianceAccount->id]);
    }

    /** @test */
    public function a_user_can_open_a_pos_shift()
    {
        $response = $this->actingAs($this->user)->postJson(route('pos.shift.open'), [
            'opening_float' => 150.00,
            'terminal_id' => 'POS-Testing',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pos_shifts', [
            'user_id' => $this->user->id,
            'status' => 'open',
            'opening_float' => 150.00,
        ]);
    }

    /** @test */
    public function a_sale_requires_an_active_shift()
    {
        // No shift opened
        $product = Product::factory()->create(['stock_quantity' => 10, 'selling_price' => 100, 'tenant_id' => $this->tenantId]);

        $response = $this->actingAs($this->user)->postJson(route('pos.sale.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 1, 'price' => 100, 'discount_pct' => 0],
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 100,
            'shift_id' => 999, // Non-existent shift
        ]);

        // It fails because shift_id validation or logic
        $response->assertStatus(422);
    }

    /** @test */
    public function a_user_can_process_a_sale_with_an_active_shift_and_creates_journal_entry()
    {
        $shift = PosShift::factory()->create(['user_id' => $this->user->id, 'tenant_id' => $this->tenantId]);
        $product = Product::factory()->create(['stock_quantity' => 10, 'selling_price' => 100, 'tenant_id' => $this->tenantId]);

        $response = $this->actingAs($this->user)->postJson(route('pos.sale.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 2, 'price' => 100, 'discount_pct' => 0],
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 200,
            'shift_id' => $shift->id,
            'tax_rate' => 5, // 5% tax
            'discount' => 0,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Check Sale creation
        $this->assertDatabaseHas('sales', [
            'user_id' => $this->user->id,
            'shift_id' => $shift->id,
            'total' => 210, // 200 + 5% tax
        ]);

        // Check Journal Entry
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'App\Models\Sale',
            'tenant_id' => $this->tenantId,
        ]);

        // Check Journal Entry Lines (Debit Cash 210, Credit Revenue 200, Credit Tax 10)
        $this->assertDatabaseHas('journal_entry_lines', ['account_id' => $this->cashAccount->id, 'debit' => 210]);
        $this->assertDatabaseHas('journal_entry_lines', ['account_id' => $this->revenueAccount->id, 'credit' => 200]);
        $this->assertDatabaseHas('journal_entry_lines', ['account_id' => $this->taxAccount->id, 'credit' => 10]);
    }

    /** @test */
    public function closing_a_shift_with_variance_creates_journal_entry()
    {
        $shift = PosShift::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenantId,
            'opening_float' => 100,
            'status' => 'open',
        ]);

        // Process a $50 cash sale
        $product = Product::factory()->create(['stock_quantity' => 10, 'selling_price' => 50, 'tenant_id' => $this->tenantId]);
        $this->actingAs($this->user)->postJson(route('pos.sale.store'), [
            'items' => [['id' => $product->id, 'qty' => 1, 'price' => 50, 'discount_pct' => 0]],
            'payment_method' => 'cash',
            'shift_id' => $shift->id,
        ]);

        // Expected Cash: 100 (float) + 50 (sale) = 150
        // Actual Cash: 140 (Shortage of 10)
        $response = $this->actingAs($this->user)->postJson(route('pos.shift.close', $shift), [
            'closing_float' => 140,
            'notes' => 'Some cash missing',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(-10, $shift->fresh()->variance);

        // Check Journal Entry for Variance
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'App\Models\PosShift',
            'source_id' => $shift->id,
            'tenant_id' => $this->tenantId,
        ]);

        // Check Lines: Debit Variance 10, Credit Cash 10
        $this->assertDatabaseHas('journal_entry_lines', ['account_id' => $this->varianceAccount->id, 'debit' => 10]);
        $this->assertDatabaseHas('journal_entry_lines', ['account_id' => $this->cashAccount->id, 'credit' => 10]);
    }

    /** @test */
    public function a_user_can_refund_a_sale_and_reverses_accounting()
    {
        $shift = PosShift::factory()->create(['user_id' => $this->user->id, 'tenant_id' => $this->tenantId]);
        $product = Product::factory()->create(['stock_quantity' => 10, 'selling_price' => 100, 'tenant_id' => $this->tenantId]);

        // Manual Create Sale with Journal Entry (simulated)
        $sale = Sale::create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->user->id,
            'shift_id' => $shift->id,
            'sale_number' => 'TEST-REFUND-01',
            'total' => 100,
            'subtotal' => 100,
            'tax_amount' => 0,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);
        $sale->items()->create([
            'tenant_id' => $this->tenantId, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100, 'total' => 100,
        ]);

        // Refund request
        $response = $this->actingAs($this->user)->postJson(route('pos.sales.refund', $sale), [
            'amount' => 100,
            'reason' => 'Defective product',
            'method' => 'cash',
            'restock' => true,
        ]);

        $response->assertStatus(200);

        // Check Journal Entry for Refund
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'App\Models\SaleRefund',
            'tenant_id' => $this->tenantId,
        ]);

        // Check Lines: Debit Revenue 100, Credit Cash 100
        $this->assertDatabaseHas('journal_entry_lines', ['account_id' => $this->revenueAccount->id, 'debit' => 100]);
        $this->assertDatabaseHas('journal_entry_lines', ['account_id' => $this->cashAccount->id, 'credit' => 100]);
    }
}
