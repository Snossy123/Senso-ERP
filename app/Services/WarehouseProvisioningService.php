<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Warehouse;

class WarehouseProvisioningService
{
    /**
     * Idempotent default warehouse for inventory, PO, and optional POS shift binding.
     */
    public function ensureDefaultWarehouseForTenant(Tenant $tenant): Warehouse
    {
        return Warehouse::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Main Warehouse',
            ],
            [
                'location' => 'Default location',
                'manager_name' => $tenant->name,
                'phone' => null,
                'is_active' => true,
            ]
        );
    }
}
