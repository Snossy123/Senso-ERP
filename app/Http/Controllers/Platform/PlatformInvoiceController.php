<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformInvoice;
use App\Services\Platform\PlatformInvoiceService;
use Illuminate\Http\Request;

class PlatformInvoiceController extends Controller
{
    public function __construct(
        protected PlatformInvoiceService $invoiceService
    ) {}

    public function index(Request $request)
    {
        $query = PlatformInvoice::with(['tenant', 'plan'])->orderByDesc('issued_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->paginate(20)->withQueryString();

        return view('platform.invoices.index', compact('invoices'));
    }

    public function show(PlatformInvoice $invoice)
    {
        $invoice->load(['tenant', 'plan']);

        return view('platform.invoices.show', compact('invoice'));
    }

    public function markPaid(PlatformInvoice $invoice)
    {
        $this->invoiceService->markPaid($invoice);

        return back()->with('success', __('platform.invoices.marked_paid'));
    }
}
