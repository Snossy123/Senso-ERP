<?php

namespace Database\Factories;

use App\Models\PosShift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PosShiftFactory extends Factory
{
    protected $model = PosShift::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'user_id' => User::factory(),
            'terminal_id' => 'POS-1',
            'opening_float' => 100.00,
            'opened_at' => now(),
            'status' => 'open',
        ];
    }
}
