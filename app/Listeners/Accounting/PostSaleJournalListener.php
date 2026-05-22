<?php

namespace App\Listeners\Accounting;

use App\Events\Domain\Sales\SaleRecorded;
use App\Listeners\Accounting\Concerns\PostsJournalIdempotently;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Services\Accounting\Generators\CogsJournalEntryGenerator;
use App\Services\Accounting\Generators\PaymentFeeJournalEntryGenerator;
use App\Services\AccountingService;

/**
 * Creates exactly one revenue journal entry per POS sale (idempotent by source Sale).
 */
class PostSaleJournalListener
{
    use PostsJournalIdempotently;

    public function __construct(
        private readonly AccountingService $accountingService,
        private readonly CogsJournalEntryGenerator $cogsGenerator,
        private readonly PaymentFeeJournalEntryGenerator $feeGenerator,
    ) {}

    public function handle(SaleRecorded $event): void
    {
        if ($event->channel !== 'pos') {
            return;
        }

        $sale = Sale::query()->with(['items.product'])->find($event->saleId);
        if (! $sale) {
            return;
        }

        $this->postJournalForSource($sale, $this->accountingService);
        $this->postPaymentFeeIfNeeded($sale);
        $this->postCogsIfNeeded($sale);
    }

    private function postPaymentFeeIfNeeded(Sale $sale): void
    {
        $fee = $this->feeGenerator->feeAmountForSale($sale);
        if ($fee > 0) {
            $sale->update(['payment_fee_amount' => $fee]);
        }

        $jeData = $this->feeGenerator->generateForSale($sale);
        if ($jeData === null) {
            return;
        }

        $exists = JournalEntry::query()
            ->where('tenant_id', $sale->tenant_id)
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

    private function postCogsIfNeeded(Sale $sale): void
    {
        $jeData = $this->cogsGenerator->generateForSale($sale);
        if ($jeData === null) {
            return;
        }

        $exists = JournalEntry::query()
            ->where('tenant_id', $sale->tenant_id)
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
