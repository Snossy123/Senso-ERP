<?php

namespace App\Listeners\Accounting\Concerns;

use App\Models\JournalEntry;
use App\Services\Accounting\JournalEntryFactory;
use App\Services\AccountingService;
use Illuminate\Database\Eloquent\Model;
use Throwable;

trait PostsJournalIdempotently
{
    protected function postJournalForSource(Model $source, AccountingService $accountingService): void
    {
        $alreadyPosted = JournalEntry::query()
            ->where('source_type', $source::class)
            ->where('source_id', $source->id)
            ->exists();

        if ($alreadyPosted) {
            return;
        }

        try {
            $generator = JournalEntryFactory::getGenerator($source);
            $jeData = $generator->generate($source);

            $accountingService->createJournalEntry(
                $jeData['header'],
                $jeData['lines']
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}
