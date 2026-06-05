<?php

namespace App\Services;

use App\Models\Tenant;
use App\Modules\StorefrontBuilder\Services\StorefrontBuilderService;

class StorefrontProvisioningService
{
    public function __construct(
        private readonly StorefrontBuilderService $builderService,
        private readonly TenantManager $tenantManager,
    ) {}

    /**
     * Creates tenant-scoped storefront pages and attempts first publish for go-live readiness.
     */
    public function provisionForTenant(Tenant $tenant, bool $attemptPublish = true): void
    {
        $previous = $this->tenantManager->getCurrent();

        $this->tenantManager->setCurrent($tenant);

        try {
            $storefront = $this->builderService->getOrCreateDefaultStorefront();

            if ($attemptPublish && ! $storefront->published_version_id) {
                try {
                    $this->builderService->publish($storefront);
                } catch (\Throwable) {
                    // Draft storefront remains usable; operator can publish manually in builder.
                }
            }
        } finally {
            if ($previous) {
                $this->tenantManager->setCurrent($previous);
            } else {
                $this->tenantManager->clear();
            }
        }
    }
}
