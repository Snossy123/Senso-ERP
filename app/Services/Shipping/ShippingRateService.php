<?php

namespace App\Services\Shipping;

use App\Models\ShippingRate;

class ShippingRateService
{
    public function feeForCity(?string $city, ?int $tenantId = null): float
    {
        $city = trim((string) $city);
        if ($city === '') {
            return 0.0;
        }

        $query = ShippingRate::query()->active()->where('city', $city);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $rate = $query->first();

        return $rate ? (float) $rate->fee : 0.0;
    }

    /**
     * @return \Illuminate\Support\Collection<int, ShippingRate>
     */
    public function activeRates(?int $tenantId = null)
    {
        $query = ShippingRate::query()->active();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get();
    }
}
