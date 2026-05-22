<?php

namespace App\Listeners\Accounting;

use App\Events\Domain\Inventory\SupplierPaymentRecorded;
use App\Listeners\Accounting\Concerns\PostsJournalIdempotently;
use App\Models\SupplierPayment;
use App\Services\AccountingService;

class PostSupplierPaymentJournalListener
{
    use PostsJournalIdempotently;

    public function __construct(
        private readonly AccountingService $accountingService
    ) {}

    public function handle(SupplierPaymentRecorded $event): void
    {
        $payment = SupplierPayment::query()->with('purchaseOrder')->find($event->supplierPaymentId);
        if (! $payment) {
            return;
        }

        $this->postJournalForSource($payment, $this->accountingService);
    }
}
