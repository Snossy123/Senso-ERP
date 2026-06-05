<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            PlatformModuleSeeder::class,
            PlanModuleSeeder::class,
            PaymentGatewaySeeder::class,
            PlatformSettingSeeder::class,
            RolePermissionSeeder::class,
            TenantSeeder::class,
            AccountSeeder::class,
            BranchSeeder::class,
            UserSeeder::class,
            InventorySeeder::class,
            SalesSeeder::class,
            DemoCompanySeeder::class,
        ]);
    }
}
