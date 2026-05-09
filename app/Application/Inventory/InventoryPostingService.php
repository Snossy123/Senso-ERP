<?php

namespace App\Application\Inventory;

use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\StockMovement;
use InvalidArgumentException;

/**
 * Single write path for inventory ledger rows (stock_movements, product_warehouse_stocks, products.stock_quantity).
 * Slice B: PO receive uses {@see postInbound}. Slice C: ecommerce checkout uses {@see postOutbound}.
 * Slice D: POS sale lines use {@see postOutbound} ({@see StockPostingData::forPosSaleLine}).
 * Slice F: POS void/refund restock uses {@see postInbound} ({@see StockPostingData::forPosVoidLine}, {@see StockPostingData::forPosRefundLine}).
 */
class InventoryPostingService
{
    /**
     * Inbound: increment warehouse slice (when warehouse set), rolled-up product qty, record movement (type in).
     * Matches legacy PurchaseOrderController::receive ordering and fields.
     */
    public function postInbound(StockPostingData $data): StockMovement
    {
        if ($data->type !== 'in') {
            throw new InvalidArgumentException('postInbound expects type "in".');
        }
        if ($data->quantity < 1) {
            throw new InvalidArgumentException('Inbound quantity must be >= 1.');
        }

        return $this->executeInboundLikePurchaseOrderReceive($data);
    }

    /**
     * Outbound: rolled-up product qty first (matches legacy POS), optional warehouse slice, then movement (type out).
     * Ecommerce uses warehouse_id=null; numeric outcome matches warehouse-first for qty-only semantics.
     */
    public function postOutbound(StockPostingData $data): StockMovement
    {
        if ($data->type !== 'out') {
            throw new InvalidArgumentException('postOutbound expects type "out".');
        }
        if ($data->quantity < 1) {
            throw new InvalidArgumentException('Outbound quantity must be >= 1.');
        }

        $product = $this->resolveProduct($data);

        $beforeQty = (int) $product->stock_quantity;
        $product->decrement('stock_quantity', $data->quantity);
        $product->refresh();

        if ($data->warehouseId !== null) {
            ProductWarehouseStock::query()->withoutGlobalScopes()->updateOrCreate(
                [
                    'product_id' => $data->productId,
                    'product_variant_id' => $data->productVariantId,
                    'warehouse_id' => $data->warehouseId,
                ],
                ['tenant_id' => $data->tenantId]
            )->decrement('quantity', $data->quantity);
        }

        return StockMovement::query()->create([
            'tenant_id' => $data->tenantId,
            'product_id' => $data->productId,
            'product_variant_id' => $data->productVariantId,
            'warehouse_id' => $data->warehouseId,
            'purchase_order_id' => $data->purchaseOrderId,
            'stock_transfer_id' => $data->stockTransferId,
            'type' => 'out',
            'quantity' => $data->quantity,
            'before_quantity' => $beforeQty,
            'after_quantity' => (int) $product->stock_quantity,
            'unit_cost' => $data->unitCost ?? 0,
            'total_value' => $data->totalValue ?? 0,
            'reference' => $data->reference,
            'notes' => $data->notes,
            'user_id' => $data->userId,
        ]);
    }

    /**
     * Legacy adjustment: rolled-up stock set to absolute value; optional warehouse row not touched (matches StockMovementController).
     */
    public function postAdjustment(StockPostingData $data): StockMovement
    {
        if ($data->type !== 'adjustment') {
            throw new InvalidArgumentException('postAdjustment expects type "adjustment".');
        }
        if ($data->absoluteTargetStock === null) {
            throw new InvalidArgumentException('Adjustment requires absoluteTargetStock.');
        }

        $product = $this->resolveProduct($data);
        $beforeQty = (int) $product->stock_quantity;

        $product->update(['stock_quantity' => $data->absoluteTargetStock]);

        return StockMovement::query()->create([
            'tenant_id' => $data->tenantId,
            'product_id' => $data->productId,
            'product_variant_id' => $data->productVariantId,
            'warehouse_id' => $data->warehouseId,
            'purchase_order_id' => $data->purchaseOrderId,
            'stock_transfer_id' => $data->stockTransferId,
            'type' => 'adjustment',
            'quantity' => $data->quantity,
            'before_quantity' => $beforeQty,
            'after_quantity' => $data->absoluteTargetStock,
            'unit_cost' => $data->unitCost ?? 0,
            'total_value' => $data->totalValue ?? 0,
            'reference' => $data->reference,
            'notes' => $data->notes,
            'user_id' => $data->userId,
        ]);
    }

    /**
     * Mirrors PurchaseOrderController::receive per line (warehouse → rolled-up → movement).
     */
    private function executeInboundLikePurchaseOrderReceive(StockPostingData $data): StockMovement
    {
        if ($data->warehouseId !== null) {
            $warehouseStock = ProductWarehouseStock::query()->withoutGlobalScopes()->firstOrCreate(
                [
                    'product_id' => $data->productId,
                    'product_variant_id' => $data->productVariantId,
                    'warehouse_id' => $data->warehouseId,
                ],
                [
                    'tenant_id' => $data->tenantId,
                    'quantity' => 0,
                ]
            );
            $warehouseStock->increment('quantity', $data->quantity);
        }

        $product = $this->resolveProduct($data);
        $beforeQty = (int) $product->stock_quantity;
        $product->increment('stock_quantity', $data->quantity);
        $product->refresh();

        return StockMovement::query()->create([
            'tenant_id' => $data->tenantId,
            'product_id' => $data->productId,
            'product_variant_id' => $data->productVariantId,
            'warehouse_id' => $data->warehouseId,
            'purchase_order_id' => $data->purchaseOrderId,
            'stock_transfer_id' => $data->stockTransferId,
            'type' => 'in',
            'quantity' => $data->quantity,
            'before_quantity' => $beforeQty,
            'after_quantity' => (int) $product->stock_quantity,
            'unit_cost' => $data->unitCost ?? 0,
            'total_value' => $data->totalValue ?? 0,
            'reference' => $data->reference,
            'notes' => $data->notes,
            'user_id' => $data->userId,
        ]);
    }

    private function resolveProduct(StockPostingData $data): Product
    {
        return Product::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $data->tenantId)
            ->whereKey($data->productId)
            ->firstOrFail();
    }
}
