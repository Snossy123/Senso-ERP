<?php

namespace App\Services\Accounting\Generators;

use App\Models\AccountSetting;
use App\Models\SalesInvoice;
use App\Services\Accounting\JournalEntryGeneratorInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceJournalEntryGenerator implements JournalEntryGeneratorInterface
{
    public function generate(Model $invoice): array
    {
        if (! $invoice instanceof SalesInvoice) {
            throw new Exception('Expected instance of App\Models\SalesInvoice');
        }

        $tenantId = $invoice->tenant_id;
        $arAccountId = AccountSetting::getAccountId('customer_receivable', $tenantId);
        $salesAccountId = AccountSetting::getAccountId('sales_revenue', $tenantId);
        $taxAccountId = AccountSetting::getAccountId('tax_payable', $tenantId);

        if (! $arAccountId || ! $salesAccountId) {
            throw new Exception("Accounting mapping missing for Sales Invoice #{$invoice->invoice_number}. Map customer_receivable and sales_revenue.");
        }

        $taxAmount = (float) ($invoice->tax_amount ?? 0);
        $revenueAmount = (float) ($invoice->total - $taxAmount);

        $lines = [
            [
                'account_id' => $arAccountId,
                'description' => "AR — Sales Invoice #{$invoice->invoice_number}",
                'debit' => (float) $invoice->total,
                'credit' => 0,
            ],
            [
                'account_id' => $salesAccountId,
                'description' => "Revenue — Sales Invoice #{$invoice->invoice_number}",
                'debit' => 0,
                'credit' => $revenueAmount,
            ],
        ];

        if ($taxAmount > 0) {
            if (! $taxAccountId) {
                throw new Exception("Tax account mapping missing for Sales Invoice #{$invoice->invoice_number}");
            }
            $lines[] = [
                'account_id' => $taxAccountId,
                'description' => "Tax — Sales Invoice #{$invoice->invoice_number}",
                'debit' => 0,
                'credit' => $taxAmount,
            ];
        }

        return [
            'header' => [
                'tenant_id' => $invoice->tenant_id,
                'date' => ($invoice->invoice_date ?? $invoice->confirmed_at ?? now())->toDateString(),
                'reference' => "SINV-{$invoice->invoice_number}",
                'description' => "Sales Invoice {$invoice->invoice_number}",
                'source_type' => SalesInvoice::class,
                'source_id' => $invoice->id,
            ],
            'lines' => $lines,
        ];
    }
}
