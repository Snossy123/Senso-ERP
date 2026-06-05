<?php

namespace App\Application\Sales;

use App\Events\Domain\Sales\CustomerPaymentRecorded;
use App\Models\CustomerPayment;
use App\Models\Order;
use App\Models\Sale;
use Exception;
use Illuminate\Support\Facades\DB;

class RecordCustomerPaymentService
{
    /**
     * @param  array{payment_date: string, payment_method: string, notes?: ?string}  $data
     */
    public function record(Order $order, array $data, int $userId): CustomerPayment
    {
        if ($order->payment_status === 'paid') {
            throw new Exception('This order is already marked as paid.');
        }

        if (in_array($order->status, ['cancelled'], true)) {
            throw new Exception('Cannot collect payment on a cancelled order.');
        }

        return $this->persistPayment(
            tenantId: (int) $order->tenant_id,
            orderId: $order->id,
            saleId: null,
            customerId: $order->customer_id,
            amount: (float) $order->total,
            data: $data,
            userId: $userId,
            onPaid: fn () => $order->update(['payment_status' => 'paid', 'paid_at' => now()]),
        );
    }

    /**
     * Collect payment on a credit POS sale (revenue already in AR).
     *
     * @param  array{payment_date: string, payment_method: string, notes?: ?string}  $data
     */
    public function recordForSale(Sale $sale, array $data, int $userId): CustomerPayment
    {
        if ($sale->payment_method !== 'credit') {
            throw new Exception('Only credit POS sales can be collected through AR.');
        }

        if ($sale->payment_status === 'paid') {
            throw new Exception('This sale is already paid.');
        }

        if ($sale->isVoided()) {
            throw new Exception('Cannot collect on a voided sale.');
        }

        return $this->persistPayment(
            tenantId: (int) $sale->tenant_id,
            orderId: null,
            saleId: $sale->id,
            customerId: $sale->customer_id,
            amount: (float) $sale->total,
            data: $data,
            userId: $userId,
            onPaid: fn () => $sale->update(['payment_status' => 'paid']),
        );
    }

    private function persistPayment(
        int $tenantId,
        ?int $orderId,
        ?int $saleId,
        ?int $customerId,
        float $amount,
        array $data,
        int $userId,
        callable $onPaid,
    ): CustomerPayment {
        if ($amount <= 0) {
            throw new Exception('Payment amount must be greater than zero.');
        }

        $payment = null;

        DB::transaction(function () use ($tenantId, $orderId, $saleId, $customerId, $amount, $data, $userId, $onPaid, &$payment) {
            $payment = CustomerPayment::create([
                'tenant_id' => $tenantId,
                'order_id' => $orderId,
                'sale_id' => $saleId,
                'customer_id' => $customerId,
                'payment_number' => CustomerPayment::generatePaymentNumber($tenantId),
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $onPaid();

            $paymentId = (int) $payment->id;

            DB::afterCommit(function () use ($paymentId, $tenantId) {
                event(new CustomerPaymentRecorded(
                    customerPaymentId: $paymentId,
                    tenantId: $tenantId,
                ));
            });
        });

        return $payment->fresh(['order', 'sale', 'customer']);
    }
}
