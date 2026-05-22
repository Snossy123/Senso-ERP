<?php

namespace App\Events\Domain\Inventory;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SupplierPaymentRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $supplierPaymentId,
        public readonly int $tenantId,
        public readonly int $payloadVersion = 1,
    ) {}
}
