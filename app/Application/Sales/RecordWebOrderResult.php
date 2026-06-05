<?php

namespace App\Application\Sales;

use App\Models\Order;
use App\Models\Product;

/**
 * Outcome of {@see RecordWebOrderService::record} after the order transaction commits.
 */
final class RecordWebOrderResult
{
    /**
     * @param  array<int, Product>  $lowStockProducts
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public readonly Order $order,
        public readonly array $lowStockProducts,
        public readonly array $warnings,
        public readonly bool $inventoryPosted,
        public readonly string $paymentStatus,
        public readonly bool $duplicate = false,
    ) {}
}
