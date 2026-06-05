<?php

namespace App\Services\Accounting\Generators;

use App\Models\AccountSetting;
use App\Models\Sale;
use App\Models\Tenant;
use App\Services\Accounting\TenantAccountingSettings;

class PaymentFeeJournalEntryGenerator
{
    public function feeAmountForSale(Sale $sale, ?Tenant $tenant = null): float
    {
        $tenant = $tenant ?? Tenant::find($sale->tenant_id);
        $percent = TenantAccountingSettings::cardFeePercent($tenant);

        if ($sale->payment_method !== 'card' || $percent <= 0) {
            return 0.0;
        }

        return round((float) $sale->total * ($percent / 100), 2);
    }

    /**
     * @return array{header: array, lines: array}|null
     */
    public function generateForSale(Sale $sale): ?array
    {
        $tenantId = (int) $sale->tenant_id;
        $fee = $this->feeAmountForSale($sale);

        if ($fee <= 0) {
            return null;
        }

        $feeAccountId = AccountSetting::getAccountId('payment_fees', $tenantId);
        $cardAccountId = AccountSetting::getAccountId('pos_card', $tenantId);

        if (! $feeAccountId || ! $cardAccountId) {
            return null;
        }

        return [
            'header' => [
                'tenant_id' => $tenantId,
                'date' => $sale->created_at?->toDateString() ?? now()->toDateString(),
                'reference' => "FEE-SALE-{$sale->sale_number}",
                'description' => "Card processing fee for POS Sale {$sale->sale_number}",
                'source_type' => null,
                'source_id' => null,
            ],
            'lines' => [
                [
                    'account_id' => $feeAccountId,
                    'description' => "Payment fee — Sale #{$sale->sale_number}",
                    'debit' => $fee,
                    'credit' => 0,
                ],
                [
                    'account_id' => $cardAccountId,
                    'description' => "Fee withheld — Sale #{$sale->sale_number}",
                    'debit' => 0,
                    'credit' => $fee,
                ],
            ],
        ];
    }
}
