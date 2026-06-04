<?php

namespace App\Application\Sales;

use App\Models\InvoiceInstallment;
use App\Models\Setting;
use App\Models\InvoicePayment;
use App\Models\InvoicePaymentAllocation;
use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use App\Services\Accounting\JournalEntryFactory;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoicePaymentService
{
    public function __construct(
        protected AccountingService $accountingService,
        protected SalesInvoiceNumberService $numberService,
    ) {}

    /**
     * @param  array{amount: float, payment_method: string, paid_at?: string, reference?: string, notes?: string, invoice_installment_id?: int}  $data
     */
    public function recordForInvoice(SalesInvoice $invoice, array $data, int $userId): InvoicePayment
    {
        if (! $invoice->isConfirmed()) {
            throw new InvalidArgumentException('Payments can only be recorded on confirmed invoices.');
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $balance = (float) $invoice->balance_due;
        if ($amount > $balance + 0.009) {
            throw new InvalidArgumentException("Payment exceeds invoice balance ({$balance}).");
        }

        return DB::transaction(function () use ($invoice, $data, $amount, $userId) {
            $payment = InvoicePayment::create([
                'tenant_id' => $invoice->tenant_id,
                'payment_number' => $this->reservePaymentNumber($invoice->tenant_id),
                'customer_id' => $invoice->customer_id,
                'user_id' => $userId,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'amount' => $amount,
                'paid_at' => $data['paid_at'] ?? now(),
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $installmentId = $data['invoice_installment_id'] ?? null;
            if ($installmentId) {
                $this->applyToInstallment($invoice, $payment, (int) $installmentId, $amount);
            } else {
                InvoicePaymentAllocation::create([
                    'tenant_id' => $invoice->tenant_id,
                    'invoice_payment_id' => $payment->id,
                    'sales_invoice_id' => $invoice->id,
                    'invoice_installment_id' => null,
                    'amount' => $amount,
                ]);
            }

            $invoice->paid_amount = round((float) $invoice->paid_amount + $amount, 2);
            $invoice->recalculatePaymentStatus();
            $invoice->save();

            $this->postPaymentJournal($payment);

            return $payment->load('allocations');
        });
    }

    protected function applyToInstallment(
        SalesInvoice $invoice,
        InvoicePayment $payment,
        int $installmentId,
        float $amount
    ): void {
        $installment = InvoiceInstallment::query()
            ->where('sales_invoice_id', $invoice->id)
            ->whereKey($installmentId)
            ->lockForUpdate()
            ->firstOrFail();

        $remaining = $installment->remainingAmount();
        if ($amount > $remaining + 0.009) {
            throw new InvalidArgumentException("Payment exceeds installment remaining ({$remaining}).");
        }

        InvoicePaymentAllocation::create([
            'tenant_id' => $invoice->tenant_id,
            'invoice_payment_id' => $payment->id,
            'sales_invoice_id' => $invoice->id,
            'invoice_installment_id' => $installment->id,
            'amount' => $amount,
        ]);

        $installment->paid_amount = round((float) $installment->paid_amount + $amount, 2);
        $installment->refreshStatus();
        $installment->save();
    }

    protected function postPaymentJournal(InvoicePayment $payment): void
    {
        $exists = JournalEntry::query()
            ->where('source_type', InvoicePayment::class)
            ->where('source_id', $payment->id)
            ->exists();

        if ($exists) {
            return;
        }

        $generator = JournalEntryFactory::getGenerator($payment);
        $jeData = $generator->generate($payment);
        $jeData['header']['tenant_id'] = $payment->tenant_id;

        $this->accountingService->createJournalEntry($jeData['header'], $jeData['lines']);
    }

    protected function reservePaymentNumber(int $tenantId): string
    {
        $seq = (int) Setting::get('invoice_payment_next_seq', 1, $tenantId);
        Setting::set('invoice_payment_next_seq', (string) ($seq + 1), 'business', $tenantId);

        return 'PAY-'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
