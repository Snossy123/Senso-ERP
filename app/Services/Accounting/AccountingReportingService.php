<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\JournalEntryLine;
use Carbon\Carbon;

class AccountingReportingService
{
    public function trialBalance(?int $tenantId, ?string $endDate = null): array
    {
        $lines = $this->baseLineQuery($tenantId, null, $endDate)
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('account_id')
            ->with('account')
            ->get();

        $accounts = $lines->map(function ($line) {
            $balance = $this->signedBalance($line->account->type, (float) $line->total_debit, (float) $line->total_credit);

            return [
                'account_code' => $line->account->code,
                'account_name' => $line->account->name,
                'type' => $line->account->type,
                'debit' => (float) $line->total_debit,
                'credit' => (float) $line->total_credit,
                'balance' => $balance,
            ];
        });

        $totalDebit = $accounts->sum('debit');
        $totalCredit = $accounts->sum('credit');

        return [
            'accounts' => $accounts,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.0001,
        ];
    }

    public function incomeStatement(?int $tenantId, string $startDate, string $endDate): array
    {
        $revenueIds = Account::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('type', 'revenue')
            ->pluck('id');

        $expenseIds = Account::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('type', 'expense')
            ->pluck('id');

        $revenues = $this->accountsWithPeriodBalance($revenueIds, $tenantId, $startDate, $endDate);
        $expenses = $this->accountsWithPeriodBalance($expenseIds, $tenantId, $startDate, $endDate);

        $totalRevenue = collect($revenues)->sum('balance');
        $totalExpense = collect($expenses)->sum('balance');

        return [
            'revenues' => $revenues,
            'expenses' => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_income' => $totalRevenue - $totalExpense,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    public function balanceSheet(?int $tenantId, ?string $asOfDate = null): array
    {
        $asOf = $asOfDate ?? Carbon::today()->toDateString();

        $mapAccounts = function (string $type) use ($tenantId, $asOf) {
            return Account::query()
                ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->where('type', $type)
                ->get()
                ->map(function (Account $account) use ($tenantId, $asOf) {
                    return [
                        'name' => $account->name,
                        'code' => $account->code,
                        'balance' => $this->accountBalanceThrough($account->id, $tenantId, $asOf),
                    ];
                });
        };

        $assets = $mapAccounts('asset');
        $liabilities = $mapAccounts('liability');
        $equities = $mapAccounts('equity');

        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equities->sum('balance');

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equities' => $equities,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.0001,
            'as_of_date' => $asOf,
        ];
    }

    public function generalLedger(?int $tenantId, ?int $accountId = null, ?string $startDate = null, ?string $endDate = null)
    {
        $query = $this->baseLineQuery($tenantId, $startDate, $endDate)
            ->with(['journalEntry', 'account']);

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

    private function accountsWithPeriodBalance($accountIds, ?int $tenantId, string $startDate, string $endDate): array
    {
        $result = [];
        foreach ($accountIds as $accountId) {
            $account = Account::find($accountId);
            if (! $account) {
                continue;
            }
            $balance = $this->accountBalanceBetween($accountId, $tenantId, $startDate, $endDate);
            if (abs($balance) < 0.0001) {
                continue;
            }
            $result[] = ['name' => $account->name, 'code' => $account->code, 'balance' => $balance];
        }

        return $result;
    }

    public function accountBalanceBetween(int $accountId, ?int $tenantId, string $startDate, string $endDate): float
    {
        $account = Account::findOrFail($accountId);
        $row = $this->baseLineQuery($tenantId, $startDate, $endDate)
            ->where('account_id', $accountId)
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        return $this->signedBalance(
            $account->type,
            (float) ($row->total_debit ?? 0),
            (float) ($row->total_credit ?? 0)
        );
    }

    public function accountBalanceThrough(int $accountId, ?int $tenantId, string $endDate): float
    {
        return $this->accountBalanceBetween($accountId, $tenantId, '1970-01-01', $endDate);
    }

    private function baseLineQuery(?int $tenantId, ?string $startDate, ?string $endDate)
    {
        return JournalEntryLine::query()
            ->whereHas('journalEntry', function ($q) use ($tenantId, $startDate, $endDate) {
                if ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                }
                $q->where('status', 'posted');
                if ($startDate) {
                    $q->where('date', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('date', '<=', $endDate);
                }
            });
    }

    private function signedBalance(string $type, float $debit, float $credit): float
    {
        if (in_array($type, ['asset', 'expense'], true)) {
            return round($debit - $credit, 4);
        }

        return round($credit - $debit, 4);
    }
}
