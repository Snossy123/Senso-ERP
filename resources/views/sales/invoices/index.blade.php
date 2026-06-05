@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">{{ __('sales_invoices.title') }}</h4>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('sales_invoices.create'))
        <a href="{{ route('sales.invoices.create') }}" class="btn btn-primary"><i class="fe fe-plus"></i> {{ __('sales_invoices.new') }}</a>
        @endif
        <a href="{{ route('sales.invoices.index', ['overdue_installments' => 1]) }}" class="btn btn-outline-warning ml-2">{{ __('sales_invoices.overdue_installments') }}</a>
    </div>
</div>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        @if($overdueInstallments)
            <h5 class="mb-3">{{ __('sales_invoices.overdue_installments') }}</h5>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-hover">
                    <thead><tr><th>{{ __('sales_invoices.invoice') }}</th><th>{{ __('sales_invoices.customer') }}</th><th>#</th><th>{{ __('sales_invoices.due_date') }}</th><th>{{ __('sales_invoices.amount') }}</th><th></th></tr></thead>
                    <tbody>
                    @forelse($overdueInstallments as $inst)
                        <tr>
                            <td><a href="{{ route('sales.invoices.show', $inst->invoice) }}">{{ $inst->invoice->invoice_number }}</a></td>
                            <td>{{ $inst->invoice->customer->name ?? '—' }}</td>
                            <td>{{ $inst->sequence }}</td>
                            <td>{{ $inst->due_date->format('Y-m-d') }}</td>
                            <td>{{ number_format($inst->remainingAmount(), 2) }}</td>
                            <td><a href="{{ route('sales.invoices.show', $inst->invoice) }}#pay" class="btn btn-sm btn-success">{{ __('sales_invoices.pay') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">{{ __('sales_invoices.no_overdue') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $overdueInstallments->links() }}
            <hr>
        @endif

        <form method="get" class="form-row mb-4">
            <div class="col-md-3"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('sales_invoices.search') }}"></div>
            <div class="col-md-2">
                <select name="status" class="form-control">
                    <option value="">{{ __('sales_invoices.all_statuses') }}</option>
                    @foreach(['draft','confirmed','cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status')===$s)>{{ __('sales_invoices.status_'.$s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="payment_status" class="form-control">
                    <option value="">{{ __('sales_invoices.all_payment_statuses') }}</option>
                    @foreach(['unpaid','partial','paid'] as $ps)
                    <option value="{{ $ps }}" @selected(request('payment_status')===$ps)>{{ __('sales_invoices.payment_'.$ps) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="payment_term" class="form-control">
                    <option value="">{{ __('sales_invoices.all_payment_terms') }}</option>
                    @foreach(['cash','credit','installment'] as $term)
                    <option value="{{ $term }}" @selected(request('payment_term')===$term)>{{ __('sales_invoices.term_'.$term) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary btn-block">{{ __('sales_invoices.filter') }}</button></div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('sales_invoices.number') }}</th>
                        <th>{{ __('sales_invoices.customer') }}</th>
                        <th>{{ __('sales_invoices.date') }}</th>
                        <th>{{ __('sales_invoices.total') }}</th>
                        <th>{{ __('sales_invoices.balance') }}</th>
                        <th>{{ __('sales_invoices.status') }}</th>
                        <th>{{ __('sales_invoices.payment') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td><a href="{{ route('sales.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                        <td>{{ $invoice->customer->name ?? '—' }}</td>
                        <td>{{ $invoice->invoice_date?->format('Y-m-d') ?? '—' }}</td>
                        <td>{{ number_format($invoice->total, 2) }}</td>
                        <td>{{ number_format($invoice->balance_due, 2) }}</td>
                        <td><span class="badge badge-light">{{ __('sales_invoices.status_'.$invoice->status) }}</span></td>
                        <td><span class="badge badge-{{ $invoice->payment_status === 'paid' ? 'success' : ($invoice->payment_status === 'partial' ? 'warning' : 'secondary') }}">{{ __('sales_invoices.payment_'.$invoice->payment_status) }}</span></td>
                        <td><a href="{{ route('sales.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">{{ __('sales_invoices.view') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted text-center">{{ __('sales_invoices.empty') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $invoices->links() }}
    </div>
</div>
@endsection
