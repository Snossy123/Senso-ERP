<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanModule;
use App\Models\PlatformModule;
use Illuminate\Database\Seeder;

class PlanModuleSeeder extends Seeder
{
    public function run(): void
    {
        $featureToModule = [
            'pos' => 'pos',
            'basic_reports' => 'reports',
            'reports' => 'reports',
            'advanced_reports' => 'reports',
            'inventory' => 'inventory',
            'multi_warehouse' => 'inventory',
            'customers' => 'crm',
            'suppliers' => 'procurement',
            'api' => 'api',
        ];

        $defaultLimits = [
            'pos' => ['users' => 5, 'devices' => 3],
            'ecommerce' => ['products' => 500, 'orders_per_month' => 1000, 'domains' => 1],
            'inventory' => ['products' => 500, 'warehouses' => 2],
            'accounting' => ['journal_entries_per_month' => 500],
            'crm' => ['customers' => 500],
            'reports' => ['export_per_month' => 50],
            'api' => ['requests_per_day' => 5000],
            'procurement' => ['suppliers' => 25],
        ];

        foreach (Plan::all() as $plan) {
            $enabledModules = [];

            foreach ($plan->features ?? [] as $feature) {
                $moduleKey = $featureToModule[$feature] ?? null;
                if ($moduleKey) {
                    $enabledModules[$moduleKey] = true;
                }
            }

            if ($plan->max_products > 0) {
                $enabledModules['ecommerce'] = true;
                $enabledModules['inventory'] = true;
            }

            foreach (PlatformModule::where('is_active', true)->orderBy('sort_order')->get() as $module) {
                $enabled = isset($enabledModules[$module->key]);

                PlanModule::updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'module_key' => $module->key,
                    ],
                    [
                        'enabled' => $enabled,
                        'limits' => $enabled ? ($defaultLimits[$module->key] ?? []) : [],
                    ]
                );
            }
        }
    }
}
