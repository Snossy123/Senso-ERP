<?php

namespace App\Services\Accounting\Generators;

use App\Models\AccountSetting;
use App\Models\InvoicePayment;
use App\Services\Accounting\JournalEntryGeneratorInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;

class InvoicePaymentJournalEntryGenerator implements JournalEntryGeneratorInterface
{
    public function generate(Model $payment): array
    {
        if (! $payment instanceof InvoicePayment) {
            throw new Exception('Expected instance of App\Models\InvoicePayment');
        }

        $tenantId = $payment->tenant_id;

        $paymentAccountKey = match ($payment->payment_method) {
            'bank_transfer' => 'pos_bank',
            default => 'pos_cash',
        };

        $paymentAccountId = AccountSetting::getAccountId($paymentAccountKey, $tenantId);
        $arAccountId = AccountSetting::getAccountId('customer_receivable', $tenantId);

        if (! $paymentAccountId || ! $arAccountId) {
            throw new Exception("Accounting mapping missing for Payment #{$payment->payment_number}. Map {$paymentAccountKey} and customer_receivable.");
        }

        $amount = (float) $payment->amount;

        return [
            'header' => [
                'tenant_id' => $payment->tenant_id,
                'date' => $payment->paid_at->toDateString(),
                'reference' => "PAY-{$payment->payment_number}",
                'description' => "Customer payment {$payment->payment_number}",
                'source_type' => InvoicePayment::class,
                'source_id' => $payment->id,
            ],
            'lines' => [
                [
                    'account_id' => $paymentAccountId,
                    'description' => "Payment #{$payment->payment_number} ({$payment->payment_method})",
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $arAccountId,
                    'description' => "AR settlement — {$payment->payment_number}",
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
        ];
    }
}
