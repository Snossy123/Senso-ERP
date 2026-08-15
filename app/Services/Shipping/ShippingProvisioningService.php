<?php

namespace App\Services\Shipping;

use App\Models\ShippingIntegration;
use App\Models\ShippingRate;
use App\Models\Tenant;

class ShippingProvisioningService
{
    /**
     * @return list<array{city: string, city_label: string, fee: float}>
     */
    public static function defaultCities(): array
    {
        return [
            ['city' => 'القاهرة', 'city_label' => 'Cairo', 'fee' => 0],
            ['city' => 'قاهره', 'city_label' => 'Cairo (QP)', 'fee' => 0],
            ['city' => 'الجيزة', 'city_label' => 'Giza', 'fee' => 0],
            ['city' => 'الإسكندرية', 'city_label' => 'Alexandria', 'fee' => 0],
            ['city' => 'القليوبية', 'city_label' => 'Qalyubia', 'fee' => 0],
            ['city' => 'الشرقية', 'city_label' => 'Sharqia', 'fee' => 0],
            ['city' => 'الدقهلية', 'city_label' => 'Dakahlia', 'fee' => 0],
            ['city' => 'البحيرة', 'city_label' => 'Beheira', 'fee' => 0],
            ['city' => 'المنوفية', 'city_label' => 'Monufia', 'fee' => 0],
            ['city' => 'الغربية', 'city_label' => 'Gharbia', 'fee' => 0],
            ['city' => 'كفر الشيخ', 'city_label' => 'Kafr El Sheikh', 'fee' => 0],
            ['city' => 'دمياط', 'city_label' => 'Damietta', 'fee' => 0],
            ['city' => 'بورسعيد', 'city_label' => 'Port Said', 'fee' => 0],
            ['city' => 'الإسماعيلية', 'city_label' => 'Ismailia', 'fee' => 0],
            ['city' => 'السويس', 'city_label' => 'Suez', 'fee' => 0],
            ['city' => 'شمال سيناء', 'city_label' => 'North Sinai', 'fee' => 0],
            ['city' => 'جنوب سيناء', 'city_label' => 'South Sinai', 'fee' => 0],
            ['city' => 'الفيوم', 'city_label' => 'Faiyum', 'fee' => 0],
            ['city' => 'بني سويف', 'city_label' => 'Beni Suef', 'fee' => 0],
            ['city' => 'المنيا', 'city_label' => 'Minya', 'fee' => 0],
            ['city' => 'أسيوط', 'city_label' => 'Asyut', 'fee' => 0],
            ['city' => 'سوهاج', 'city_label' => 'Sohag', 'fee' => 0],
            ['city' => 'قنا', 'city_label' => 'Qena', 'fee' => 0],
            ['city' => 'الأقصر', 'city_label' => 'Luxor', 'fee' => 0],
            ['city' => 'أسوان', 'city_label' => 'Aswan', 'fee' => 0],
            ['city' => 'البحر الأحمر', 'city_label' => 'Red Sea', 'fee' => 0],
            ['city' => 'الوادي الجديد', 'city_label' => 'New Valley', 'fee' => 0],
            ['city' => 'وادي جديد', 'city_label' => 'New Valley (QP)', 'fee' => 0],
            ['city' => 'مطروح', 'city_label' => 'Matrouh', 'fee' => 0],
        ];
    }

    public function provisionForTenant(Tenant $tenant): ShippingIntegration
    {
        $integration = ShippingIntegration::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'driver' => 'qp',
                'is_active' => false,
                'default_weight' => 1,
                'auto_create_on_checkout' => false,
            ]
        );

        foreach (self::defaultCities() as $city) {
            ShippingRate::withoutGlobalScopes()->firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'city' => $city['city'],
                ],
                [
                    'city_label' => $city['city_label'],
                    'fee' => $city['fee'],
                    'is_active' => true,
                ]
            );
        }

        return $integration;
    }
}
