<?php

namespace App\Services\Accounting;

use App\Models\Tenant;

class TenantAccountingSettings
{
    public static function goLiveDate(?Tenant $tenant): ?string
    {
        $date = $tenant?->settings['accounting']['go_live_date'] ?? null;

        return is_string($date) && $date !== '' ? $date : null;
    }

    public static function setGoLiveDate(Tenant $tenant, ?string $date): void
    {
        $settings = $tenant->settings ?? [];
        $settings['accounting'] = array_merge($settings['accounting'] ?? [], [
            'go_live_date' => $date,
        ]);
        $tenant->update(['settings' => $settings]);
    }

    public static function cardFeePercent(?Tenant $tenant): float
    {
        $value = $tenant?->settings['accounting']['card_fee_percent'] ?? 0;

        return max(0, min(100, (float) $value));
    }

    public static function setCardFeePercent(Tenant $tenant, float $percent): void
    {
        $settings = $tenant->settings ?? [];
        $settings['accounting'] = array_merge($settings['accounting'] ?? [], [
            'card_fee_percent' => max(0, min(100, $percent)),
        ]);
        $tenant->update(['settings' => $settings]);
    }

    public static function baseCurrency(?Tenant $tenant): string
    {
        return $tenant?->currency ?? config('app.currency', 'USD');
    }
}
