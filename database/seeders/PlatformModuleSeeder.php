<?php

namespace Database\Seeders;

use App\Models\PlatformModule;
use Illuminate\Database\Seeder;

class PlatformModuleSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['key' => 'pos', 'name' => 'POS', 'sort_order' => 1],
            ['key' => 'ecommerce', 'name' => 'E-commerce', 'sort_order' => 2],
            ['key' => 'inventory', 'name' => 'Inventory', 'sort_order' => 3],
            ['key' => 'accounting', 'name' => 'Accounting', 'sort_order' => 4],
            ['key' => 'crm', 'name' => 'CRM', 'sort_order' => 5],
            ['key' => 'reports', 'name' => 'Reports', 'sort_order' => 6],
            ['key' => 'api', 'name' => 'API', 'sort_order' => 7],
            ['key' => 'procurement', 'name' => 'Procurement', 'sort_order' => 8],
        ];

        foreach ($definitions as $def) {
            $config = config('platform_modules.modules.'.$def['key'], []);

            PlatformModule::updateOrCreate(
                ['key' => $def['key']],
                [
                    'name' => $def['name'],
                    'icon' => $config['icon'] ?? 'fe-box',
                    'sort_order' => $def['sort_order'],
                    'is_active' => true,
                    'limit_schema' => $config['limits'] ?? [],
                    'feature_key' => $config['feature_key'] ?? $def['key'],
                ]
            );
        }
    }
}
