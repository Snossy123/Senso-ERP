<?php

namespace App\Observers;

use App\Models\PurchaseOrder;
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
     * Handle Purchase Order "updated" event.
     *
     * Must not type-hint PurchaseOrder: Laravel dispatches updated() for observed models;
     * a PurchaseOrder-only type hint would TypeError for other observed models.
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
