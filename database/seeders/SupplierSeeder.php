<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'TechSupply Co.',
                'email' => 'orders@techsupply.com',
                'phone' => '+1-800-TECH',
                'address' => '123 Tech Avenue',
                'city' => 'San Francisco',
                'country' => 'USA',
                'tax_number' => 'US123456789',
            ],
            [
                'name' => 'Global Goods Ltd.',
                'email' => 'info@globalgoods.com',
                'phone' => '+44-20-1234-5678',
                'address' => '456 Trade Street',
                'city' => 'London',
                'country' => 'UK',
                'tax_number' => 'GB987654321',
            ],
        ];

        foreach ($suppliers as $sup) {
            Supplier::create(array_merge($sup, ['is_active' => true]));
        }
    }
}
