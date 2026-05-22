<?php

namespace App\Services\Accounting\Generators;

use App\Models\AccountSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;

class CogsJournalEntryGenerator
{
    /**
     * @return array{header: array, lines: array}|null
     */
    public function generateForSale(Sale $sale): ?array
    {
        $sale->loadMissing(['items.product']);
        $tenantId = (int) $sale->tenant_id;
        $cogsAccountId = AccountSetting::getAccountId('cogs_account', $tenantId);
        $inventoryAccountId = AccountSetting::getAccountId('inventory_account', $tenantId);

        if (! $cogsAccountId || ! $inventoryAccountId) {
            return null;
        }

        $totalCogs = $this->sumSaleItemCosts($sale->items);
        if ($totalCogs <= 0) {
            return null;
        }

        return $this->buildEntry(
            tenantId: $tenantId,
            reference: "COGS-SALE-{$sale->sale_number}",
            description: "COGS for POS Sale {$sale->sale_number}",
            date: $sale->created_at?->toDateString() ?? now()->toDateString(),
            cogsAccountId: $cogsAccountId,
            inventoryAccountId: $inventoryAccountId,
            totalCogs: $totalCogs,
            sourceType: Sale::class,
            sourceId: $sale->id,
        );
    }

    /**
     * @return array{header: array, lines: array}|null
     */
    public function generateForOrder(Order $order): ?array
    {
        $order->loadMissing('items');
        $tenantId = (int) $order->tenant_id;
        $cogsAccountId = AccountSetting::getAccountId('cogs_account', $tenantId);
        $inventoryAccountId = AccountSetting::getAccountId('inventory_account', $tenantId);

        if (! $cogsAccountId || ! $inventoryAccountId) {
            return null;
        }

        $totalCogs = 0.0;
        foreach ($order->items as $item) {
            $product = Product::find($item->product_id);
            $unitCost = (float) ($product?->purchase_price ?? 0);
            $totalCogs += $unitCost * (int) $item->quantity;
        }

        if ($totalCogs <= 0) {
            return null;
        }

        return $this->buildEntry(
            tenantId: $tenantId,
            reference: "COGS-ORDER-{$order->order_number}",
            description: "COGS for Web Order {$order->order_number}",
            date: $order->created_at?->toDateString() ?? now()->toDateString(),
            cogsAccountId: $cogsAccountId,
            inventoryAccountId: $inventoryAccountId,
            totalCogs: $totalCogs,
            sourceType: Order::class,
            sourceId: $order->id,
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SaleItem>  $items
     */
    private function sumSaleItemCosts($items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $product = $item->product;
            $unitCost = (float) ($product?->purchase_price ?? 0);
            $total += $unitCost * (int) $item->quantity;
        }

        return round($total, 2);
    }

    private function buildEntry(
        int $tenantId,
        string $reference,
        string $description,
        string $date,
        int $cogsAccountId,
        int $inventoryAccountId,
        float $totalCogs,
        string $sourceType,
        int $sourceId,
    ): array {
        return [
            'header' => [
                'tenant_id' => $tenantId,
                'date' => $date,
                'reference' => $reference,
                'description' => $description,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ],
            'lines' => [
                [
                    'account_id' => $cogsAccountId,
                    'description' => $description,
                    'debit' => $totalCogs,
                    'credit' => 0,
                ],
                [
                    'account_id' => $inventoryAccountId,
                    'description' => $description,
                    'debit' => 0,
                    'credit' => $totalCogs,
                ],
            ],
        ];
    }
}
