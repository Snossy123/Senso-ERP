<?php

namespace Tests\Feature\Platform;

use App\Models\Plan;
use App\Models\PlatformInvoice;
use App\Models\Tenant;
use App\Services\Platform\PlatformInvoiceService;
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

    public function test_invoices_index_requires_platform_operator(): void
    {
        $platform = $this->makePlatformOperator();

        $this->actingAs($platform)
            ->get(route('platform.invoices.index'))
            ->assertOk();
    }
}
