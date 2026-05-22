<?php

namespace App\Application\Inventory;

use App\Events\Domain\Inventory\SupplierPaymentRecorded;
use App\Models\JournalEntry;
use App\Models\PurchaseOrder;
use App\Models\SupplierPayment;
use Exception;
use Illuminate\Support\Facades\DB;

class RecordSupplierPaymentService
{
    /**
     * @param  array{payment_date: string, payment_method: string, notes?: ?string, amount?: ?float}  $data
     */
    public function record(PurchaseOrder $order, array $data, int $userId): SupplierPayment
    {
        if ($order->status !== 'received') {
            throw new Exception('Only received purchase orders can be paid.');
        }

        if ($order->payment_status === 'paid') {
            throw new Exception('This purchase order is already paid.');
        }

        $hasReceiptJournal = JournalEntry::query()
            ->where('source_type', PurchaseOrder::class)
            ->where('source_id', $order->id)
            ->exists();

        if (! $hasReceiptJournal) {
            throw new Exception('Cannot pay: goods receipt journal entry is missing. Receive stock and post GRNI first.');
        }

        $amount = isset($data['amount']) ? (float) $data['amount'] : (float) $order->total_amount;
        if ($amount <= 0) {
            throw new Exception('Payment amount must be greater than zero.');
        }

        if (abs($amount - (float) $order->total_amount) > 0.01) {
            throw new Exception('Partial payments are not supported yet. Pay the full PO amount.');
        }

        $payment = null;

        DB::transaction(function () use ($order, $data, $userId, $amount, &$payment) {
            $payment = SupplierPayment::create([
                'tenant_id' => $order->tenant_id,
                'purchase_order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'payment_number' => SupplierPayment::generatePaymentNumber((int) $order->tenant_id),
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            $paymentId = (int) $payment->id;
            $tenantId = (int) $order->tenant_id;

            DB::afterCommit(function () use ($paymentId, $tenantId) {
                event(new SupplierPaymentRecorded(
                    supplierPaymentId: $paymentId,
                    tenantId: $tenantId,
                ));
            });
        });

        return $payment->fresh(['purchaseOrder', 'supplier']);
    }
}
