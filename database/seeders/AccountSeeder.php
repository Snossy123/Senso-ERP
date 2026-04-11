<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all tenants or create a default if none exist
        $tenants = Tenant::all();
        
        if ($tenants->isEmpty()) {
            // If No tenants, we can't seed accounts meaningfully in a multi-tenant app
            // But for testing purposes, we might want to skip or handle global accounts
            return;
        }

        foreach ($tenants as $tenant) {
            $this->seedForTenant($tenant);
        }
    }

    private function seedForTenant($tenant)
    {
        $coa = [
            // ASSETS
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset', 'parent_code' => null],
            ['code' => '1100', 'name' => 'Cash and Bank', 'type' => 'asset', 'parent_code' => '1000'],
            ['code' => '1110', 'name' => 'General Cash', 'type' => 'asset', 'parent_code' => '1100'],
            ['code' => '1120', 'name' => 'POS Cash Drawer', 'type' => 'asset', 'parent_code' => '1100'],
            ['code' => '1200', 'name' => 'Bank Account', 'type' => 'asset', 'parent_code' => '1100'],
            ['code' => '1210', 'name' => 'POS Card Clearing', 'type' => 'asset', 'parent_code' => '1200'],
            ['code' => '1220', 'name' => 'POS Bank Transfer', 'type' => 'asset', 'parent_code' => '1200'],
            ['code' => '1300', 'name' => 'Accounts Receivable', 'type' => 'asset', 'parent_code' => '1000'],
            ['code' => '1400', 'name' => 'Inventory', 'type' => 'asset', 'parent_code' => '1000'],

            // LIABILITIES
            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability', 'parent_code' => null],
            ['code' => '2100', 'name' => 'Accounts Payable', 'type' => 'liability', 'parent_code' => '2000'],
            ['code' => '2200', 'name' => 'Tax Payable (VAT/GST)', 'type' => 'liability', 'parent_code' => '2000'],

            // EQUITY
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity', 'parent_code' => null],
            ['code' => '3100', 'name' => 'Owner\'s Equity', 'type' => 'equity', 'parent_code' => '3000'],

            // REVENUE
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue', 'parent_code' => null],
            ['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'revenue', 'parent_code' => '4000'],
            ['code' => '4200', 'name' => 'Discounts Allowed', 'type' => 'revenue', 'parent_code' => '4000'],

            // EXPENSES
            ['code' => '5000', 'name' => 'Expenses', 'type' => 'expense', 'parent_code' => null],
            ['code' => '5100', 'name' => 'Cost of Goods Sold (COGS)', 'type' => 'expense', 'parent_code' => '5000'],
            ['code' => '5200', 'name' => 'Operating Expenses', 'type' => 'expense', 'parent_code' => '5000'],
            ['code' => '5300', 'name' => 'Payment Processing Fees', 'type' => 'expense', 'parent_code' => '5000'],
            ['code' => '5400', 'name' => 'POS Cash Variance', 'type' => 'expense', 'parent_code' => '5000'],
        ];

        $accountMap = [];

        DB::beginTransaction();
        try {
            // 1. Create Accounts
            foreach ($coa as $item) {
                $parent = $item['parent_code'] ? ($accountMap[$item['parent_code']] ?? null) : null;
                
                $account = Account::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'code'      => $item['code'],
                    ],
                    [
                        'name'      => $item['name'],
                        'type'      => $item['type'],
                        'parent_id' => $parent?->id,
                        'is_active' => true,
                    ]
                );
                
                $accountMap[$item['code']] = $account;
            }

            // 2. Define Mappings
            $mappings = [
                'pos_cash'            => '1120',
                'pos_card'            => '1210',
                'pos_bank'            => '1220',
                'pos_variance'        => '5400',
                'bank_payment'        => '1200',
                'sales_revenue'       => '4100',
                'sales_discount'      => '4200',
                'tax_payable'         => '2200',
                'cogs_account'        => '5100',
                'inventory_account'   => '1400',
                'supplier_payable'    => '2100',
                'customer_receivable' => '1300',
                'cash_customer'       => '1110',
                'refund_account'      => '4100', // Usually reverses sales revenue
                'payment_fees'        => '5300',
            ];

            // 3. Seed Settings
            foreach ($mappings as $key => $code) {
                if (isset($accountMap[$code])) {
                    AccountSetting::updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'key'       => $key,
                        ],
                        [
                            'account_id' => $accountMap[$code]->id,
                        ]
                    );
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
