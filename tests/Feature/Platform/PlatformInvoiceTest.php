<?php

namespace Tests\Feature\Platform;

use App\Models\Plan;
use App\Models\PlatformInvoice;
use App\Models\Tenant;
use App\Services\Platform\PlatformInvoiceService;
use App\Services\TenantService;
use Database\Seeders\PlanSeeder;
use Database\Seeders\PlatformSettingSeeder;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AdministrationFixtures;
use Tests\TestCase;

#[Group('platform')]
class PlatformInvoiceTest extends TestCase
{
    use AdministrationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoleTemplates();
        $this->seed(PlanSeeder::class);
        $this->seed(PlatformSettingSeeder::class);
    }

    public function test_invoice_numbers_are_unique_and_monotonic(): void
    {
        $plan = Plan::where('slug', 'basic')->first();
        $service = app(PlatformInvoiceService::class);

        $tenantA = Tenant::create([
            'name' => 'Seq Tenant A',
            'slug' => 'seq-a-'.uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);
        $tenantB = Tenant::create([
            'name' => 'Seq Tenant B',
            'slug' => 'seq-b-'.uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $first = $service->createForTenant($tenantA, $plan);
        $second = $service->createForTenant($tenantB, $plan);

        $this->assertNotSame($first->number, $second->number);
        $this->assertSame(1, preg_match('/^INV-\d{6}$/', $first->number));
        $this->assertSame(1, preg_match('/^INV-\d{6}$/', $second->number));

        $firstSeq = (int) substr($first->number, 4);
        $secondSeq = (int) substr($second->number, 4);
        $this->assertSame($firstSeq + 1, $secondSeq);
    }

    public function test_upgrade_plan_creates_invoice(): void
    {
        $plan = Plan::where('slug', 'basic')->first();
        $tenant = Tenant::create([
            'name' => 'Invoice Tenant',
            'slug' => 'inv-'.uniqid(),
            'status' => 'trial',
            'is_active' => true,
        ]);

        $tenant->upgradePlan($plan);

        $this->assertDatabaseHas('platform_invoices', [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
        ]);
    }

    public function test_upgrade_same_paid_plan_does_not_duplicate_invoice_or_reset_payment(): void
    {
        $plan = Plan::where('slug', 'basic')->first();
        $tenant = Tenant::create([
            'name' => 'Paid Tenant',
            'slug' => 'paid-'.uniqid(),
            'status' => 'active',
            'is_active' => true,
            'plan_id' => $plan->id,
            'payment_status' => 'paid',
        ]);

        PlatformInvoice::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'number' => 'INV-TEST-001',
            'amount' => $plan->price,
            'currency' => 'USD',
            'status' => 'paid',
            'issued_at' => now(),
            'paid_at' => now(),
        ]);

        $invoiceCountBefore = PlatformInvoice::where('tenant_id', $tenant->id)->count();

        $tenant->upgradePlan($plan);

        $tenant->refresh();

        $this->assertSame('paid', $tenant->payment_status);
        $this->assertSame(
            $invoiceCountBefore,
            PlatformInvoice::where('tenant_id', $tenant->id)->count()
        );
    }

    public function test_upgrade_same_unpaid_plan_does_not_stack_pending_invoices(): void
    {
        $plan = Plan::where('slug', 'basic')->first();
        $tenant = Tenant::create([
            'name' => 'Pending Tenant',
            'slug' => 'pend-'.uniqid(),
            'status' => 'active',
            'is_active' => true,
            'plan_id' => $plan->id,
            'payment_status' => 'pending',
        ]);

        app(PlatformInvoiceService::class)->createForTenant($tenant, $plan);

        $tenant->upgradePlan($plan);

        $this->assertSame(
            1,
            PlatformInvoice::where('tenant_id', $tenant->id)
                ->where('plan_id', $plan->id)
                ->where('status', 'pending')
                ->count()
        );
    }

    public function test_platform_operator_can_mark_invoice_paid(): void
    {
        $this->withoutCsrf();
        $platform = $this->makePlatformOperator();
        $plan = Plan::first();
        $tenant = Tenant::create([
            'name' => 'Pay Tenant',
            'slug' => 'pay-'.uniqid(),
            'status' => 'active',
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        $invoice = app(PlatformInvoiceService::class)->createForTenant($tenant, $plan);

        $this->actingAs($platform)
            ->post(route('platform.invoices.mark-paid', $invoice))
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
    }

    public function test_mark_paid_activates_suspended_tenant_and_re_enables_users(): void
    {
        $plan = Plan::where('slug', 'basic')->first();
        $tenant = $this->createTenantWithClonedRoles([
            'name' => 'Suspended Pay Co',
            'slug' => 'susp-pay-'.uniqid(),
            'status' => 'active',
            'is_active' => true,
            'plan_id' => $plan->id,
            'payment_status' => 'pending',
        ]);

        $user = $this->makeTenantAdmin($tenant, ['is_active' => true]);

        app(TenantService::class)->suspendTenant($tenant);

        $tenant->refresh();
        $user->refresh();

        $this->assertFalse($tenant->allowsApplicationAccess());
        $this->assertSame('suspended', $tenant->status);
        $this->assertFalse($user->is_active);

        $invoice = app(PlatformInvoiceService::class)->createForTenant($tenant, $plan);
        app(PlatformInvoiceService::class)->markPaid($invoice);

        $tenant->refresh();
        $user->refresh();

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('paid', $tenant->payment_status);
        $this->assertTrue($tenant->allowsApplicationAccess());
        $this->assertSame('active', $tenant->status);
        $this->assertTrue($tenant->is_active);
        $this->assertNull($tenant->suspended_at);
        $this->assertTrue($user->is_active);
    }

    public function test_invoices_index_requires_platform_operator(): void
    {
        $platform = $this->makePlatformOperator();

        $this->actingAs($platform)
            ->get(route('platform.invoices.index'))
            ->assertOk();
    }
}
