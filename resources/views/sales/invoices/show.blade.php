@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <h4 class="content-title mb-0">{{ $invoice->invoice_number }}</h4>
        <span class="text-muted tx-13">{{ $invoice->customer->name ?? '' }}</span>
    </div>
    <div class="d-flex">
        @if($invoice->isConfirmed())
        <a href="{{ route('exports.sales-invoice.pdf', $invoice) }}" class="btn btn-outline-secondary mr-2"><i class="fe fe-download"></i> PDF</a>
        @endif
        <a href="{{ route('sales.invoices.index') }}" class="btn btn-secondary">{{ __('sales_invoices.back') }}</a>
    </div>
</div>
@endsection
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <span class="badge badge-light">{{ __('sales_invoices.status_'.$invoice->status) }}</span>
                        <span class="badge badge-{{ $invoice->payment_status === 'paid' ? 'success' : 'warning' }}">{{ __('sales_invoices.payment_'.$invoice->payment_status) }}</span>
                        <span class="badge badge-info">{{ __('sales_invoices.term_'.$invoice->payment_term) }}</span>
                    </div>
                    <div class="text-right">
                        <div>{{ __('sales_invoices.total') }}: <strong>{{ number_format($invoice->total, 2) }}</strong></div>
                        <div>{{ __('sales_invoices.paid') }}: {{ number_format($invoice->paid_amount, 2) }}</div>
                        <div>{{ __('sales_invoices.balance') }}: <strong>{{ number_format($invoice->balance_due, 2) }}</strong></div>
                    </div>
                </div>
                <table class="table table-sm">
                    <thead><tr><th>{{ __('sales_invoices.product') }}</th><th>{{ __('sales_invoices.qty') }}</th><th>{{ __('sales_invoices.unit_price') }}</th><th>{{ __('sales_invoices.line_total') }}</th></tr></thead>
                    <tbody>
                    @foreach($invoice->lines as $line)
                    <tr>
                        <td>{{ $line->description ?? $line->product?->name }}</td>
                        <td>{{ $line->quantity }}</td>
                        <td>{{ number_format($line->unit_price, 2) }}</td>
                        <td>{{ number_format($line->line_total, 2) }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                @if($invoice->notes)<p class="text-muted mt-2"><strong>{{ __('sales_invoices.notes') }}:</strong> {{ $invoice->notes }}</p>@endif
            </div>
        </div>

        @if($invoice->installments->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header"><h5 class="mb-0">{{ __('sales_invoices.installments') }}</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>#</th><th>{{ __('sales_invoices.due_date') }}</th><th>{{ __('sales_invoices.amount') }}</th><th>{{ __('sales_invoices.paid') }}</th><th>{{ __('sales_invoices.status') }}</th><th></th></tr></thead>
                    <tbody>
                    @foreach($invoice->installments as $inst)
                    <tr>
                        <td>{{ $inst->sequence }}</td>
                        <td>{{ $inst->due_date->format('Y-m-d') }}</td>
                        <td>{{ number_format($inst->amount, 2) }}</td>
                        <td>{{ number_format($inst->paid_amount, 2) }}</td>
                        <td><span class="badge badge-{{ $inst->status === 'paid' ? 'success' : ($inst->status === 'overdue' ? 'danger' : 'secondary') }}">{{ $inst->status }}</span></td>
                        <td>
                            @if($invoice->isConfirmed() && $inst->status !== 'paid' && (auth()->user()->isAdmin() || auth()->user()->hasPermission('sales_invoices.pay')))
                            <form method="post" action="{{ route('sales.invoices.payments.store', $invoice) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="invoice_installment_id" value="{{ $inst->id }}">
                                <input type="hidden" name="amount" value="{{ $inst->remainingAmount() }}">
                                <input type="hidden" name="payment_method" value="cash">
                                <button type="submit" class="btn btn-xs btn-success">{{ __('sales_invoices.pay_installment') }}</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        @if($invoice->isDraft())
        <div class="card">
            <div class="card-body">
                @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('sales_invoices.edit'))
                <a href="{{ route('sales.invoices.edit', $invoice) }}" class="btn btn-primary btn-block mb-2">{{ __('sales_invoices.edit') }}</a>
                @endif
                @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('sales_invoices.confirm'))
                <form method="post" action="{{ route('sales.invoices.confirm', $invoice) }}">
                    @csrf
                    @if($invoice->payment_term === 'installment')
                    <p class="tx-13 text-muted">{{ __('sales_invoices.confirm_installment_hint') }}</p>
                    <div class="form-group"><label>{{ __('sales_invoices.down_payment') }}</label><input type="number" step="0.01" name="down_payment" class="form-control" value="0"></div>
                    <div class="form-group"><label>{{ __('sales_invoices.installment_count') }}</label><input type="number" name="installment_count" class="form-control" value="3" min="1" required></div>
                    <div class="form-group"><label>{{ __('sales_invoices.interval_days') }}</label><input type="number" name="interval_days" class="form-control" value="30" min="1" required></div>
                    <div class="form-group"><label>{{ __('sales_invoices.first_due_date') }}</label><input type="date" name="first_due_date" class="form-control" value="{{ now()->addMonth()->format('Y-m-d') }}" required></div>
                    @endif
                    <button type="submit" class="btn btn-success btn-block">{{ __('sales_invoices.confirm') }}</button>
                </form>
                @endif
                @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('sales_invoices.cancel'))
                <form method="post" action="{{ route('sales.invoices.cancel', $invoice) }}" class="mt-2" onsubmit="return confirm('{{ __('sales_invoices.cancel_confirm') }}')">
                    @csrf
                    <button class="btn btn-outline-danger btn-block">{{ __('sales_invoices.cancel') }}</button>
                </form>
                @endif
            </div>
        </div>
        @endif

        @if($invoice->isConfirmed() && $invoice->balance_due > 0.009)
        <div class="card" id="pay">
            <div class="card-header"><h5 class="mb-0">{{ __('sales_invoices.record_payment') }}</h5></div>
            <div class="card-body">
                @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('sales_invoices.pay'))
                <form method="post" action="{{ route('sales.invoices.payments.store', $invoice) }}">
                    @csrf
                    <div class="form-group">
                        <label>{{ __('sales_invoices.amount') }}</label>
                        <input type="number" step="0.01" name="amount" class="form-control" max="{{ $invoice->balance_due }}" value="{{ $invoice->balance_due }}" required>
                    </div>
                    <div class="form-group">
                        <label>{{ __('sales_invoices.payment_method') }}</label>
                        <select name="payment_method" class="form-control">
                            <option value="cash">{{ __('sales_invoices.method_cash') }}</option>
                            <option value="bank_transfer">{{ __('sales_invoices.method_bank') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ __('sales_invoices.paid_at') }}</label>
                        <input type="datetime-local" name="paid_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __('sales_invoices.reference') }}</label>
                        <input type="text" name="reference" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-success btn-block">{{ __('sales_invoices.pay') }}</button>
                </form>
                @endif
            </div>
        </div>
        @endif

        @include('partials.shipment-card', [
            'shipment' => $invoice->shipment,
            'shippingIntegration' => $shippingIntegration ?? null,
            'canManage' => $invoice->isConfirmed() && (auth()->user()->isAdmin() || auth()->user()->hasPermission('sales_invoices.edit')),
            'refreshUrl' => $invoice->shipment ? route('sales.invoices.shipments.refresh', $invoice) : null,
            'updateUrl' => $invoice->shipment ? route('sales.invoices.shipments.update', $invoice) : null,
            'createUrl' => route('sales.invoices.shipments.store', $invoice),
            'createMode' => 'invoice',
            'invoice' => $invoice,
            'shippingRates' => $shippingRates ?? collect(),
            'notes' => $invoice->notes,
        ])

        @if($invoice->paymentAllocations->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header"><h5 class="mb-0">{{ __('sales_invoices.payments_history') }}</h5></div>
            <ul class="list-group list-group-flush">
                @foreach($invoice->paymentAllocations as $alloc)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ $alloc->payment->payment_number ?? '—' }}</span>
                    <strong>{{ number_format($alloc->amount, 2) }}</strong>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>
@endsection
