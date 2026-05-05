<?php

namespace App\Services\Accounting;

use App\Models\PosShift;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\SaleRefund;
use App\Services\Accounting\Generators\PurchaseJournalEntryGenerator;
use App\Services\Accounting\Generators\RefundJournalEntryGenerator;
use App\Services\Accounting\Generators\SaleJournalEntryGenerator;
use App\Services\Accounting\Generators\ShiftVarianceJournalEntryGenerator;
use Exception;
use Illuminate\Database\Eloquent\Model;

class JournalEntryFactory
{
    /**
     * Get the appropriate generator for the given model.
     *
     * @throws Exception
     */
    public static function getGenerator(Model $model): JournalEntryGeneratorInterface
    {
        if ($model instanceof Sale) {
            return new SaleJournalEntryGenerator;
        }

        if ($model instanceof SaleRefund) {
            return new RefundJournalEntryGenerator;
        }

        if ($model instanceof PosShift) {
            return new ShiftVarianceJournalEntryGenerator;
        }

        if ($model instanceof PurchaseOrder) {
            return new PurchaseJournalEntryGenerator;
        }

        throw new Exception('No journal entry generator found for model: '.get_class($model));
    }
}
