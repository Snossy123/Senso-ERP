<?php

namespace App\Http\Controllers\Accounting\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function dashboard()
    {
        $tenantId = auth()->user()->tenant_id;
        
        $totalAssets = Account::where('type', 'asset')->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->get()->sum('balance');
        $totalLiabilities = Account::where('type', 'liability')->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->get()->sum('balance');
        $totalRevenue = Account::where('type', 'revenue')->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->get()->sum('balance');
        $totalExpense = Account::where('type', 'expense')->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->get()->sum('balance');
        
        $recentEntries = JournalEntry::where('tenant_id', $tenantId)
                            ->latest('date')
                            ->take(5)
                            ->get();

        return view('accounting.index', compact(
            'totalAssets', 'totalLiabilities', 'totalRevenue', 'totalExpense', 'recentEntries'
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
                    ->with('lines.account', 'creator')
                    ->latest('date')
                    ->paginate(15);
                    
        return view('accounting.journal-entries.index', compact('entries'));
    }

    public function reports(Request $request)
    {
        return view('accounting.reports.index');
    }

    public function createJournalEntry()
    {
        return view('accounting.journal-entries.create');
    }

    public function storeJournalEntry(Request $request, \App\Services\AccountingService $accountingService)
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
            $accountingService->createJournalEntry($validated, $validated['lines']);
            return redirect()->route('accounting.journal-entries')->with('success', 'Journal Entry posted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:accounts,id',
            'code'      => 'required|string',
            'name'      => 'required|string',
            'type'      => 'required|in:asset,liability,equity,revenue,expense',
            'description' => 'nullable|string',
        ]);

        $validated['tenant_id'] = auth()->user()->tenant_id ?? null;
        Account::create($validated);
        
        return redirect()->route('accounting.accounts')->with('success', 'Account created successfully.');
    }
}

