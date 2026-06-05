<?php

namespace App\Http\Controllers\Sales;

use App\Application\Sales\InvoicePaymentService;
use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;

class InvoicePaymentController extends Controller
{
    public function __construct(
        protected InvoicePaymentService $paymentService,
    ) {
        $this->middleware('auth');
    }

    public function store(Request $request, SalesInvoice $invoice)
    {
        $this->authorizePermission('sales_invoices.pay');

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer',
            'paid_at' => 'nullable|date',
            'reference' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:500',
            'invoice_installment_id' => 'nullable|exists:invoice_installments,id',
        ]);

        try {
            $this->paymentService->recordForInvoice($invoice, $data, (int) auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('sales_invoices.messages.payment_recorded'));
    }

    protected function authorizePermission(string $slug): void
    {
        if (! auth()->user()->hasPermission($slug) && ! auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
