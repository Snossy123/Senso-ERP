<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Tenant;

class BranchProvisioningService
{
    /**
     * Idempotent default branches for a tenant (same codes can exist on other tenants).
     */
    public function ensureDefaultBranchesForTenant(Tenant $tenant): void
    {
        $defaults = [
            ['name' => 'Main Branch', 'code' => 'HQ', 'address' => '123 Main Street, Downtown', 'phone' => '555-0100', 'email' => 'hq@company.com'],
            ['name' => 'Branch North', 'code' => 'NORTH', 'address' => '456 North Avenue, Uptown', 'phone' => '555-0200', 'email' => 'north@company.com'],
            ['name' => 'Branch South', 'code' => 'SOUTH', 'address' => '789 South Blvd, Suburb', 'phone' => '555-0300', 'email' => 'south@company.com'],
        ];

        foreach ($defaults as $row) {
            $code = strtoupper($row['code']);
            Branch::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $code],
                [
                    'name' => $row['name'],
                    'address' => $row['address'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'is_active' => true,
                ]
            );
        }
    }
}
