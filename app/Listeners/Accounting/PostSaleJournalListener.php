<?php

namespace App\Listeners\Accounting;

use App\Events\Domain\Sales\SaleRecorded;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Services\AccountingService;
use Throwable;

/**
 * Creates exactly one journal entry per POS sale (idempotent by source Sale).
 */
class PostSaleJournalListener
{
    public function __construct(
        private readonly AccountingService $accountingService
    ) {}

    public function handle(SaleRecorded $event): void
    {
        if ($event->channel !== 'pos') {
            return;
        }

        $sale = Sale::query()->with('items')->find($event->saleId);
        if (! $sale) {
            return;
        }

        $alreadyPosted = JournalEntry::query()
            ->where('source_type', Sale::class)
            ->where('source_id', $sale->id)
            ->exists();

        if ($alreadyPosted) {
            return;
        }

        try {
            $generator = \App\Services\Accounting\JournalEntryFactory::getGenerator($sale);
            $jeData = $generator->generate($sale);

            $this->accountingService->createJournalEntry(
                $jeData['header'],
                $jeData['lines']
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}
