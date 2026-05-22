<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        PaymentGateway::updateOrCreate(
            ['driver' => 'manual'],
            [
                'name' => 'Manual / Bank Transfer',
                'config' => [],
                'is_active' => true,
                'is_default' => true,
            ]
        );
    }
}
