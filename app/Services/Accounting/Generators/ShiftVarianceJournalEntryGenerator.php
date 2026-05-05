<?php

namespace App\Services\Accounting\Generators;

use App\Models\AccountSetting;
use App\Models\PosShift;
use App\Services\Accounting\JournalEntryGeneratorInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;

class ShiftVarianceJournalEntryGenerator implements JournalEntryGeneratorInterface
{
    public function generate(Model $shift): array
    {
        if (! $shift instanceof PosShift) {
            throw new Exception("Expected instance of App\Models\PosShift");
        }

        $tenantId = $shift->tenant_id;
        $variance = (float) $shift->variance;

        if (abs($variance) < 0.01) {
            throw new Exception("No variance to record for shift #{$shift->id}");
        }

        $cashAccountId = AccountSetting::getAccountId('pos_cash', $tenantId);
        $varianceAccountId = AccountSetting::getAccountId('pos_variance', $tenantId);

        if (! $cashAccountId || ! $varianceAccountId) {
            throw new Exception("Accounting Mapping Missing for Shift Variance #{$shift->id}. Map 'pos_cash' and 'pos_variance'.");
        }

        $lines = [];

        if ($variance > 0) {
            // Cash Surplus: Debit Cash (Increase), Credit Variance (Revenue/Gain)
            $lines[] = [
                'account_id' => $cashAccountId,
                'description' => "Shift Cash Surplus #{$shift->id}",
                'debit' => $variance,
                'credit' => 0,
            ];
            $lines[] = [
                'account_id' => $varianceAccountId,
                'description' => "Shift Variance Gain #{$shift->id}",
                'debit' => 0,
                'credit' => $variance,
            ];
        } else {
            // Cash Shortage: Debit Variance (Expense/Loss), Credit Cash (Decrease)
            $loss = abs($variance);
            $lines[] = [
                'account_id' => $varianceAccountId,
                'description' => "Shift Cash Shortage #{$shift->id}",
                'debit' => $loss,
                'credit' => 0,
            ];
            $lines[] = [
                'account_id' => $cashAccountId,
                'description' => "Shift Variance Loss #{$shift->id}",
                'debit' => 0,
                'credit' => $loss,
            ];
        }

        return [
            'header' => [
                'tenant_id' => $shift->tenant_id,
                'date' => now()->toDateString(),
                'reference' => "VAR-SH-{$shift->id}",
                'description' => "Journal Entry for Shift Variance #{$shift->id}",
                'source_type' => get_class($shift),
                'source_id' => $shift->id,
            ],
            'lines' => $lines,
        ];
    }
}
