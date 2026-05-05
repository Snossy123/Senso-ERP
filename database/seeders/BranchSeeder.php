<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Services\BranchProvisioningService;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(BranchProvisioningService::class);

        foreach (Tenant::query()->orderBy('id')->cursor() as $tenant) {
            $service->ensureDefaultBranchesForTenant($tenant);
        }
    }
}
