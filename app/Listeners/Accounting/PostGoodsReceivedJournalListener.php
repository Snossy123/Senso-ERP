<?php

namespace App\Listeners\Accounting;

use App\Events\Domain\Inventory\GoodsReceived;
use App\Listeners\Accounting\Concerns\PostsJournalIdempotently;
use App\Models\PurchaseOrder;
use App\Services\AccountingService;

class PostGoodsReceivedJournalListener
{
    use PostsJournalIdempotently;

    public function __construct(
        private readonly AccountingService $accountingService
    ) {}

    public function handle(GoodsReceived $event): void
    {
        $order = PurchaseOrder::query()->find($event->purchaseOrderId);
        if (! $order || $order->status !== 'received') {
            return;
        }

        $this->postJournalForSource($order, $this->accountingService);
    }
}
