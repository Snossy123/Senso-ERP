<?php

namespace App\Services\Accounting\Generators;

use App\Models\AccountSetting;
use App\Models\SaleRefund;
use App\Services\Accounting\JournalEntryGeneratorInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;

class RefundJournalEntryGenerator implements JournalEntryGeneratorInterface
{
    public function generate(Model $refund): array
    {
        if (! $refund instanceof SaleRefund) {
            throw new Exception("Expected instance of App\Models\SaleRefund");
        }

        $sale = $refund->sale;
        $tenantId = $refund->tenant_id;

        // Map payment method to account key (where the money is going out from)
        $paymentAccountKey = match ($refund->method) {
            'cash' => 'pos_cash',
            'card' => 'pos_card',
            'bank_transfer' => 'pos_bank',
            'original' => match ($sale->payment_method) {
                'cash' => 'pos_cash',
                'card' => 'pos_card',
                'bank_transfer' => 'pos_bank',
                default => 'pos_cash'
            },
            default => 'pos_cash'
        };

        $paymentAccountId = AccountSetting::getAccountId($paymentAccountKey, $tenantId);
        $salesAccountId = AccountSetting::getAccountId('sales_revenue', $tenantId);
        $taxAccountId = AccountSetting::getAccountId('tax_payable', $tenantId);

        if (! $paymentAccountId || ! $salesAccountId) {
            throw new Exception("Accounting Mapping Missing for Refund #{$refund->refund_number}. Map '{$paymentAccountKey}' and 'sales_revenue'.");
        }

        $lines = [];

        $totalRefund = (float) $refund->amount;

        // Pro-rate tax if possible, otherwise assume refund includes tax
        // For simplicity, we'll calculate the tax portion based on the original sale's tax rate
        $taxRate = $sale->tax_amount > 0 ? ($sale->tax_amount / ($sale->total - $sale->tax_amount)) : 0;
        $revenueRefund = round($totalRefund / (1 + $taxRate), 2);
        $taxRefund = round($totalRefund - $revenueRefund, 2);

        // Debit: Sales Revenue (Reducing revenue)
        $lines[] = [
            'account_id' => $salesAccountId,
            'description' => "Refund Revenue - Sale #{$sale->sale_number}",
            'debit' => $revenueRefund,
            'credit' => 0,
        ];

        // Debit: Tax Payable (Reducing tax liability)
        if ($taxRefund > 0) {
            if (! $taxAccountId) {
                throw new Exception("Tax account mapping missing for Refund #{$refund->refund_number}");
            }
            $lines[] = [
                'account_id' => $taxAccountId,
                'description' => "Refund Tax - Sale #{$sale->sale_number}",
                'debit' => $taxRefund,
                'credit' => 0,
            ];
        }

        // Credit: Payment Account (Money going back to customer)
        $lines[] = [
            'account_id' => $paymentAccountId,
            'description' => "Refund Payment - Sale #{$sale->sale_number} ({$refund->method})",
            'debit' => 0,
            'credit' => $totalRefund,
        ];

        return [
            'header' => [
                'tenant_id' => $refund->tenant_id,
                'date' => $refund->created_at ? $refund->created_at->toDateString() : now()->toDateString(),
                'reference' => "REF-{$refund->refund_number}",
                'description' => "Journal Entry for Refund {$refund->refund_number} (Sale #{$sale->sale_number})",
                'source_type' => get_class($refund),
                'source_id' => $refund->id,
            ],
            'lines' => $lines,
        ];
    }
}
