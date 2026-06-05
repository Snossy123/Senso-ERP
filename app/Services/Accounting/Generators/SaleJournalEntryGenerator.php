<?php

namespace App\Services\Accounting\Generators;

use App\Models\AccountSetting;
use App\Models\Sale;
use App\Models\Tenant;
use App\Services\Accounting\JournalEntryGeneratorInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;

class SaleJournalEntryGenerator implements JournalEntryGeneratorInterface
{
    public function generate(Model $sale): array
    {
        if (! $sale instanceof Sale) {
            throw new Exception("Expected instance of App\Models\Sale");
        }

        $tenantId = $sale->tenant_id;

        $paymentAccountKey = match ($sale->payment_method) {
            'cash' => 'pos_cash',
            'card' => 'pos_card',
            'bank_transfer' => 'pos_bank',
            'credit' => 'customer_receivable',
            default => 'pos_cash',
        };

        $paymentAccountId = AccountSetting::getAccountId($paymentAccountKey, $tenantId);
        $salesAccountId = AccountSetting::getAccountId('sales_revenue', $tenantId);
        $taxAccountId = AccountSetting::getAccountId('tax_payable', $tenantId);

        if (! $paymentAccountId || ! $salesAccountId) {
            throw new Exception("Accounting Mapping Missing for Sale #{$sale->sale_number}. Map '{$paymentAccountKey}' and 'sales_revenue'.");
        }

        $lines = [];

        $paymentDebit = (float) $sale->total;
        if ($sale->payment_method === 'card') {
            $feeGenerator = new PaymentFeeJournalEntryGenerator;
            $fee = $feeGenerator->feeAmountForSale($sale, Tenant::find($tenantId));
            $paymentDebit = round($paymentDebit - $fee, 2);
        }

        $lines[] = [
            'account_id' => $paymentAccountId,
            'description' => "POS Sale #{$sale->sale_number} ({$sale->payment_method})",
            'debit' => $paymentDebit,
            'credit' => 0,
        ];

        $taxAmount = (float) ($sale->tax_amount ?? 0);
        $discountAmount = (float) ($sale->discount_amount ?? 0);
        $revenueAmount = (float) $sale->subtotal;

        // Credit: Sales Revenue (gross; discount posted separately)
        $lines[] = [
            'account_id' => $salesAccountId,
            'description' => "POS Sale Revenue #{$sale->sale_number}",
            'debit' => 0,
            'credit' => $revenueAmount,
        ];

        if ($discountAmount > 0) {
            $discountAccountId = AccountSetting::getAccountId('sales_discount', $tenantId);
            if (! $discountAccountId) {
                throw new Exception("Discount account mapping missing for Sale #{$sale->sale_number}");
            }
            $lines[] = [
                'account_id' => $discountAccountId,
                'description' => "Discount on Sale #{$sale->sale_number}",
                'debit' => $discountAmount,
                'credit' => 0,
            ];
        }

        // Credit: Tax Payable
        if ($taxAmount > 0) {
            if (! $taxAccountId) {
                throw new Exception("Tax account mapping missing for Sale #{$sale->sale_number}");
            }
            $lines[] = [
                'account_id' => $taxAccountId,
                'description' => "Tax on Sale #{$sale->sale_number}",
                'debit' => 0,
                'credit' => $taxAmount,
            ];
        }

        return [
            'header' => [
                'tenant_id' => $sale->tenant_id,
                'date' => $sale->created_at->toDateString(),
                'reference' => "SALE-{$sale->sale_number}",
                'description' => "Journal Entry for POS Sale {$sale->sale_number}",
                'source_type' => get_class($sale),
                'source_id' => $sale->id,
            ],
            'lines' => $lines,
        ];
    }
}
