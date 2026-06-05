<?php

namespace App\Services\Accounting;

use App\Models\Tenant;

class CommerceRevenueRecognition
{
    public const ON_PLACE = 'on_place';

    public const ON_PAID = 'on_paid';

    public static function policy(?Tenant $tenant): string
    {
        $settings = $tenant?->settings ?? [];
        $policy = $settings['commerce']['revenue_recognition'] ?? self::ON_PLACE;

        return in_array($policy, [self::ON_PLACE, self::ON_PAID], true)
            ? $policy
            : self::ON_PLACE;
    }

    public static function shouldRecognizeOnCheckout(?Tenant $tenant): bool
    {
        return self::policy($tenant) === self::ON_PLACE;
    }

    public static function shouldRecognizeOnPayment(?Tenant $tenant): bool
    {
        return self::policy($tenant) === self::ON_PAID;
    }
}
