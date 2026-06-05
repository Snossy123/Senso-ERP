<?php

namespace Tests\Unit\Accounting;

use App\Models\JournalEntry;
use App\Models\Plan;
use App\Models\PlanModule;
use App\Models\Tenant;
use App\Services\Accounting\JournalEntryLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_assert_can_create_throws_when_monthly_limit_reached(): void
    {
        $plan = Plan::create([
            'name' => 'Limit Plan',
            'slug' => 'limit-plan-'.uniqid(),
            'price' => 0,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        PlanModule::create([
            'plan_id' => $plan->id,
            'module_key' => 'accounting',
            'enabled' => true,
            'limits' => ['journal_entries_per_month' => 1],
        ]);

        $tenant = Tenant::create([
            'name' => 'Limit Tenant',
            'slug' => 'limit-tenant-'.uniqid(),
            'status' => 'active',
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        JournalEntry::create([
            'tenant_id' => $tenant->id,
            'reference' => 'JE-00001',
            'date' => now()->toDateString(),
            'description' => 'Test',
            'status' => 'posted',
        ]);

        $service = app(JournalEntryLimitService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Monthly journal entry limit');

        $service->assertCanCreate($tenant);
    }
}
