<?php

namespace App\Services;

use App\Models\Tenant;
use App\Services\Shipping\ShippingProvisioningService;

/**
 * Orchestrates all idempotent provisioning steps for a new or existing tenant.
 */
class TenantProvisioningService
{
    public function __construct(
        private readonly AccountingProvisioningService $accountingProvisioning,
        private readonly WarehouseProvisioningService $warehouseProvisioning,
        private readonly InventoryMasterProvisioningService $inventoryMasterProvisioning,
        private readonly FinancialPeriodProvisioningService $financialPeriodProvisioning,
        private readonly StorefrontProvisioningService $storefrontProvisioning,
        private readonly ShippingProvisioningService $shippingProvisioning,
    ) {}

    public function provision(Tenant $tenant, bool $publishStorefront = true): void
    {
        $this->accountingProvisioning->provisionForTenant($tenant);
        $this->warehouseProvisioning->ensureDefaultWarehouseForTenant($tenant);
        $this->inventoryMasterProvisioning->provisionForTenant($tenant);
        $this->financialPeriodProvisioning->ensureCurrentYearPeriodForTenant($tenant);
        $this->storefrontProvisioning->provisionForTenant($tenant, $publishStorefront);
        $this->shippingProvisioning->provisionForTenant($tenant);
    }
}
