<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'default_currency' => 'USD',
            'default_trial_days' => 14,
            'platform_name' => 'Valex',
            'support_email' => 'support@example.com',
            'invoice_prefix' => 'INV-',
        ];

        foreach ($defaults as $key => $value) {
            PlatformSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
