<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Perfect for small businesses getting started',
                'price' => 0,
                'billing_cycle' => 'monthly',
                'max_users' => 2,
                'max_products' => 50,
                'max_orders_per_month' => 30,
                'features' => ['pos', 'basic_reports', 'inventory'],
                'sort_order' => 1,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'For growing businesses with more needs',
                'price' => 29.99,
                'billing_cycle' => 'monthly',
                'max_users' => 5,
                'max_products' => 500,
                'max_orders_per_month' => 200,
                'features' => ['pos', 'reports', 'inventory', 'multi_warehouse', 'customers'],
                'sort_order' => 2,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'Advanced features for established businesses',
                'price' => 79.99,
                'billing_cycle' => 'monthly',
                'max_users' => 20,
                'max_products' => 2000,
                'max_orders_per_month' => 1000,
                'features' => ['pos', 'reports', 'api', 'inventory', 'multi_warehouse', 'customers', 'suppliers', 'advanced_reports'],
                'sort_order' => 3,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited power for large organizations',
                'price' => 199.99,
                'billing_cycle' => 'monthly',
                'max_users' => 100,
                'max_products' => 10000,
                'max_orders_per_month' => 10000,
                'features' => ['pos', 'reports', 'api', 'inventory', 'multi_warehouse', 'customers', 'suppliers', 'advanced_reports', 'white_label', 'priority_support', 'custom_integrations'],
                'sort_order' => 4,
                'is_active' => true,
                'is_featured' => false,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
