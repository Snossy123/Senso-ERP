<?php

namespace App\Services\Accounting\Generators;

use App\Models\AccountSetting;
use App\Models\Order;
use App\Services\Accounting\JournalEntryGeneratorInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;

class OrderJournalEntryGenerator implements JournalEntryGeneratorInterface
{
    public function generate(Model $order): array
    {
        if (! $order instanceof Order) {
            throw new Exception('Expected instance of App\Models\Order');
        }

        $order->loadMissing('items');
        $tenantId = (int) $order->tenant_id;

        $isCod = $order->payment_method === 'cash_on_delivery';
        $paymentAccountKey = $isCod ? 'customer_receivable' : 'pos_card';
        $paymentAccountId = AccountSetting::getAccountId($paymentAccountKey, $tenantId)
            ?? AccountSetting::getAccountId('cash_customer', $tenantId);
        $salesAccountId = AccountSetting::getAccountId('sales_revenue', $tenantId);
        $taxAccountId = AccountSetting::getAccountId('tax_payable', $tenantId);

        if (! $paymentAccountId || ! $salesAccountId) {
            throw new Exception("Accounting mapping missing for Order #{$order->order_number}.");
        }

        $taxAmount = (float) ($order->tax_amount ?? 0);
        $revenueAmount = (float) $order->total - $taxAmount;

        $lines = [
            [
                'account_id' => $paymentAccountId,
                'description' => "Web Order #{$order->order_number} ({$order->payment_method})",
                'debit' => (float) $order->total,
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
                'date' => $order->created_at?->toDateString() ?? now()->toDateString(),
                'reference' => "ORDER-{$order->order_number}",
                'description' => "Journal Entry for Web Order {$order->order_number}",
                'source_type' => Order::class,
                'source_id' => $order->id,
            ],
            'lines' => $lines,
        ];
    }
}
