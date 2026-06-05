<?php

namespace App\Events\Domain\Sales;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched after an ecommerce order is ready for revenue recognition (per tenant policy).
 */
final class WebOrderRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $orderId,
        public readonly int $tenantId,
        public readonly string $channel = 'web',
        public readonly int $payloadVersion = 1,
    ) {}
}
