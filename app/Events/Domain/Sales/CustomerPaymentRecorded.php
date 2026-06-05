<?php

namespace App\Events\Domain\Sales;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CustomerPaymentRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $customerPaymentId,
        public readonly int $tenantId,
        public readonly int $payloadVersion = 1,
    ) {}
}
