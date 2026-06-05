<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Services\AccountingProvisioningService;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function __construct(
        protected AccountingProvisioningService $accountingProvisioning
    ) {}

    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            return;
        }

        foreach ($tenants as $tenant) {
            $this->accountingProvisioning->provisionForTenant($tenant);
        }
    }
}
