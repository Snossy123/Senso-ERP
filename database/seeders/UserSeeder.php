<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // Tech Store Electronics (tenant_id = 1)
            ['name' => 'Admin Tech', 'email' => 'admin@techstore.local', 'password' => 'password', 'role' => 'admin', 'tenant_id' => 1],
            ['name' => 'Manager Tech', 'email' => 'manager@techstore.local', 'password' => 'password', 'role' => 'manager', 'tenant_id' => 1],
            ['name' => 'Staff Tech', 'email' => 'staff@techstore.local', 'password' => 'password', 'role' => 'staff', 'tenant_id' => 1],

            // Fashion Hub Clothing (tenant_id = 2)
            ['name' => 'Admin Fashion', 'email' => 'admin@fashionhub.local', 'password' => 'password', 'role' => 'admin', 'tenant_id' => 2],
            ['name' => 'Manager Fashion', 'email' => 'manager@fashionhub.local', 'password' => 'password', 'role' => 'manager', 'tenant_id' => 2],

            // Home Essentials Store (tenant_id = 3)
            ['name' => 'Admin Home', 'email' => 'admin@homeessentials.local', 'password' => 'password', 'role' => 'admin', 'tenant_id' => 3],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                    'role' => $user['role'],
                    'tenant_id' => $user['tenant_id'],
                    'is_active' => true,
                ]
            );
        }
    }
}
