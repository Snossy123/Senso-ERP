<?php

namespace App\Listeners\Accounting;

use App\Events\Domain\Sales\WebOrderRecorded;
use App\Listeners\Accounting\Concerns\PostsJournalIdempotently;
use App\Models\Order;
use App\Services\AccountingService;

class PostWebOrderJournalListener
{
    use PostsJournalIdempotently;

    public function __construct(
        private readonly AccountingService $accountingService
    ) {}

    public function handle(WebOrderRecorded $event): void
    {
        if ($event->channel !== 'web') {
            return;
        }

        $order = Order::query()->with('items')->find($event->orderId);
        if (! $order) {
            return;
        }

        $this->postJournalForSource($order, $this->accountingService);

        $this->postCogsForOrder($order);
    }

    private function postCogsForOrder(Order $order): void
    {
        try {
            $generator = new \App\Services\Accounting\Generators\CogsJournalEntryGenerator;
            $jeData = $generator->generateForOrder($order);

            if ($jeData === null) {
                return;
            }

            $reference = $jeData['header']['reference'];
            $exists = \App\Models\JournalEntry::query()
                ->where('tenant_id', $order->tenant_id)
                ->where('reference', $reference)
                ->exists();

            if ($exists) {
                return;
            }

            $this->accountingService->createJournalEntry(
                $jeData['header'],
                $jeData['lines']
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
