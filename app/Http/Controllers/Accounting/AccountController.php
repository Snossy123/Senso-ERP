<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Display a listing of the chart of accounts.
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id ?? null;

        $accounts = Account::when($tenantId, function ($query, $tenantId) {
                return $query->where('tenant_id', $tenantId);
            })
            ->with(['children', 'parent'])
            ->get();

        // Optional: Transform into a hierarchical tree format
        $tree = $accounts->whereNull('parent_id')->map(function ($account) use ($accounts) {
            return $this->buildTree($account, $accounts);
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'list' => $accounts,
                'tree' => $tree->values(),
            ]
        ]);
    }

    private function buildTree($account, $allAccounts)
    {
        $children = $allAccounts->where('parent_id', $account->id)->map(function ($child) use ($allAccounts) {
            return $this->buildTree($child, $allAccounts);
        });
        
        $accountArray = $account->toArray();
        $accountArray['children'] = $children->values();
        $accountArray['balance'] = $account->balance; // calculated attribute

        return $accountArray;
    }

    /**
     * Store a newly created account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:accounts,id',
            'code'      => 'required|string',
            'name'      => 'required|string',
            'type'      => 'required|in:asset,liability,equity,revenue,expense',
            'description' => 'nullable|string',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id ?? null;

        $account = Account::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Account created successfully',
            'data' => $account
        ], 201);
    }
}
