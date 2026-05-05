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
     */
    public function created(Sale $sale)
    {
        $this->processJournalEntry($sale);
    }

    /**
     * Handle Purchase Order "updated" event
     */
    public function updated(PurchaseOrder $order)
    {
        if ($order->isDirty('status') && $order->status === 'completed') {
            $this->processJournalEntry($order);
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
