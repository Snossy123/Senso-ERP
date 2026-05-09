<?php

namespace App\Observers;

use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Services\AccountingService;
use Exception;

class AccountingObserver
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Handle the Sale "created" event.
     *
     * Must not type-hint Sale: this observer is shared with PurchaseOrder; Laravel dispatches
     * created() for every observed model, so a Sale type hint causes TypeError on PurchaseOrder::created.
     */
    public function created($model): void
    {
        if ($model instanceof Sale) {
            $this->processJournalEntry($model);
        }
    }

    /**
     * Handle Purchase Order "updated" event.
     *
     * Must not type-hint PurchaseOrder: this observer is shared with Sale; Laravel dispatches
     * updated() for every observed model, so a PurchaseOrder type hint causes TypeError on Sale::updated.
     */
    public function updated($model): void
    {
        if (! $model instanceof PurchaseOrder) {
            return;
        }

        if ($model->isDirty('status') && $model->status === 'completed') {
            $this->processJournalEntry($model);
        }
    }

    /**
     * Common method to process journal entries using the factory and generators.
     */
    protected function processJournalEntry($model)
    {
        try {
            $generator = \App\Services\Accounting\JournalEntryFactory::getGenerator($model);
            $entryData = $generator->generate($model);

            $this->accountingService->createJournalEntry(
                $entryData['header'],
                $entryData['lines']
            );
        } catch (Exception $e) {
            // Log error or handle as per system requirements
            // For now, we allow the exception to bubble up as it ensures data integrity
            throw $e;
        }
    }
}
