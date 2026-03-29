<?php

namespace App\Observers;

use App\Models\Sale;
use App\Models\PurchaseOrder;
use App\Services\AccountingService;
use App\Models\Setting;
use App\Models\Account;

class AccountingObserver
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Handle the Sale "created" event.
     * When a POS sale is registered, trigger the accounting journal entry.
     */
    public function createdSale(Sale $sale)
    {
        // Get Default Accounts from Settings. Examples:
        $cashAccountId = Setting::get('default_cash_account_id');
        $salesAccountId = Setting::get('default_sales_account_id');
        $taxAccountId = Setting::get('default_tax_account_id'); // Optional
        
        if (!$cashAccountId || !$salesAccountId) {
            return; // Config missing, skipping auto-accounting
        }

        $lines = [];
        
        // Debit: Cash/Bank Account (Asset increases)
        $lines[] = [
            'account_id' => $cashAccountId,
            'description' => "POS Sale #{$sale->invoice_number}",
            'debit' => $sale->total_amount,
            'credit' => 0,
        ];

        // Credit: Sales Revenue (Revenue increases)
        // If there's tax, we split the credit
        $taxAmount = $sale->tax_amount ?? 0;
        $revenueAmount = $sale->total_amount - $taxAmount;

        $lines[] = [
            'account_id' => $salesAccountId,
            'description' => "POS Sale Revenue #{$sale->invoice_number}",
            'debit' => 0,
            'credit' => $revenueAmount,
        ];

        if ($taxAmount > 0 && $taxAccountId) {
            $lines[] = [
                'account_id' => $taxAccountId,
                'description' => "Tax on Sale #{$sale->invoice_number}",
                'debit' => 0,
                'credit' => $taxAmount,
            ];
        }

        $this->accountingService->createJournalEntry([
            'tenant_id' => $sale->tenant_id,
            'date' => now()->toDateString(),
            'reference' => "SALE-{$sale->invoice_number}",
            'description' => "Journal Entry for POS Sale {$sale->invoice_number}",
            'source_type' => get_class($sale),
            'source_id' => $sale->id,
        ], $lines);
    }

    /**
     * Handle Purchase Order "completed" event
     */
    public function completedPurchase(PurchaseOrder $order)
    {
        $inventoryAccountId = Setting::get('default_inventory_account_id');
        $payableAccountId = Setting::get('default_payable_account_id');

        if (!$inventoryAccountId || !$payableAccountId) {
            return;
        }

        $lines = [
            // Debit Inventory (Asset increases)
            [
                'account_id' => $inventoryAccountId,
                'description' => "Purchase Order #{$order->reference}",
                'debit' => $order->total_amount,
                'credit' => 0,
            ],
            // Credit Accounts Payable (Liability increases)
            [
                'account_id' => $payableAccountId,
                'description' => "Purchase Order #{$order->reference}",
                'debit' => 0,
                'credit' => $order->total_amount,
            ]
        ];

        $this->accountingService->createJournalEntry([
            'tenant_id' => $order->tenant_id,
            'date' => now()->toDateString(),
            'reference' => "PO-{$order->reference}",
            'description' => "Journal Entry for Purchase Order {$order->reference}",
            'source_type' => get_class($order),
            'source_id' => $order->id,
        ], $lines);
    }
}
