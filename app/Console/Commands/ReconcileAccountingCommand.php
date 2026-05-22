<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Accounting\AccountingReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileAccountingCommand extends Command
{
    protected $signature = 'accounting:reconcile {--tenant= : Tenant ID to reconcile}';

    protected $description = 'Detect business documents missing journal entries and log alerts';

    public function handle(AccountingReconciliationService $reconciliation): int
    {
        $tenantId = $this->option('tenant');

        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::where('is_active', true)->get();

        $totalMissing = 0;

        foreach ($tenants as $tenant) {
            $missing = $reconciliation->missingJournalEntries((int) $tenant->id);
            $count = $missing->count();
            $totalMissing += $count;

            if ($count > 0) {
                Log::warning('accounting.reconciliation.missing_journals', [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'count' => $count,
                    'items' => $missing->take(20)->all(),
                ]);

                $this->warn("Tenant #{$tenant->id} ({$tenant->name}): {$count} document(s) without journal entries.");
            } else {
                $this->info("Tenant #{$tenant->id} ({$tenant->name}): OK");
            }
        }

        $this->info("Reconciliation complete. Total missing: {$totalMissing}");

        return self::SUCCESS;
    }
}
