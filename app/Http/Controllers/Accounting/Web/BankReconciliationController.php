<?php

namespace App\Http\Controllers\Accounting\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Accounting\BankReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankReconciliationController extends Controller
{
    public function __construct(
        private readonly BankReconciliationService $bankReconciliationService,
    ) {}

    public function index(Request $request)
    {
        $tenantId = (int) auth()->user()->tenant_id;
        $accounts = $this->bankReconciliationService->bankAccounts($tenantId);
        $accountId = (int) $request->input('account_id', $accounts->first()?->id ?? 0);
        $endDate = $request->input('end_date', now()->toDateString());

        $summary = null;
        $statementLines = collect();
        $glLines = collect();

        if ($accountId > 0) {
            $summary = $this->bankReconciliationService->summary($tenantId, $accountId, $endDate);
            $statementLines = $this->bankReconciliationService->unreconciledStatementLines($tenantId, $accountId);
            $glLines = $this->bankReconciliationService->unreconciledGlLines($tenantId, $accountId, $endDate);
        }

        return view('accounting.bank-reconciliation.index', compact(
            'accounts',
            'accountId',
            'endDate',
            'summary',
            'statementLines',
            'glLines',
        ));
    }

    public function importLine(Request $request)
    {
        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->hasPermission('accounting.reconcile')) {
            abort(403, 'You do not have permission to reconcile bank accounts.');
        }

        $tenantId = (int) $user->tenant_id;
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:debit,credit',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $account = Account::findOrFail($validated['account_id']);
        if ((int) $account->tenant_id !== $tenantId) {
            abort(403);
        }

        try {
            $this->bankReconciliationService->importLine($tenantId, $validated);

            return redirect()
                ->route('accounting.bank-reconciliation', ['account_id' => $validated['account_id']])
                ->with('success', 'Bank statement line imported.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function match(Request $request)
    {
        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->hasPermission('accounting.reconcile')) {
            abort(403, 'You do not have permission to reconcile bank accounts.');
        }

        $validated = $request->validate([
            'statement_line_id' => 'required|exists:bank_statement_lines,id',
            'journal_entry_line_id' => 'required|exists:journal_entry_lines,id',
        ]);

        try {
            $this->bankReconciliationService->match(
                (int) $validated['statement_line_id'],
                (int) $validated['journal_entry_line_id'],
                (int) $user->id,
            );

            return back()->with('success', 'Statement line matched to GL entry.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
