<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            [
                'name' => 'Tech Store Electronics',
                'slug' => 'tech-store',
                'domain' => 'techstore.local',
                'settings' => ['plan' => 'premium', 'max_users' => 20],
                'is_active' => true,
                'trial_ends_at' => now()->addDays(30),
            ],
            [
                'name' => 'Fashion Hub Clothing',
                'slug' => 'fashion-hub',
                'domain' => 'fashionhub.local',
                'settings' => ['plan' => 'standard', 'max_users' => 10],
                'is_active' => true,
                'trial_ends_at' => now()->addDays(30),
            ],
            [
                'name' => 'Home Essentials Store',
                'slug' => 'home-essentials',
                'domain' => 'homeessentials.local',
                'settings' => ['plan' => 'basic', 'max_users' => 5],
                'is_active' => true,
            ],
        ];

        foreach ($tenants as $tenant) {
            Tenant::firstOrCreate(['slug' => $tenant['slug']], $tenant);
        }
    }
}
