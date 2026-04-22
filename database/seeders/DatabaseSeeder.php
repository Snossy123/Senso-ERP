<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            TenantSeeder::class,
            AccountSeeder::class,
            RolePermissionSeeder::class,
            BranchSeeder::class,
            UserSeeder::class,
            InventorySeeder::class,
            SalesSeeder::class,
        ]);
    }
}
