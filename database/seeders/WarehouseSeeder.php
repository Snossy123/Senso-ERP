<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            ['name' => 'Main Warehouse',   'location' => 'Building A, Floor 1', 'manager_name' => 'John Doe',  'phone' => '+1-555-0101'],
            ['name' => 'Branch Warehouse', 'location' => 'Building B, Floor 2', 'manager_name' => 'Jane Smith', 'phone' => '+1-555-0102'],
        ];

        foreach ($warehouses as $wh) {
            Warehouse::create(array_merge($wh, ['is_active' => true]));
        }
    }
}
