<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use Exception;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Create a double-entry journal entry with total transactional safety.
     *
     * @param  array  $data  ['date' => 'Y-m-d', 'reference' => '...', 'description' => '...', 'source_type' => '...', 'source_id' => '...']
     * @param  array  $lines  [['account_id' => 1, 'description' => '...', 'debit' => 100, 'credit' => 0], ...]
     * @return JournalEntry
     *
     * @throws Exception
     */
    public function createJournalEntry(array $data, array $lines)
    {
        // 1. Validate entries
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        // Enforce the Golden Rule of Accounting
        if (abs($totalDebit - $totalCredit) > 0.0001) {
            throw new Exception("Journal Entry not balanced. Total Debit: {$totalDebit}, Total Credit: {$totalCredit}");
        }

        if ($totalDebit <= 0) {
            throw new Exception('Journal entry must have a non-zero value.');
        }

        DB::beginTransaction();

        try {
            // 2. Create the Header
            $journalEntry = JournalEntry::create([
                'tenant_id' => request()->user()?->tenant_id ?? $data['tenant_id'] ?? null,
                'reference' => $data['reference'] ?? $this->generateReference(),
                'date' => $data['date'] ?? now()->toDateString(),
                'description' => $data['description'],
                'status' => 'posted', // Auto-post for automated entries
                'created_by' => auth()->id(),
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
            ]);

            // 3. Create the Lines
            foreach ($lines as $line) {
                // Ensure account exists and is valid
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

            DB::commit();

            return $journalEntry;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generates a unique reference for journal entries.
     */
    private function generateReference()
    {
        $prefix = 'JE-';
        $latest = JournalEntry::latest('id')->first();
        if (! $latest) {
            return $prefix.'00001';
        }

        $number = intval(str_replace($prefix, '', $latest->reference)) + 1;

        return $prefix.str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}
