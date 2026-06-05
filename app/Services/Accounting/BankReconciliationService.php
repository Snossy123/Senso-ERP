<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\BankStatementLine;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class BankReconciliationService
{
    /**
     * GL accounts commonly used for bank reconciliation.
     *
     * @return Collection<int, Account>
     */
    public function bankAccounts(int $tenantId): Collection
    {
        $ids = collect([
            AccountSetting::getAccountId('bank_payment', $tenantId),
            AccountSetting::getAccountId('pos_card', $tenantId),
            AccountSetting::getAccountId('pos_bank', $tenantId),
        ])->filter()->unique()->values();

        return Account::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->orderBy('code')
            ->get();
    }

    public function summary(int $tenantId, int $accountId, ?string $endDate = null): array
    {
        $endDate = $endDate ?? now()->toDateString();

        $glBalance = app(AccountingReportingService::class)
            ->accountBalanceThrough($accountId, $tenantId, $endDate);

        $unreconciledStatement = BankStatementLine::query()
            ->where('tenant_id', $tenantId)
            ->where('account_id', $accountId)
            ->where('is_reconciled', false)
            ->sum('amount');

        $unreconciledGl = $this->unreconciledGlLines($tenantId, $accountId, $endDate)->count();

        return [
            'gl_balance' => $glBalance,
            'unreconciled_statement_lines' => (int) BankStatementLine::query()
                ->where('tenant_id', $tenantId)
                ->where('account_id', $accountId)
                ->where('is_reconciled', false)
                ->count(),
            'unreconciled_statement_amount' => (float) $unreconciledStatement,
            'unreconciled_gl_lines' => $unreconciledGl,
        ];
    }

    public function unreconciledGlLines(int $tenantId, int $accountId, ?string $endDate = null)
    {
        $reconciledLineIds = BankStatementLine::query()
            ->where('tenant_id', $tenantId)
            ->where('account_id', $accountId)
            ->whereNotNull('journal_entry_line_id')
            ->pluck('journal_entry_line_id');

        $query = JournalEntryLine::query()
            ->with('journalEntry')
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) use ($tenantId, $endDate) {
                $q->where('tenant_id', $tenantId)->where('status', 'posted');
                if ($endDate) {
                    $q->where('date', '<=', $endDate);
                }
            })
            ->whereNotIn('id', $reconciledLineIds);

        return $query->orderBy('created_at')->get();
    }

    public function unreconciledStatementLines(int $tenantId, int $accountId)
    {
        return BankStatementLine::query()
            ->where('tenant_id', $tenantId)
            ->where('account_id', $accountId)
            ->where('is_reconciled', false)
            ->orderBy('transaction_date')
            ->get();
    }

    public function importLine(int $tenantId, array $data): BankStatementLine
    {
        return BankStatementLine::create([
            'tenant_id' => $tenantId,
            'account_id' => $data['account_id'],
            'transaction_date' => $data['transaction_date'],
            'reference' => $data['reference'] ?? null,
            'description' => $data['description'] ?? null,
            'amount' => abs((float) $data['amount']),
            'type' => $data['type'],
        ]);
    }

    public function match(int $statementLineId, int $journalEntryLineId, int $userId): BankStatementLine
    {
        $statement = BankStatementLine::findOrFail($statementLineId);
        $glLine = JournalEntryLine::with('journalEntry')->findOrFail($journalEntryLineId);

        if ($statement->account_id !== $glLine->account_id) {
            throw new \Exception('Statement line and journal line must use the same GL account.');
        }

        $statement->update([
            'is_reconciled' => true,
            'journal_entry_line_id' => $glLine->id,
            'reconciled_at' => now(),
            'reconciled_by' => $userId,
        ]);

        return $statement->fresh();
    }
}
