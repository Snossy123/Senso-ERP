<?php

namespace App\Listeners\Accounting;

use App\Events\Domain\Sales\RefundRecorded;
use App\Listeners\Accounting\Concerns\PostsJournalIdempotently;
use App\Models\SaleRefund;
use App\Services\AccountingService;

class PostRefundJournalListener
{
    use PostsJournalIdempotently;

    public function __construct(
        private readonly AccountingService $accountingService
    ) {}

    public function handle(RefundRecorded $event): void
    {
        if ($event->channel !== 'pos') {
            return;
        }

        $refund = SaleRefund::query()->with('sale')->find($event->refundId);
        if (! $refund) {
            return;
        }

        $this->postJournalForSource($refund, $this->accountingService);
    }
}
