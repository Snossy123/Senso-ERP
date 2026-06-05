<?php

namespace App\Services\Accounting\Generators;

use App\Models\AccountSetting;
use App\Models\CustomerPayment;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\Sale;
use App\Services\Accounting\JournalEntryGeneratorInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;

class CustomerPaymentJournalEntryGenerator implements JournalEntryGeneratorInterface
{
    public function generate(Model $payment): array
    {
        if (! $payment instanceof CustomerPayment) {
            throw new Exception('Expected instance of App\Models\CustomerPayment');
        }

        $payment->loadMissing(['order.items', 'sale']);

        $tenantId = (int) $payment->tenant_id;
        $amount = (float) $payment->amount;

        $cashAccountId = $this->resolveCashAccountId($payment->payment_method, $tenantId);
        if (! $cashAccountId) {
            throw new Exception("Cash/bank account mapping missing for payment #{$payment->payment_number}");
        }

        if ($payment->sale_id && $payment->sale) {
            return $this->generateForSale($payment, $payment->sale, $tenantId, $amount, $cashAccountId);
        }

        $order = $payment->order;
        if (! $order) {
            throw new Exception("Order or sale missing for payment #{$payment->payment_number}");
        }

        $revenueAlreadyPosted = JournalEntry::query()
            ->where('source_type', Order::class)
            ->where('source_id', $order->id)
            ->exists();

        if ($revenueAlreadyPosted) {
            return $this->collectionEntry($payment, $order->order_number, $tenantId, $amount, $cashAccountId);
        }

        return $this->revenueOnPaymentEntry($payment, $order, $tenantId, $amount, $cashAccountId);
    }

    private function generateForSale(
        CustomerPayment $payment,
        Sale $sale,
        int $tenantId,
        float $amount,
        int $cashAccountId,
    ): array {
        $revenuePosted = JournalEntry::query()
            ->where('source_type', Sale::class)
            ->where('source_id', $sale->id)
            ->exists();

        if ($revenuePosted) {
            return $this->collectionEntry($payment, $sale->sale_number, $tenantId, $amount, $cashAccountId);
        }

        return $this->revenueOnPaymentForSale($payment, $sale, $tenantId, $amount, $cashAccountId);
    }

    private function collectionEntry(
        CustomerPayment $payment,
        string $documentRef,
        int $tenantId,
        float $amount,
        int $cashAccountId,
    ): array {
        $receivableAccountId = AccountSetting::getAccountId('customer_receivable', $tenantId);
        if (! $receivableAccountId) {
            throw new Exception("AR account mapping missing for payment #{$payment->payment_number}");
        }

        return [
            'header' => [
                'tenant_id' => $tenantId,
                'date' => $payment->payment_date->toDateString(),
                'reference' => "RCPT-{$payment->payment_number}",
                'description' => "Cash receipt for {$documentRef}",
                'source_type' => CustomerPayment::class,
                'source_id' => $payment->id,
            ],
            'lines' => [
                [
                    'account_id' => $cashAccountId,
                    'description' => "Receipt #{$payment->payment_number}",
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $receivableAccountId,
                    'description' => "AR relief — {$documentRef}",
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
        ];
    }

    private function revenueOnPaymentEntry(
        CustomerPayment $payment,
        Order $order,
        int $tenantId,
        float $amount,
        int $cashAccountId,
    ): array {
        $salesAccountId = AccountSetting::getAccountId('sales_revenue', $tenantId);
        $taxAccountId = AccountSetting::getAccountId('tax_payable', $tenantId);

        if (! $salesAccountId) {
            throw new Exception("Sales revenue mapping missing for payment #{$payment->payment_number}");
        }

        $taxAmount = (float) ($order->tax_amount ?? 0);
        $revenueAmount = (float) $order->total - $taxAmount;

        $lines = [
            [
                'account_id' => $cashAccountId,
                'description' => "Web Order #{$order->order_number} (paid)",
                'debit' => $amount,
                'credit' => 0,
            ],
            [
                'account_id' => $salesAccountId,
                'description' => "Web Order Revenue #{$order->order_number}",
                'debit' => 0,
                'credit' => $revenueAmount,
            ],
        ];

        if ($taxAmount > 0) {
            if (! $taxAccountId) {
                throw new Exception("Tax account mapping missing for Order #{$order->order_number}");
            }
            $lines[] = [
                'account_id' => $taxAccountId,
                'description' => "Tax on Order #{$order->order_number}",
                'debit' => 0,
                'credit' => $taxAmount,
            ];
        }

        return [
            'header' => [
                'tenant_id' => $tenantId,
                'date' => $payment->payment_date->toDateString(),
                'reference' => "RCPT-{$payment->payment_number}",
                'description' => "Revenue on payment for Order {$order->order_number}",
                'source_type' => CustomerPayment::class,
                'source_id' => $payment->id,
            ],
            'lines' => $lines,
        ];
    }

    private function revenueOnPaymentForSale(
        CustomerPayment $payment,
        Sale $sale,
        int $tenantId,
        float $amount,
        int $cashAccountId,
    ): array {
        $salesAccountId = AccountSetting::getAccountId('sales_revenue', $tenantId);
        $taxAccountId = AccountSetting::getAccountId('tax_payable', $tenantId);

        if (! $salesAccountId) {
            throw new Exception("Sales revenue mapping missing for payment #{$payment->payment_number}");
        }

        $taxAmount = (float) ($sale->tax_amount ?? 0);
        $revenueAmount = (float) $sale->subtotal - (float) ($sale->discount_amount ?? 0);

        $lines = [
            [
                'account_id' => $cashAccountId,
                'description' => "POS Sale #{$sale->sale_number} (paid)",
                'debit' => $amount,
                'credit' => 0,
            ],
            [
                'account_id' => $salesAccountId,
                'description' => "POS Sale Revenue #{$sale->sale_number}",
                'debit' => 0,
                'credit' => $revenueAmount,
            ],
        ];

        if ($taxAmount > 0 && $taxAccountId) {
            $lines[] = [
                'account_id' => $taxAccountId,
                'description' => "Tax on Sale #{$sale->sale_number}",
                'debit' => 0,
                'credit' => $taxAmount,
            ];
        }

        return [
            'header' => [
                'tenant_id' => $tenantId,
                'date' => $payment->payment_date->toDateString(),
                'reference' => "RCPT-{$payment->payment_number}",
                'description' => "Revenue on payment for Sale {$sale->sale_number}",
                'source_type' => CustomerPayment::class,
                'source_id' => $payment->id,
            ],
            'lines' => $lines,
        ];
    }

    private function resolveCashAccountId(string $method, int $tenantId): ?int
    {
        $key = match ($method) {
            'cash' => 'pos_cash',
            'card' => 'pos_card',
            'bank_transfer' => 'bank_payment',
            default => 'pos_cash',
        };

        return AccountSetting::getAccountId($key, $tenantId)
            ?? AccountSetting::getAccountId('cash_customer', $tenantId);
    }
}
