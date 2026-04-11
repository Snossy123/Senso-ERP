<?php

namespace App\Services\Accounting\Generators;

use App\Models\Sale;
use App\Models\AccountSetting;
use App\Services\Accounting\JournalEntryGeneratorInterface;
use Illuminate\Database\Eloquent\Model;
use Exception;

class SaleJournalEntryGenerator implements JournalEntryGeneratorInterface
{
    public function generate(Model $sale): array
    {
        if (!$sale instanceof Sale) {
            throw new Exception("Expected instance of App\Models\Sale");
        }

        $tenantId = $sale->tenant_id;
        
        // Map payment method to account key
        $paymentAccountKey = match($sale->payment_method) {
            'cash'          => 'pos_cash',
            'card'          => 'pos_card',
            'bank_transfer' => 'pos_bank',
            default         => 'pos_cash' // Fallback
        };

        $paymentAccountId = AccountSetting::getAccountId($paymentAccountKey, $tenantId);
        $salesAccountId   = AccountSetting::getAccountId('sales_revenue', $tenantId);
        $taxAccountId     = AccountSetting::getAccountId('tax_payable', $tenantId);
        
        if (!$paymentAccountId || !$salesAccountId) {
            throw new Exception("Accounting Mapping Missing for Sale #{$sale->sale_number}. Map '{$paymentAccountKey}' and 'sales_revenue'.");
        }

        $lines = [];
        
        // Debit: Payment Account (Cash/Card/Bank)
        $lines[] = [
            'account_id'  => $paymentAccountId,
            'description' => "POS Sale #{$sale->sale_number} ({$sale->payment_method})",
            'debit'       => (float) $sale->total,
            'credit'      => 0,
        ];

        $taxAmount     = (float) ($sale->tax_amount ?? 0);
        $revenueAmount = (float) ($sale->total - $taxAmount);

        // Credit: Sales Revenue
        $lines[] = [
            'account_id'  => $salesAccountId,
            'description' => "POS Sale Revenue #{$sale->sale_number}",
            'debit'       => 0,
            'credit'      => $revenueAmount,
        ];

        // Credit: Tax Payable
        if ($taxAmount > 0) {
            if (!$taxAccountId) {
                throw new Exception("Tax account mapping missing for Sale #{$sale->sale_number}");
            }
            $lines[] = [
                'account_id'  => $taxAccountId,
                'description' => "Tax on Sale #{$sale->sale_number}",
                'debit'       => 0,
                'credit'      => $taxAmount,
            ];
        }

        return [
            'header' => [
                'tenant_id'   => $sale->tenant_id,
                'date'        => $sale->created_at->toDateString(),
                'reference'   => "SALE-{$sale->sale_number}",
                'description' => "Journal Entry for POS Sale {$sale->sale_number}",
                'source_type' => get_class($sale),
                'source_id'   => $sale->id,
            ],
            'lines' => $lines
        ];
    }
}
