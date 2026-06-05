<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\PlanModule;
use App\Models\Tenant;
use Carbon\Carbon;
use Exception;

class JournalEntryLimitService
{
    public function assertCanCreate(Tenant $tenant): void
    {
        $limit = $this->monthlyLimit($tenant);
        if ($limit <= 0) {
            return;
        }

        $count = JournalEntry::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'posted')
            ->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])
            ->count();

        if ($count >= $limit) {
            throw new Exception("Monthly journal entry limit ({$limit}) reached for this plan.");
        }
    }

    public function usage(Tenant $tenant): array
    {
        $limit = $this->monthlyLimit($tenant);
        $count = JournalEntry::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'posted')
            ->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])
            ->count();

        return [
            'current' => $count,
            'limit' => $limit,
            'remaining' => $limit > 0 ? max(0, $limit - $count) : null,
            'at_limit' => $limit > 0 && $count >= $limit,
        ];
    }

    private function monthlyLimit(Tenant $tenant): int
    {
        if (! $tenant->plan_id) {
            return 0;
        }

        $planModule = PlanModule::query()
            ->where('plan_id', $tenant->plan_id)
            ->where('module_key', 'accounting')
            ->where('enabled', true)
            ->first();

        if (! $planModule) {
            return 0;
        }

        return (int) ($planModule->limits['journal_entries_per_month'] ?? 0);
    }
}
