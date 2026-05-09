<?php

namespace App\Application\Inventory;

use InvalidArgumentException;

/**
 * Input for InventoryPostingService. Keep constructor permissive; the service validates per operation.
 *
 * @phpstan-type MovementType 'in'|'out'|'adjustment'
 */
final readonly class StockPostingData
{
    /**
     * @param  MovementType  $type
     */
    public function __construct(
        public int $tenantId,
        public int $productId,
        public ?int $productVariantId,
        public ?int $warehouseId,
        public int $quantity,
        public string $type,
        public ?float $unitCost = null,
        public ?float $totalValue = null,
        public ?string $reference = null,
        public ?string $notes = null,
        public ?int $userId = null,
        public ?int $purchaseOrderId = null,
        public ?int $stockTransferId = null,
        public ?StockMovementReason $reason = null,
        /**
         * Legacy adjustment semantics (see StockMovementController): absolute rolled-up stock level.
         * Only used when $type === 'adjustment'.
         */
        public ?int $absoluteTargetStock = null,
    ) {
        if (! in_array($type, ['in', 'out', 'adjustment'], true)) {
            throw new InvalidArgumentException("Invalid movement type [{$type}].");
        }
    }

    public static function forGoodsReceipt(
        int $tenantId,
        int $productId,
        ?int $productVariantId,
        int $warehouseId,
        int $quantity,
        float $unitCost,
        float $totalValue,
        string $reference,
        string $notes,
        ?int $userId,
        ?int $purchaseOrderId = null,
    ): self {
        return new self(
            tenantId: $tenantId,
            productId: $productId,
            productVariantId: $productVariantId,
            warehouseId: $warehouseId,
            quantity: $quantity,
            type: 'in',
            unitCost: $unitCost,
            totalValue: $totalValue,
            reference: $reference,
            notes: $notes,
            userId: $userId,
            purchaseOrderId: $purchaseOrderId,
            stockTransferId: null,
            reason: StockMovementReason::GoodsReceipt,
        );
    }

    /**
     * Web store checkout outbound (no warehouse slice today — rolled-up stock only).
     */
    public static function forEcommerceOrderLine(
        int $tenantId,
        int $productId,
        int $quantity,
        string $orderNumber,
        string $notes = 'Ecommerce Order',
    ): self {
        return new self(
            tenantId: $tenantId,
            productId: $productId,
            productVariantId: null,
            warehouseId: null,
            quantity: $quantity,
            type: 'out',
            unitCost: null,
            totalValue: null,
            reference: $orderNumber,
            notes: $notes,
            userId: null,
            purchaseOrderId: null,
            stockTransferId: null,
            reason: StockMovementReason::EcommerceOrder,
        );
    }

    /**
     * POS sale line outbound (rolled-up validation remains on parent product; warehouse optional from shift).
     */
    public static function forPosSaleLine(
        int $tenantId,
        int $productId,
        ?int $productVariantId,
        ?int $warehouseId,
        int $quantity,
        float $unitCost,
        float $totalValue,
        string $saleNumber,
        int $userId,
        string $notes = 'POS Sale',
    ): self {
        return new self(
            tenantId: $tenantId,
            productId: $productId,
            productVariantId: $productVariantId,
            warehouseId: $warehouseId,
            quantity: $quantity,
            type: 'out',
            unitCost: $unitCost,
            totalValue: $totalValue,
            reference: $saleNumber,
            notes: $notes,
            userId: $userId,
            purchaseOrderId: null,
            stockTransferId: null,
            reason: StockMovementReason::PosSale,
        );
    }

    /**
     * POS void: full line quantity inbound (warehouse slice when shift had warehouse).
     */
    public static function forPosVoidLine(
        int $tenantId,
        int $productId,
        ?int $productVariantId,
        ?int $warehouseId,
        int $quantity,
        float $unitCost,
        float $totalValue,
        string $reference,
        int $userId,
        string $notes = 'Voided POS Sale',
    ): self {
        return new self(
            tenantId: $tenantId,
            productId: $productId,
            productVariantId: $productVariantId,
            warehouseId: $warehouseId,
            quantity: $quantity,
            type: 'in',
            unitCost: $unitCost,
            totalValue: $totalValue,
            reference: $reference,
            notes: $notes,
            userId: $userId,
            purchaseOrderId: null,
            stockTransferId: null,
            reason: StockMovementReason::Void,
        );
    }

    /**
     * POS refund restock: prorated quantity inbound (same warehouse/variant semantics as the sale line).
     */
    public static function forPosRefundLine(
        int $tenantId,
        int $productId,
        ?int $productVariantId,
        ?int $warehouseId,
        int $quantity,
        float $unitCost,
        float $totalValue,
        string $reference,
        int $userId,
        string $notes = 'POS Refund',
    ): self {
        return new self(
            tenantId: $tenantId,
            productId: $productId,
            productVariantId: $productVariantId,
            warehouseId: $warehouseId,
            quantity: $quantity,
            type: 'in',
            unitCost: $unitCost,
            totalValue: $totalValue,
            reference: $reference,
            notes: $notes,
            userId: $userId,
            purchaseOrderId: null,
            stockTransferId: null,
            reason: StockMovementReason::Refund,
        );
    }
}
