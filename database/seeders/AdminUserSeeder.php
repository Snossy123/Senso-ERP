<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@senso.com'],
            [
                'name' => 'Senso Admin',
                'email' => 'admin@senso.com',
                'password' => Hash::make('password'),
            ]
        );
    }
}
