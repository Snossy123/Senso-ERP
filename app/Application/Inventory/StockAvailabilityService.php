<?php

namespace App\Application\Inventory;

use App\Models\Product;
use App\Models\ProductWarehouseStock;
use InvalidArgumentException;

/**
 * Unified stock availability checks (ADR-001 interim: rolled-up is canonical for validation;
 * warehouse slice used when warehouse_id is provided and a row exists).
 */
class StockAvailabilityService
{
    public function availableQuantity(
        Product $product,
        ?int $warehouseId = null,
        ?int $productVariantId = null
    ): int {
        if ($warehouseId !== null) {
            $warehouseQty = ProductWarehouseStock::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $product->tenant_id)
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouseId)
                ->when(
                    $productVariantId,
                    fn ($q) => $q->where('product_variant_id', $productVariantId),
                    fn ($q) => $q->whereNull('product_variant_id')
                )
                ->value('quantity');

            if ($warehouseQty !== null) {
                return max(0, (int) $warehouseQty);
            }
        }

        return max(0, (int) $product->stock_quantity);
    }

    public function assertAvailable(
        Product $product,
        int $quantity,
        ?int $warehouseId = null,
        ?int $productVariantId = null
    ): void {
        if ($quantity < 1) {
            throw new InvalidArgumentException("Invalid quantity for product: {$product->name}.");
        }

        $available = $this->availableQuantity($product, $warehouseId, $productVariantId);

        if ($available < $quantity) {
            $context = $warehouseId ? " (warehouse #{$warehouseId})" : '';
            throw new InvalidArgumentException(
                "Insufficient stock for {$product->name}{$context}. Available: {$available}"
            );
        }
    }
}
