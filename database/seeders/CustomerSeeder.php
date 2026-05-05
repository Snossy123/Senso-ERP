<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Alice Johnson', 'email' => 'alice@example.com', 'phone' => '+1-555-1001', 'city' => 'New York'],
            ['name' => 'Bob Williams',  'email' => 'bob@example.com',   'phone' => '+1-555-1002', 'city' => 'Los Angeles'],
        ];

        foreach ($customers as $c) {
            Customer::create(array_merge($c, [
                'password' => Hash::make('password'),
                'is_active' => true,
            ]));
        }
    }
}
