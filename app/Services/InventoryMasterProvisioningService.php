<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Support\Str;

class InventoryMasterProvisioningService
{
    public function provisionForTenant(Tenant $tenant): void
    {
        $tenantId = (int) $tenant->id;

        Category::withoutGlobalScopes()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'slug' => 'general',
            ],
            [
                'name' => 'General',
                'is_active' => true,
            ]
        );

        Unit::withoutGlobalScopes()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'name' => 'Piece',
            ],
            [
                'short_name' => 'pcs',
                'is_active' => true,
            ]
        );
    }
}
