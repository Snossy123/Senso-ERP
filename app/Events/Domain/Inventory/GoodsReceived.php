<?php

namespace App\Events\Domain\Inventory;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched after a purchase order receive transaction commits.
 */
final class GoodsReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $purchaseOrderId,
        public readonly int $tenantId,
        public readonly int $payloadVersion = 1,
    ) {}
}
