<?php

namespace App\Services;

use App\Models\AccountSetting;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Modules\StorefrontBuilder\Models\Storefront;
use Illuminate\Support\Collection;

class GoLiveChecklistService
{
    /**
     * @return Collection<int, array{key: string, label: string, done: bool, hint: ?string, route: ?string}>
     */
    public function itemsForTenant(Tenant $tenant): Collection
    {
        $tenantId = (int) $tenant->id;

        $accountSettings = AccountSetting::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->count();

        $warehouseCount = Warehouse::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();

        $productCount = Product::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->count();

        $ecommerceProducts = Product::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_ecommerce', true)
            ->where('is_active', true)
            ->count();

        $storefrontPublished = Storefront::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('published_version_id')
            ->exists();

        $hasDomain = filled($tenant->domain);
        $hasPeriod = FinancialPeriod::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->exists();

        $hasPlan = $tenant->plan_id !== null && $tenant->hasFeature('pos');

        return collect([
            [
                'key' => 'plan',
                'label' => 'Subscription plan with POS / inventory features',
                'done' => $hasPlan,
                'hint' => $hasPlan ? null : 'Assign a plan from the platform console.',
                'route' => null,
            ],
            [
                'key' => 'domain',
                'label' => 'Store domain configured',
                'done' => $hasDomain,
                'hint' => $hasDomain ? $tenant->domain : 'Set tenant domain to the storefront host (e.g. shop.client.com).',
                'route' => null,
            ],
            [
                'key' => 'accounting',
                'label' => 'Chart of accounts and GL mappings',
                'done' => $accountSettings >= 10,
                'hint' => $accountSettings >= 10 ? null : 'Run accounting provisioning or open Accounting Setup.',
                'route' => 'accounting.settings',
            ],
            [
                'key' => 'warehouse',
                'label' => 'Active warehouse',
                'done' => $warehouseCount > 0,
                'hint' => null,
                'route' => 'inventory.warehouses.index',
            ],
            [
                'key' => 'products',
                'label' => 'Products in catalog',
                'done' => $productCount > 0,
                'hint' => null,
                'route' => 'inventory.products.index',
            ],
            [
                'key' => 'ecommerce_products',
                'label' => 'Ecommerce-enabled products',
                'done' => $ecommerceProducts > 0,
                'hint' => null,
                'route' => 'inventory.products.index',
            ],
            [
                'key' => 'storefront',
                'label' => 'Storefront published',
                'done' => $storefrontPublished,
                'hint' => $storefrontPublished ? null : 'Publish the storefront in Storefront Builder.',
                'route' => 'admin.storefront-builder.index',
            ],
            [
                'key' => 'financial_period',
                'label' => 'Financial period created',
                'done' => $hasPeriod,
                'hint' => null,
                'route' => 'accounting.periods',
            ],
            [
                'key' => 'opening',
                'label' => 'Opening balances entered',
                'done' => JournalEntry::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('description', 'like', '%Opening balance%')
                    ->exists(),
                'hint' => 'Post opening balances before the go-live date.',
                'route' => 'accounting.opening-balance',
            ],
        ]);
    }

    public function isReadyForGoLive(Tenant $tenant): bool
    {
        return $this->itemsForTenant($tenant)->every(fn (array $item) => $item['done']);
    }

    public function completionPercentage(Tenant $tenant): int
    {
        $items = $this->itemsForTenant($tenant);
        if ($items->isEmpty()) {
            return 0;
        }

        $done = $items->where('done', true)->count();

        return (int) round(($done / $items->count()) * 100);
    }
}
