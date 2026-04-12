<?php

namespace App\Services\Accounting;

use Illuminate\Database\Eloquent\Model;

interface JournalEntryGeneratorInterface
{
    /**
     * Generate the journal entry lines for a given model.
     *
     * @param Model $model
     * @return array ['header' => [], 'lines' => []]
     */
    public function generate(Model $model): array;
}
