<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Exception;

class JournalEntryController extends Controller
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * List Journal Entries.
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id ?? null;

        $entries = JournalEntry::when($tenantId, function ($query, $tenantId) {
                return $query->where('tenant_id', $tenantId);
            })
            ->with('lines.account')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $entries
        ]);
    }

    /**
     * Create a new Journal Entry manually.
     */
    public function store(Request $request)
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
            // Validation step: total debit must equal total credit
            $entry = $this->accountingService->createJournalEntry([
                'tenant_id' => $request->user()->tenant_id ?? null,
                'date' => $validated['date'],
                'description' => $validated['description'],
                'reference' => $validated['reference'] ?? null,
            ], $validated['lines']);

            return response()->json([
                'status' => 'success',
                'message' => 'Journal entry created and posted successfully.',
                'data' => $entry->load('lines.account')
            ], 201);
            
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * View details of a journal entry.
     */
    public function show($id)
    {
        $entry = JournalEntry::with('lines.account')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $entry
        ]);
    }
}
