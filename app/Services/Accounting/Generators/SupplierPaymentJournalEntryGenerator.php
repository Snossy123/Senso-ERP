<?php

namespace App\Services\Accounting\Generators;

use App\Models\AccountSetting;
use App\Models\SupplierPayment;
use App\Services\Accounting\JournalEntryGeneratorInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;

class SupplierPaymentJournalEntryGenerator implements JournalEntryGeneratorInterface
{
    public function generate(Model $payment): array
    {
        if (! $payment instanceof SupplierPayment) {
            throw new Exception('Expected instance of App\Models\SupplierPayment');
        }

        $payment->loadMissing('purchaseOrder');
        $tenantId = (int) $payment->tenant_id;
        $amount = (float) $payment->amount;

        $payableAccountId = AccountSetting::getAccountId('supplier_payable', $tenantId);
        $cashAccountKey = $payment->payment_method === 'cash' ? 'pos_cash' : 'bank_payment';
        $cashAccountId = AccountSetting::getAccountId($cashAccountKey, $tenantId);

        if (! $payableAccountId || ! $cashAccountId) {
            throw new Exception("Accounting mapping missing for payment #{$payment->payment_number}. Map supplier_payable and {$cashAccountKey}.");
        }

        $poRef = $payment->purchaseOrder?->reference_no ?? $payment->purchase_order_id;

        return [
            'header' => [
                'tenant_id' => $tenantId,
                'date' => $payment->payment_date->toDateString(),
                'reference' => "PAY-{$payment->payment_number}",
                'description' => "Supplier payment for PO {$poRef}",
                'source_type' => SupplierPayment::class,
                'source_id' => $payment->id,
            ],
            'lines' => [
                [
                    'account_id' => $payableAccountId,
                    'description' => "Payment #{$payment->payment_number} — AP relief",
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $cashAccountId,
                    'description' => "Payment #{$payment->payment_number} ({$payment->payment_method})",
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
        ];
    }
}
