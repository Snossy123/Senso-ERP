<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Tenant;
use App\Services\Accounting\FinancialPeriodService;
use App\Services\Accounting\JournalEntryLimitService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    public function __construct(
        private readonly FinancialPeriodService $financialPeriodService,
        private readonly JournalEntryLimitService $journalEntryLimitService,
    ) {}

    /**
     * Create a posted double-entry journal entry (automated flows).
     *
     * @param  array  $data  ['date' => 'Y-m-d', 'reference' => '...', 'description' => '...', 'source_type' => '...', 'source_id' => '...', 'tenant_id' => int]
     * @param  array  $lines  [['account_id' => 1, 'description' => '...', 'debit' => 100, 'credit' => 0], ...]
     */
    public function createJournalEntry(array $data, array $lines): JournalEntry
    {
        $data['status'] = 'posted';

        return $this->persistJournalEntry($data, $lines);
    }

    /**
     * Manual entry saved as draft (requires approve + post).
     */
    public function createDraftJournalEntry(array $data, array $lines): JournalEntry
    {
        $data['status'] = 'draft';

        return $this->persistJournalEntry($data, $lines);
    }

    public function approveJournalEntry(JournalEntry $entry): JournalEntry
    {
        if ($entry->status !== 'draft') {
            throw new Exception('Only draft journal entries can be approved.');
        }

        $entry->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $entry->fresh();
    }

    public function postJournalEntry(JournalEntry $entry): JournalEntry
    {
        if (! in_array($entry->status, ['draft', 'approved'], true)) {
            throw new Exception('Only draft or approved journal entries can be posted.');
        }

        if ($entry->status === 'draft' && ! $entry->approved_at) {
            throw new Exception('Journal entry must be approved before posting.');
        }

        $this->financialPeriodService->assertDateWritable(
            $entry->tenant_id,
            $entry->date->toDateString()
        );

        $entry->update(['status' => 'posted']);

        return $entry->fresh();
    }

    /**
     * Opening balance entry (posted immediately).
     */
    public function createOpeningBalanceEntry(array $data, array $lines): JournalEntry
    {
        $data['status'] = 'posted';
        $data['reference'] = $data['reference'] ?? 'OPENING-'.now()->format('Ymd');
        $data['description'] = $data['description'] ?? 'Opening balance entry';

        return $this->persistJournalEntry($data, $lines);
    }

    private function persistJournalEntry(array $data, array $lines): JournalEntry
    {
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        if (abs($totalDebit - $totalCredit) > 0.0001) {
            throw new Exception("Journal Entry not balanced. Total Debit: {$totalDebit}, Total Credit: {$totalCredit}");
        }

        if ($totalDebit <= 0) {
            throw new Exception('Journal entry must have a non-zero value.');
        }

        $tenantId = $data['tenant_id'] ?? request()->user()?->tenant_id ?? null;
        $entryDate = $data['date'] ?? now()->toDateString();

        if (($data['status'] ?? 'posted') === 'posted') {
            $this->financialPeriodService->assertDateWritable($tenantId, $entryDate);
            $tenant = $tenantId ? Tenant::find($tenantId) : null;
            if ($tenant) {
                $this->journalEntryLimitService->assertCanCreate($tenant);
            }
        }

        return DB::transaction(function () use ($data, $lines, $tenantId, $entryDate) {
            $journalEntry = JournalEntry::create([
                'tenant_id' => $tenantId,
                'reference' => $data['reference'] ?? $this->generateReference(),
                'date' => $entryDate,
                'description' => $data['description'],
                'status' => $data['status'] ?? 'posted',
                'created_by' => Auth::id(),
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
            ]);

            foreach ($lines as $line) {
                $account = Account::findOrFail($line['account_id']);

                if (! $account->is_active) {
                    throw new Exception("Account [{$account->code}] is inactive.");
                }

                $journalEntry->lines()->create([
                    'account_id' => $account->id,
                    'description' => $line['description'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                ]);
            }

            return $journalEntry;
        });
    }

    private function generateReference(): string
    {
        $prefix = 'JE-';
        $latest = JournalEntry::latest('id')->first();
        if (! $latest) {
            return $prefix.'00001';
        }

        $number = intval(str_replace($prefix, '', $latest->reference)) + 1;

        return $prefix.str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }
}
