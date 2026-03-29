<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $managerRole = Role::where('slug', 'manager')->first();
        $cashierRole = Role::where('slug', 'cashier')->first();

        $users = [
            ['name' => 'Admin Tech', 'email' => 'admin@techstore.local', 'password' => 'password', 'role_id' => $adminRole?->id, 'tenant_id' => 1],
            ['name' => 'Manager Tech', 'email' => 'manager@techstore.local', 'password' => 'password', 'role_id' => $managerRole?->id, 'tenant_id' => 1],
            ['name' => 'Staff Tech', 'email' => 'staff@techstore.local', 'password' => 'password', 'role_id' => $cashierRole?->id, 'tenant_id' => 1],
            ['name' => 'Admin Fashion', 'email' => 'admin@fashionhub.local', 'password' => 'password', 'role_id' => $adminRole?->id, 'tenant_id' => 2],
            ['name' => 'Manager Fashion', 'email' => 'manager@fashionhub.local', 'password' => 'password', 'role_id' => $managerRole?->id, 'tenant_id' => 2],
            ['name' => 'Admin Home', 'email' => 'admin@homeessentials.local', 'password' => 'password', 'role_id' => $adminRole?->id, 'tenant_id' => 3],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                    'role_id' => $user['role_id'],
                    'tenant_id' => $user['tenant_id'],
                    'is_active' => true,
                ]
            );
        }
    }
}
