<?php

namespace App\Application\Inventory;

/**
 * Semantic reason for a stock posting (not persisted on stock_movements yet; used for tracing and future events).
 */
enum StockMovementReason: string
{
    case GoodsReceipt = 'goods_receipt';
    case PosSale = 'pos_sale';
    case EcommerceOrder = 'ecommerce_order';
    case Refund = 'refund';
    case Void = 'void';
    case Adjustment = 'adjustment';
    case Transfer = 'transfer';
    case Manufacturing = 'manufacturing';
}
