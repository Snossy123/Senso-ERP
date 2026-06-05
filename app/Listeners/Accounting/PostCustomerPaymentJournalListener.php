<?php

namespace App\Listeners\Accounting;

use App\Events\Domain\Sales\CustomerPaymentRecorded;
use App\Listeners\Accounting\Concerns\PostsJournalIdempotently;
use App\Models\CustomerPayment;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Services\Accounting\Generators\CogsJournalEntryGenerator;
use App\Services\AccountingService;

class PostCustomerPaymentJournalListener
{
    use PostsJournalIdempotently;

    public function __construct(
        private readonly AccountingService $accountingService,
        private readonly CogsJournalEntryGenerator $cogsGenerator,
    ) {}

    public function handle(CustomerPaymentRecorded $event): void
    {
        $payment = CustomerPayment::query()
            ->with(['order.items', 'sale'])
            ->find($event->customerPaymentId);

        if (! $payment) {
            return;
        }

        if ($payment->sale_id && $payment->sale) {
            $this->postJournalForSource($payment, $this->accountingService);

            return;
        }

        if (! $payment->order) {
            return;
        }

        $this->postJournalForSource($payment, $this->accountingService);

        $revenueOnOrder = JournalEntry::query()
            ->where('source_type', Order::class)
            ->where('source_id', $payment->order->id)
            ->exists();

        if (! $revenueOnOrder) {
            $this->postCogsIfNeeded($payment->order);
        }
    }

    private function postCogsIfNeeded(Order $order): void
    {
        $jeData = $this->cogsGenerator->generateForOrder($order);
        if ($jeData === null) {
            return;
        }

        $exists = JournalEntry::query()
            ->where('tenant_id', $order->tenant_id)
            ->where('reference', $jeData['header']['reference'])
            ->exists();

        if ($exists) {
            return;
        }

        try {
            $this->accountingService->createJournalEntry(
                $jeData['header'],
                $jeData['lines']
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
