<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Illuminate\Console\Command;

class ProvisionTenantsCommand extends Command
{
    protected $signature = 'tenants:provision
                            {--tenant= : Tenant ID to provision (default: all tenants)}';

    protected $description = 'Idempotent go-live provisioning: COA, warehouse, inventory masters, financial period, storefront';

    public function handle(TenantProvisioningService $provisioning): int
    {
        $query = Tenant::query()->orderBy('id');

        if ($id = $this->option('tenant')) {
            $query->whereKey($id);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants matched.');

            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $this->info("Provisioning tenant #{$tenant->id} ({$tenant->name})...");
            $provisioning->provision($tenant);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
