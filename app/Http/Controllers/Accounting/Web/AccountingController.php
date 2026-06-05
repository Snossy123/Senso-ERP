<?php

namespace App\Http\Controllers\Accounting\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\Tenant;
use App\Services\Accounting\AccountingReconciliationService;
use App\Services\Accounting\AccountingReportingService;
use App\Services\Accounting\CommerceRevenueRecognition;
use App\Services\Accounting\FinancialPeriodService;
use App\Services\Accounting\JournalEntryLimitService;
use App\Services\Accounting\SubsidiaryLedgerService;
use App\Services\Accounting\TenantAccountingSettings;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountingController extends Controller
{
    public function __construct(
        private readonly AccountingReportingService $reporting,
        private readonly AccountingReconciliationService $reconciliation,
        private readonly FinancialPeriodService $financialPeriodService,
        private readonly SubsidiaryLedgerService $subsidiaryLedgerService,
        private readonly JournalEntryLimitService $journalEntryLimitService,
    ) {}

    public function dashboard()
    {
        $tenantId = auth()->user()->tenant_id;

        $totalAssets = Account::where('type', 'asset')->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))->get()->sum('balance');
        $totalLiabilities = Account::where('type', 'liability')->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))->get()->sum('balance');
        $totalRevenue = Account::where('type', 'revenue')->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))->get()->sum('balance');
        $totalExpense = Account::where('type', 'expense')->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))->get()->sum('balance');

        $recentEntries = JournalEntry::where('tenant_id', $tenantId)
            ->latest('date')
            ->take(5)
            ->get();

        $missingCount = $this->reconciliation->missingJournalEntries($tenantId)->count();
        $tenant = Tenant::find($tenantId);
        $journalUsage = $tenant ? $this->journalEntryLimitService->usage($tenant) : null;
        $goLiveDate = TenantAccountingSettings::goLiveDate($tenant);

        return view('accounting.index', compact(
            'totalAssets', 'totalLiabilities', 'totalRevenue', 'totalExpense', 'recentEntries', 'missingCount', 'journalUsage', 'goLiveDate'
        ));
    }

    public function accounts(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $accounts = Account::where('tenant_id', $tenantId)
            ->with('children')
            ->whereNull('parent_id')
            ->get();

        return view('accounting.accounts.index', compact('accounts'));
    }

    public function journalEntries(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $entries = JournalEntry::where('tenant_id', $tenantId)
            ->with('lines.account', 'creator', 'approver')
            ->latest('date')
            ->paginate(15);

        return view('accounting.journal-entries.index', [
            'entries' => $entries,
            'canApprove' => $this->canApproveJournal(),
            'canPost' => $this->canPostJournal(),
        ]);
    }

    public function reports(Request $request)
    {
        return view('accounting.reports.index');
    }

    public function reportTrialBalance(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $data = $this->reporting->trialBalance($tenantId, $endDate);

        return view('accounting.reports.trial-balance', compact('data', 'endDate'));
    }

    public function reportIncomeStatement(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfYear()->toDateString());
        $data = $this->reporting->incomeStatement($tenantId, $startDate, $endDate);

        return view('accounting.reports.income-statement', compact('data', 'startDate', 'endDate'));
    }

    public function reportBalanceSheet(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $asOfDate = $request->input('as_of_date', Carbon::today()->toDateString());
        $data = $this->reporting->balanceSheet($tenantId, $asOfDate);

        return view('accounting.reports.balance-sheet', compact('data', 'asOfDate'));
    }

    public function reportGeneralLedger(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $accountId = $request->input('account_id');
        $accounts = Account::where('tenant_id', $tenantId)->orderBy('code')->get();
        $entries = $this->reporting->generalLedger(
            $tenantId,
            $accountId ? (int) $accountId : null,
            $startDate,
            $endDate
        );

        return view('accounting.reports.general-ledger', compact('entries', 'accounts', 'startDate', 'endDate', 'accountId'));
    }

    public function reconciliation()
    {
        $tenantId = auth()->user()->tenant_id;
        $missing = $this->reconciliation->missingJournalEntries($tenantId);

        return view('accounting.reconciliation.index', compact('missing'));
    }

    public function subsidiaryLedgers()
    {
        $tenantId = (int) auth()->user()->tenant_id;
        $tenant = Tenant::find($tenantId);
        $ar = $this->subsidiaryLedgerService->accountsReceivableSummary($tenantId);
        $ap = $this->subsidiaryLedgerService->accountsPayableSummary($tenantId);
        $baseCurrency = TenantAccountingSettings::baseCurrency($tenant);

        return view('accounting.subsidiary-ledgers.index', compact('ar', 'ap', 'baseCurrency'));
    }

    public function auditTrail(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $query = JournalEntry::where('tenant_id', $tenantId)
            ->with(['lines.account', 'creator', 'approver']);

        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%'.$request->reference.'%');
        }
        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }

        $entries = $query->latest('date')->paginate(20)->withQueryString();

        return view('accounting.audit-trail.index', compact('entries'));
    }

    public function periods()
    {
        $tenantId = auth()->user()->tenant_id;
        $periods = FinancialPeriod::where('tenant_id', $tenantId)
            ->orderByDesc('start_date')
            ->get();

        return view('accounting.periods.index', compact('periods'));
    }

    public function storePeriod(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $this->financialPeriodService->openPeriod(
            $tenantId,
            $validated['name'],
            $validated['start_date'],
            $validated['end_date']
        );

        return redirect()->route('accounting.periods')->with('success', 'Financial period created.');
    }

    public function closePeriod(FinancialPeriod $period)
    {
        if ($period->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->hasPermission('accounting.periods.close')) {
            abort(403, 'You do not have permission to close financial periods.');
        }

        $this->financialPeriodService->closePeriod($period);

        return redirect()->route('accounting.periods')->with('success', 'Period closed.');
    }

    public function openingBalance()
    {
        $tenantId = auth()->user()->tenant_id;
        $accounts = Account::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('code')->get();

        return view('accounting.opening-balance.create', compact('accounts'));
    }

    public function storeOpeningBalance(Request $request, AccountingService $accountingService)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
        ]);

        try {
            $accountingService->createOpeningBalanceEntry([
                'tenant_id' => auth()->user()->tenant_id,
                'date' => $validated['date'],
                'description' => $validated['description'] ?? 'Opening balances',
            ], $validated['lines']);

            return redirect()->route('accounting.journal-entries')->with('success', 'Opening balance entry posted.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function createJournalEntry()
    {
        $tenantId = auth()->user()->tenant_id;
        $accounts = Account::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('code')->get();

        return view('accounting.journal-entries.create', compact('accounts'));
    }

    public function storeJournalEntry(Request $request, AccountingService $accountingService)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'reference' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
        ]);

        try {
            $validated['tenant_id'] = auth()->user()->tenant_id ?? null;
            $accountingService->createDraftJournalEntry($validated, $validated['lines']);

            return redirect()->route('accounting.journal-entries')->with('success', 'Journal entry saved as draft. Approve and post to update the GL.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approveJournalEntry(JournalEntry $entry, AccountingService $accountingService)
    {
        $this->authorizeEntry($entry);

        if (! $this->canApproveJournal()) {
            return back()->with('error', 'You do not have permission to approve journal entries.');
        }

        try {
            $accountingService->approveJournalEntry($entry);

            return back()->with('success', 'Journal entry approved.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function postJournalEntry(JournalEntry $entry, AccountingService $accountingService)
    {
        $this->authorizeEntry($entry);

        if (! $this->canPostJournal()) {
            return back()->with('error', 'You do not have permission to post journal entries.');
        }

        try {
            $accountingService->postJournalEntry($entry);

            return back()->with('success', 'Journal entry posted to the general ledger.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:accounts,id',
            'code' => 'required|string',
            'name' => 'required|string',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'description' => 'nullable|string',
        ]);

        $validated['tenant_id'] = auth()->user()->tenant_id ?? null;
        Account::create($validated);

        return redirect()->route('accounting.accounts')->with('success', 'Account created successfully.');
    }

    public function settings()
    {
        $tenantId = auth()->user()->tenant_id;
        $tenant = Tenant::find($tenantId);
        $accounts = Account::where('tenant_id', $tenantId)->orderBy('code')->get();
        $settings = \App\Models\AccountSetting::where('tenant_id', $tenantId)->get()->pluck('account_id', 'key');
        $revenueRecognition = CommerceRevenueRecognition::policy($tenant);
        $goLiveDate = TenantAccountingSettings::goLiveDate($tenant);
        $cardFeePercent = TenantAccountingSettings::cardFeePercent($tenant);
        $baseCurrency = TenantAccountingSettings::baseCurrency($tenant);

        $mappingKeys = [
            'pos_cash' => 'POS Cash Drawer',
            'pos_card' => 'POS Card Clearing (Bank)',
            'pos_bank' => 'POS Bank Transfer Account',
            'pos_variance' => 'POS Cash Variance Account',
            'bank_payment' => 'Supplier Bank Payment Account',
            'sales_revenue' => 'Sales Revenue Account',
            'sales_discount' => 'Discounts Allowed Account',
            'tax_payable' => 'Tax (VAT/GST) Account',
            'cogs_account' => 'Cost of Goods Sold Account',
            'inventory_account' => 'Inventory Asset Account',
            'supplier_payable' => 'Accounts Payable (Suppliers)',
            'customer_receivable' => 'Accounts Receivable (Customers)',
            'cash_customer' => 'General Cash Account',
            'refund_account' => 'Refunds/Returns Account',
            'payment_fees' => 'Payment Processing Fees Account',
        ];

        return view('accounting.settings', compact(
            'accounts',
            'settings',
            'mappingKeys',
            'revenueRecognition',
            'goLiveDate',
            'cardFeePercent',
            'baseCurrency',
        ));
    }

    public function updateSettings(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $validated = $request->validate([
            'mappings' => 'required|array',
            'mappings.*' => 'nullable|exists:accounts,id',
            'revenue_recognition' => 'nullable|in:on_place,on_paid',
            'go_live_date' => 'nullable|date',
            'card_fee_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        foreach ($validated['mappings'] as $key => $accountId) {
            if ($accountId) {
                \App\Models\AccountSetting::updateOrCreate(
                    ['tenant_id' => $tenantId, 'key' => $key],
                    ['account_id' => $accountId]
                );
            }
        }

        $tenant = Tenant::findOrFail($tenantId);

        if (! empty($validated['revenue_recognition'])) {
            $settings = $tenant->settings ?? [];
            $settings['commerce']['revenue_recognition'] = $validated['revenue_recognition'];
            $tenant->update(['settings' => $settings]);
            $tenant->refresh();
        }

        if (array_key_exists('go_live_date', $validated)) {
            TenantAccountingSettings::setGoLiveDate($tenant, $validated['go_live_date']);
        }

        if (array_key_exists('card_fee_percent', $validated) && $validated['card_fee_percent'] !== null) {
            TenantAccountingSettings::setCardFeePercent($tenant, (float) $validated['card_fee_percent']);
        }

        return redirect()->route('accounting.settings')->with('success', 'Accounting settings updated successfully.');
    }

    private function authorizeEntry(JournalEntry $entry): void
    {
        if ($entry->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }

    private function canApproveJournal(): bool
    {
        $user = Auth::user();

        return $user->isAdmin() || $user->hasPermission('accounting.approve');
    }

    private function canPostJournal(): bool
    {
        $user = Auth::user();

        return $user->isAdmin() || $user->hasPermission('accounting.post');
    }
}
