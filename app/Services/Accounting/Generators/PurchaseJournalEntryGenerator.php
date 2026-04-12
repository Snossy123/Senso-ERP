<?php

namespace App\Services\Accounting\Generators;

use App\Models\PurchaseOrder;
use App\Models\AccountSetting;
use App\Services\Accounting\JournalEntryGeneratorInterface;
use Illuminate\Database\Eloquent\Model;
use Exception;

class PurchaseJournalEntryGenerator implements JournalEntryGeneratorInterface
{
    public function generate(Model $order): array
    {
        if (!$order instanceof PurchaseOrder) {
            throw new Exception("Expected instance of App\Models\PurchaseOrder");
        }

        $tenantId = $order->tenant_id;
        
        $inventoryAccountId = AccountSetting::getAccountId('inventory_account', $tenantId);
        $payableAccountId = AccountSetting::getAccountId('supplier_payable', $tenantId);

        if (!$inventoryAccountId || !$payableAccountId) {
            throw new Exception("Accounting Mapping Missing for Purchase Order #{$order->reference_no}");
        }

        $lines = [
            [
                'account_id' => $inventoryAccountId,
                'description' => "Purchase Order #{$order->reference_no}",
                'debit' => $order->total_amount,
                'credit' => 0,
            ],
            [
                'account_id' => $payableAccountId,
                'description' => "Purchase Order #{$order->reference_no}",
                'debit' => 0,
                'credit' => $order->total_amount,
            ]
        ];

        return [
            'header' => [
                'tenant_id' => $order->tenant_id,
                'date' => now()->toDateString(),
                'reference' => "PO-{$order->reference_no}",
                'description' => "Journal Entry for Purchase Order {$order->reference_no}",
                'source_type' => get_class($order),
                'source_id' => $order->id,
            ],
            'lines' => $lines
        ];
    }
}
