<?php

namespace App\Events\Domain\Sales;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched after a POS refund transaction commits.
 */
final class RefundRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $refundId,
        public readonly int $tenantId,
        public readonly string $channel = 'pos',
        public readonly int $payloadVersion = 1,
    ) {}
}
