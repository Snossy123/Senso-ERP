<?php

namespace App\Services\Accounting;

use App\Models\FinancialPeriod;
use Carbon\Carbon;
use Exception;

class FinancialPeriodService
{
    public function assertDateWritable(?int $tenantId, string $date): void
    {
        if (! $tenantId) {
            return;
        }

        $closed = FinancialPeriod::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'closed')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();

        if ($closed) {
            throw new Exception('Cannot post to a closed financial period for this date.');
        }
    }

    public function openPeriod(int $tenantId, string $name, string $startDate, string $endDate): FinancialPeriod
    {
        return FinancialPeriod::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'open',
        ]);
    }

    public function closePeriod(FinancialPeriod $period): FinancialPeriod
    {
        $period->update(['status' => 'closed']);

        return $period->fresh();
    }

    public function currentOpenPeriod(int $tenantId): ?FinancialPeriod
    {
        $today = Carbon::today()->toDateString();

        return FinancialPeriod::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();
    }
}
