<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class AccountingProvisioningService
{
    /**
     * Idempotent chart of accounts + AccountSetting mappings for a tenant.
     */
    public function provisionForTenant(Tenant $tenant): void
    {
        $coa = [
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset', 'parent_code' => null],
            ['code' => '1100', 'name' => 'Cash and Bank', 'type' => 'asset', 'parent_code' => '1000'],
            ['code' => '1110', 'name' => 'General Cash', 'type' => 'asset', 'parent_code' => '1100'],
            ['code' => '1120', 'name' => 'POS Cash Drawer', 'type' => 'asset', 'parent_code' => '1100'],
            ['code' => '1200', 'name' => 'Bank Account', 'type' => 'asset', 'parent_code' => '1100'],
            ['code' => '1210', 'name' => 'POS Card Clearing', 'type' => 'asset', 'parent_code' => '1200'],
            ['code' => '1220', 'name' => 'POS Bank Transfer', 'type' => 'asset', 'parent_code' => '1200'],
            ['code' => '1300', 'name' => 'Accounts Receivable', 'type' => 'asset', 'parent_code' => '1000'],
            ['code' => '1400', 'name' => 'Inventory', 'type' => 'asset', 'parent_code' => '1000'],
            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability', 'parent_code' => null],
            ['code' => '2100', 'name' => 'Accounts Payable', 'type' => 'liability', 'parent_code' => '2000'],
            ['code' => '2200', 'name' => 'Tax Payable (VAT/GST)', 'type' => 'liability', 'parent_code' => '2000'],
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity', 'parent_code' => null],
            ['code' => '3100', 'name' => 'Owner\'s Equity', 'type' => 'equity', 'parent_code' => '3000'],
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue', 'parent_code' => null],
            ['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'revenue', 'parent_code' => '4000'],
            ['code' => '4200', 'name' => 'Discounts Allowed', 'type' => 'revenue', 'parent_code' => '4000'],
            ['code' => '5000', 'name' => 'Expenses', 'type' => 'expense', 'parent_code' => null],
            ['code' => '5100', 'name' => 'Cost of Goods Sold (COGS)', 'type' => 'expense', 'parent_code' => '5000'],
            ['code' => '5200', 'name' => 'Operating Expenses', 'type' => 'expense', 'parent_code' => '5000'],
            ['code' => '5300', 'name' => 'Payment Processing Fees', 'type' => 'expense', 'parent_code' => '5000'],
            ['code' => '5400', 'name' => 'POS Cash Variance', 'type' => 'expense', 'parent_code' => '5000'],
        ];

        $mappings = [
            'pos_cash' => '1120',
            'pos_card' => '1210',
            'pos_bank' => '1220',
            'pos_variance' => '5400',
            'bank_payment' => '1200',
            'sales_revenue' => '4100',
            'sales_discount' => '4200',
            'tax_payable' => '2200',
            'cogs_account' => '5100',
            'inventory_account' => '1400',
            'supplier_payable' => '2100',
            'customer_receivable' => '1300',
            'cash_customer' => '1110',
            'refund_account' => '4100',
            'payment_fees' => '5300',
        ];

        DB::transaction(function () use ($tenant, $coa, $mappings) {
            $accountMap = [];

            foreach ($coa as $item) {
                $parent = $item['parent_code'] ? ($accountMap[$item['parent_code']] ?? null) : null;

                $account = Account::withoutGlobalScopes()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'code' => $item['code'],
                    ],
                    [
                        'name' => $item['name'],
                        'type' => $item['type'],
                        'parent_id' => $parent?->id,
                        'is_active' => true,
                    ]
                );

                $accountMap[$item['code']] = $account;
            }

            foreach ($mappings as $key => $code) {
                if (! isset($accountMap[$code])) {
                    continue;
                }

                AccountSetting::withoutGlobalScopes()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'key' => $key,
                    ],
                    [
                        'account_id' => $accountMap[$code]->id,
                    ]
                );
            }
        });
    }
}
