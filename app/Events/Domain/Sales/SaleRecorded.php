<?php

namespace App\Events\Domain\Sales;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched after the POS sale transaction commits (see SaleController + DB::afterCommit).
 */
final class SaleRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $saleId,
        public readonly int $tenantId,
        public readonly string $channel = 'pos',
        public readonly int $payloadVersion = 1,
    ) {}
}
