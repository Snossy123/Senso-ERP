<?php

namespace App\Services;

use App\Models\FinancialPeriod;
use App\Models\Tenant;
use App\Services\Accounting\FinancialPeriodService;
use Carbon\Carbon;

class FinancialPeriodProvisioningService
{
    public function __construct(
        private readonly FinancialPeriodService $financialPeriodService
    ) {}

    public function ensureCurrentYearPeriodForTenant(Tenant $tenant): FinancialPeriod
    {
        $existing = FinancialPeriod::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return $existing;
        }

        $year = (string) now()->year;

        return $this->financialPeriodService->openPeriod(
            (int) $tenant->id,
            "{$year} Fiscal Year",
            Carbon::create($year, 1, 1)->toDateString(),
            Carbon::create($year, 12, 31)->toDateString(),
        );
    }
}
