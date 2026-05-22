@extends('layouts.platform.master')
@section('title', __('platform.invoices.title'))

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">{{ __('platform.invoices.title') }}</h2>
        <p class="mg-b-0 text-muted">{{ __('platform.invoices.subtitle') }}</p>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="card shadow mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">{{ __('platform.invoices.all_statuses') }}</option>
                        @foreach(['pending','paid','overdue','cancelled'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ __('platform.invoices.status_'.$s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-primary">{{ __('platform.invoices.filter') }}</button></div>
            </form>
        </div>
    </div>
    <div class="card shadow">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('platform.invoices.number') }}</th>
                        <th>{{ __('platform.invoices.tenant') }}</th>
                        <th>{{ __('platform.invoices.amount') }}</th>
                        <th>{{ __('platform.invoices.status') }}</th>
                        <th>{{ __('platform.invoices.due') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td><a href="{{ route('platform.invoices.show', $invoice) }}">{{ $invoice->number }}</a></td>
                        <td>{{ $invoice->tenant->name ?? '—' }}</td>
                        <td>{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</td>
                        <td><span class="badge bg-{{ $invoice->status_color }}">{{ __('platform.invoices.status_'.$invoice->status) }}</span></td>
                        <td>{{ $invoice->due_at?->format('Y-m-d') ?? '—' }}</td>
                        <td class="text-end">
                            @if($invoice->status !== 'paid')
                            <form action="{{ route('platform.invoices.mark-paid', $invoice) }}" method="POST" class="d-inline">@csrf
                                <button class="btn btn-sm btn-success">{{ __('platform.invoices.mark_paid') }}</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('platform.invoices.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())<div class="card-footer">{{ $invoices->links() }}</div>@endif
    </div>
</div>
@endsection
