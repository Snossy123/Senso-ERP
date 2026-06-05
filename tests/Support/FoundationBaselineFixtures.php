<?php

namespace Tests\Support;

use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;

trait FoundationBaselineFixtures
{
    protected Tenant $foundationTenant;

    protected User $foundationUser;

    protected int $foundationTenantId;

    protected Account $foundationCashAccount;

    protected Account $foundationRevenueAccount;

    protected Account $foundationTaxAccount;

    protected Account $foundationVarianceAccount;

    protected function seedFoundationTenantAndStaff(): void
    {
        $plan = Plan::firstOrCreate(
            ['slug' => 'foundation-baseline-plan'],
            [
                'name' => 'Foundation Baseline',
                'description' => 'Automated characterization tests',
                'price' => 0,
                'billing_cycle' => 'monthly',
                'max_users' => 100,
                'max_products' => 10000,
                'max_orders_per_month' => 10000,
                'features' => ['pos', 'inventory', 'reports', 'multi_warehouse'],
                'sort_order' => 99,
                'is_active' => true,
                'is_featured' => false,
            ]
        );

        $this->foundationTenant = Tenant::create([
            'name' => 'Foundation Baseline Co',
            'slug' => 'found-bl-'.str_replace('.', '', uniqid('', true)),
            'status' => 'active',
            'is_active' => true,
            'plan_id' => $plan->id,
            'trial_ends_at' => now()->addMonth(),
            'currency' => 'USD',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $this->foundationTenantId = $this->foundationTenant->id;

        Permission::firstOrCreate(
            ['slug' => 'pos.refund'],
            ['name' => 'POS Refund', 'group' => 'pos', 'description' => 'Refund sales']
        );
        Permission::firstOrCreate(
            ['slug' => 'pos.discount'],
            ['name' => 'POS Discount', 'group' => 'pos', 'description' => 'Apply discounts']
        );

        $role = Role::create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Baseline Cashier',
            'slug' => 'baseline-cashier',
            'guard_name' => 'web',
            'is_active' => true,
        ]);
        $role->givePermissionTo(['pos.refund', 'pos.discount']);

        $this->foundationUser = User::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'role_id' => $role->id,
        ]);

        $tid = $this->foundationTenantId;

        $this->foundationCashAccount = Account::create([
            'tenant_id' => $tid, 'name' => 'POS Cash', 'code' => 'FB1001', 'type' => 'asset', 'is_active' => true,
        ]);
        $this->foundationRevenueAccount = Account::create([
            'tenant_id' => $tid, 'name' => 'Sales Revenue', 'code' => 'FB4001', 'type' => 'revenue', 'is_active' => true,
        ]);
        $this->foundationTaxAccount = Account::create([
            'tenant_id' => $tid, 'name' => 'Tax Payable', 'code' => 'FB2001', 'type' => 'liability', 'is_active' => true,
        ]);
        $this->foundationVarianceAccount = Account::create([
            'tenant_id' => $tid, 'name' => 'POS Variance', 'code' => 'FB5001', 'type' => 'expense', 'is_active' => true,
        ]);

        AccountSetting::create(['tenant_id' => $tid, 'key' => 'pos_cash', 'account_id' => $this->foundationCashAccount->id]);
        AccountSetting::create(['tenant_id' => $tid, 'key' => 'sales_revenue', 'account_id' => $this->foundationRevenueAccount->id]);
        AccountSetting::create(['tenant_id' => $tid, 'key' => 'tax_payable', 'account_id' => $this->foundationTaxAccount->id]);
        AccountSetting::create(['tenant_id' => $tid, 'key' => 'pos_variance', 'account_id' => $this->foundationVarianceAccount->id]);

        $inventory = Account::create([
            'tenant_id' => $tid, 'name' => 'Inventory', 'code' => 'FB1200', 'type' => 'asset', 'is_active' => true,
        ]);
        $payable = Account::create([
            'tenant_id' => $tid, 'name' => 'AP', 'code' => 'FB2100', 'type' => 'liability', 'is_active' => true,
        ]);
        $bank = Account::create([
            'tenant_id' => $tid, 'name' => 'Bank', 'code' => 'FB1100', 'type' => 'asset', 'is_active' => true,
        ]);
        AccountSetting::create(['tenant_id' => $tid, 'key' => 'inventory_account', 'account_id' => $inventory->id]);
        AccountSetting::create(['tenant_id' => $tid, 'key' => 'supplier_payable', 'account_id' => $payable->id]);
        AccountSetting::create(['tenant_id' => $tid, 'key' => 'bank_payment', 'account_id' => $bank->id]);

        $ar = Account::create([
            'tenant_id' => $tid, 'name' => 'AR', 'code' => 'FB1300', 'type' => 'asset', 'is_active' => true,
        ]);
        AccountSetting::create(['tenant_id' => $tid, 'key' => 'customer_receivable', 'account_id' => $ar->id]);
    }

    protected function tenantHeader(): array
    {
        return ['X-Tenant-ID' => (string) $this->foundationTenantId];
    }
}
