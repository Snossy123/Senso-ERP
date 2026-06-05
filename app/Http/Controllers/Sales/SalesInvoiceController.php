<?php

namespace App\Http\Controllers\Sales;

use App\Application\Sales\InstallmentScheduleService;
use App\Application\Sales\SalesInvoiceService;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InvoiceInstallment;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SalesInvoiceController extends Controller
{
    public function __construct(
        protected SalesInvoiceService $invoiceService,
        protected InstallmentScheduleService $installmentSchedule,
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->authorizePermission('sales_invoices.view');

        if ($request->boolean('overdue_installments')) {
            $this->installmentSchedule->markOverdueInstallments((int) auth()->user()->tenant_id);
        }

        $query = SalesInvoice::with('customer')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('payment_term')) {
            $query->where('payment_term', $request->payment_term);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('invoice_number', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%"));
            });
        }

        $invoices = $query->paginate(25)->withQueryString();
        $customers = Customer::orderBy('name')->get(['id', 'name']);

        $overdueInstallments = null;
        if ($request->boolean('overdue_installments')) {
            $overdueInstallments = InvoiceInstallment::with('invoice.customer')
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->whereDate('due_date', '<', now()->toDateString())
                ->orderBy('due_date')
                ->paginate(25, ['*'], 'installments_page')
                ->withQueryString();
        }

        return view('sales.invoices.index', compact('invoices', 'customers', 'overdueInstallments'));
    }

    public function create()
    {
        $this->authorizePermission('sales_invoices.create');

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'selling_price', 'stock_quantity']);
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('sales.invoices.create', compact('customers', 'products', 'warehouses'));
    }

    public function store(Request $request)
    {
        $this->authorizePermission('sales_invoices.create');

        $data = $this->validateInvoice($request);
        $installmentPlan = $this->resolveInstallmentPlanForConfirm($request, $data);

        $invoice = $this->invoiceService->createDraft(
            $data['header'],
            $data['lines'],
            (int) auth()->id()
        );

        if ($request->boolean('confirm_now')) {
            return $this->redirectAfterConfirm($request, $invoice, $installmentPlan);
        }

        return redirect()
            ->route('sales.invoices.index')
            ->with('success', __('sales_invoices.messages.draft_created'));
    }

    public function show(SalesInvoice $invoice)
    {
        $this->authorizePermission('sales_invoices.view');

        $invoice->load([
            'customer', 'lines.product', 'user', 'warehouse',
            'installmentPlan', 'installments',
            'paymentAllocations.payment',
        ]);

        return view('sales.invoices.show', compact('invoice'));
    }

    public function edit(SalesInvoice $invoice)
    {
        $this->authorizePermission('sales_invoices.edit');

        if (! $invoice->isDraft()) {
            return redirect()
                ->route('sales.invoices.show', $invoice)
                ->with('error', __('sales_invoices.messages.not_editable'));
        }

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'selling_price', 'stock_quantity']);
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        $invoice->load('lines');

        return view('sales.invoices.edit', compact('invoice', 'customers', 'products', 'warehouses'));
    }

    public function update(Request $request, SalesInvoice $invoice)
    {
        $this->authorizePermission('sales_invoices.edit');

        $data = $this->validateInvoice($request);
        $installmentPlan = $this->resolveInstallmentPlanForConfirm($request, $data);
        $invoice = $this->invoiceService->updateDraft($invoice, $data['header'], $data['lines']);

        if ($request->boolean('confirm_now')) {
            return $this->redirectAfterConfirm($request, $invoice, $installmentPlan);
        }

        return redirect()
            ->route('sales.invoices.index')
            ->with('success', __('sales_invoices.messages.updated'));
    }

    public function confirm(Request $request, SalesInvoice $invoice)
    {
        $this->authorizePermission('sales_invoices.confirm');

        $installmentPlan = null;
        if ($invoice->payment_term === 'installment' || $request->input('payment_term') === 'installment') {
            $installmentPlan = $request->validate([
                'down_payment' => 'nullable|numeric|min:0',
                'installment_count' => 'required|integer|min:1|max:120',
                'interval_days' => 'required|integer|min:1|max:365',
                'first_due_date' => 'required|date',
            ]);
        }

        try {
            $this->invoiceService->confirm($invoice, $installmentPlan, (int) auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.invoices.index')
            ->with('success', __('sales_invoices.messages.confirmed'));
    }

    public function cancel(SalesInvoice $invoice)
    {
        $this->authorizePermission('sales_invoices.cancel');

        try {
            if ($invoice->isDraft()) {
                $this->invoiceService->cancelDraft($invoice);
            } else {
                $this->invoiceService->cancelConfirmed($invoice);
            }
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.invoices.index')
            ->with('success', __('sales_invoices.messages.cancelled'));
    }

    protected function redirectAfterConfirm(Request $request, SalesInvoice $invoice, ?array $installmentPlan = null)
    {
        $this->authorizePermission('sales_invoices.confirm');

        try {
            $this->invoiceService->confirm($invoice, $installmentPlan, (int) auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', __('sales_invoices.messages.confirm_failed'));
        }

        return redirect()
            ->route('sales.invoices.index')
            ->with('success', __('sales_invoices.messages.confirmed'));
    }

    /**
     * @param  array{header: array<string, mixed>, lines: array<int, array<string, mixed>>}  $data
     * @return array<string, mixed>|null
     */
    protected function resolveInstallmentPlanForConfirm(Request $request, array $data): ?array
    {
        if (! $request->boolean('confirm_now')) {
            return null;
        }

        $this->authorizePermission('sales_invoices.confirm');

        if (($data['header']['payment_term'] ?? '') !== 'installment') {
            return null;
        }

        return $this->validateInstallmentPlan(
            $request,
            $data['lines'],
            (float) ($data['header']['discount_amount'] ?? 0)
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array{down_payment: float, installment_count: int, interval_days: int, first_due_date: string}
     */
    protected function validateInstallmentPlan(Request $request, array $lines, float $orderDiscount = 0): array
    {
        $validated = $request->validate([
            'down_payment' => 'nullable|numeric|min:0',
            'installment_count' => 'required|integer|min:1|max:120',
            'interval_days' => 'required|integer|min:1|max:365',
            'first_due_date' => 'required|date',
        ]);

        $totals = $this->invoiceService->calculateTotals($lines, $orderDiscount);
        $downPayment = round((float) ($validated['down_payment'] ?? 0), 2);

        if ($downPayment >= $totals['total']) {
            throw ValidationException::withMessages([
                'down_payment' => [
                    __('sales_invoices.validation.down_payment_too_high', [
                        'total' => number_format($totals['total'], 2),
                    ]),
                ],
            ]);
        }

        return [
            'down_payment' => $downPayment,
            'installment_count' => (int) $validated['installment_count'],
            'interval_days' => (int) $validated['interval_days'],
            'first_due_date' => $validated['first_due_date'],
        ];
    }

    /**
     * @return array{header: array<string, mixed>, lines: array<int, array<string, mixed>>}
     */
    protected function validateInvoice(Request $request): array
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'payment_term' => 'required|in:cash,credit,installment',
            'invoice_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'nullable|exists:products,id',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.quantity' => 'required|integer|min:1',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount' => 'nullable|numeric|min:0',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        return [
            'header' => [
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'payment_term' => $validated['payment_term'],
                'invoice_date' => $validated['invoice_date'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'notes' => $validated['notes'] ?? null,
            ],
            'lines' => $validated['lines'],
        ];
    }

    protected function authorizePermission(string $slug): void
    {
        if (! auth()->user()->hasPermission($slug) && ! auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
