<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Trial Balance
     */
    public function trialBalance(Request $request)
    {
        $tenantId = $request->user()->tenant_id ?? null;

        $lines = JournalEntryLine::selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereHas('journalEntry', function ($q) use ($tenantId) {
                if ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                }
                $q->where('status', 'posted');
            })
            ->groupBy('account_id')
            ->with('account')
            ->get();

        $accounts = $lines->map(function ($line) {
            $balance = 0;
            $type = $line->account->type;

            if (in_array($type, ['asset', 'expense'])) {
                $balance = $line->total_debit - $line->total_credit;
            } else {
                $balance = $line->total_credit - $line->total_debit;
            }

            return [
                'account_code' => $line->account->code,
                'account_name' => $line->account->name,
                'type' => $type,
                'debit' => $line->total_debit,
                'credit' => $line->total_credit,
                'balance' => $balance,
            ];
        });

        $totalDebit = $accounts->sum('debit');
        $totalCredit = $accounts->sum('credit');

        return response()->json([
            'status' => 'success',
            'data' => [
                'accounts' => $accounts,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'is_balanced' => abs($totalDebit - $totalCredit) < 0.0001,
            ],
        ]);
    }

    /**
     * Income Statement (P&L)
     */
    public function incomeStatement(Request $request)
    {
        $tenantId = $request->user()->tenant_id ?? null;
        $startDate = $request->input('start_date', Carbon::now()->startOfYear());
        $endDate = $request->input('end_date', Carbon::now()->endOfYear());

        $revenues = Account::where('type', 'revenue')
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->get()->map(function ($account) {
                return ['name' => $account->name, 'balance' => $account->balance];
            });

        $expenses = Account::where('type', 'expense')
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->get()->map(function ($account) {
                return ['name' => $account->name, 'balance' => $account->balance];
            });

        $totalRevenue = $revenues->sum('balance');
        $totalExpense = $expenses->sum('balance');
        $netIncome = $totalRevenue - $totalExpense;

        return response()->json([
            'status' => 'success',
            'data' => [
                'revenues' => $revenues,
                'expenses' => $expenses,
                'total_revenue' => $totalRevenue,
                'total_expense' => $totalExpense,
                'net_income' => $netIncome,
            ],
        ]);
    }

    /**
     * Balance Sheet
     */
    public function balanceSheet(Request $request)
    {
        $tenantId = $request->user()->tenant_id ?? null;

        $assets = Account::where('type', 'asset')
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->get();
        $liabilities = Account::where('type', 'liability')
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->get();
        $equities = Account::where('type', 'equity')
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->get();

        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equities->sum('balance');

        return response()->json([
            'status' => 'success',
            'data' => [
                'assets' => $assets->map(fn ($a) => ['name' => $a->name, 'balance' => $a->balance]),
                'liabilities' => $liabilities->map(fn ($a) => ['name' => $a->name, 'balance' => $a->balance]),
                'equities' => $equities->map(fn ($a) => ['name' => $a->name, 'balance' => $a->balance]),
                'total_assets' => $totalAssets,
                'total_liabilities' => $totalLiabilities,
                'total_equity' => $totalEquity,
                'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.0001,
            ],
        ]);
    }

    /**
     * General Ledger
     */
    public function generalLedger(Request $request)
    {
        $account_id = $request->input('account_id');
        $tenantId = $request->user()->tenant_id ?? null;

        $query = JournalEntryLine::with('journalEntry')
            ->whereHas('journalEntry', function ($q) use ($tenantId) {
                if ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                }
                $q->where('status', 'posted');
            });

        if ($account_id) {
            $query->where('account_id', $account_id);
        }

        $entries = $query->orderBy('created_at', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $entries,
        ]);
    }
}
