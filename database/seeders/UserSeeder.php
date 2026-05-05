<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $platformAdmin = Role::withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->where('slug', 'admin')
            ->first();

        if ($platformAdmin) {
            User::firstOrCreate(
                ['email' => 'platform@senso.local'],
                [
                    'name' => 'Platform Operator',
                    'password' => Hash::make('password'),
                    'role_id' => $platformAdmin->id,
                    'tenant_id' => null,
                    'is_active' => true,
                ]
            );
        }

        $rows = [
            ['name' => 'Admin Tech', 'email' => 'admin@techstore.local', 'password' => 'password', 'role' => 'admin', 'tenant_slug' => 'tech-store'],
            ['name' => 'Manager Tech', 'email' => 'manager@techstore.local', 'password' => 'password', 'role' => 'manager', 'tenant_slug' => 'tech-store'],
            ['name' => 'Staff Tech', 'email' => 'staff@techstore.local', 'password' => 'password', 'role' => 'cashier', 'tenant_slug' => 'tech-store'],
            ['name' => 'Admin Fashion', 'email' => 'admin@fashionhub.local', 'password' => 'password', 'role' => 'admin', 'tenant_slug' => 'fashion-hub'],
            ['name' => 'Manager Fashion', 'email' => 'manager@fashionhub.local', 'password' => 'password', 'role' => 'manager', 'tenant_slug' => 'fashion-hub'],
            ['name' => 'Admin Home', 'email' => 'admin@homeessentials.local', 'password' => 'password', 'role' => 'admin', 'tenant_slug' => 'home-essentials'],
        ];

        foreach ($rows as $row) {
            $tenant = Tenant::where('slug', $row['tenant_slug'])->first();
            if (! $tenant) {
                continue;
            }

            $role = Role::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('slug', $row['role'])
                ->first();

            if (! $role) {
                continue;
            }

            User::firstOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => Hash::make($row['password']),
                    'role_id' => $role->id,
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
