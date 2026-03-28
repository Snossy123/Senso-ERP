<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['name' => 'Main Branch', 'code' => 'HQ', 'address' => '123 Main Street, Downtown', 'phone' => '555-0100', 'email' => 'hq@company.com'],
            ['name' => 'Branch North', 'code' => 'NORTH', 'address' => '456 North Avenue, Uptown', 'phone' => '555-0200', 'email' => 'north@company.com'],
            ['name' => 'Branch South', 'code' => 'SOUTH', 'address' => '789 South Blvd, Suburb', 'phone' => '555-0300', 'email' => 'south@company.com'],
        ];

        foreach ($branches as $branch) {
            Branch::firstOrCreate(['code' => $branch['code']], array_merge($branch, ['is_active' => true]));
        }
    }
}
