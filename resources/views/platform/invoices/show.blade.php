@extends('layouts.platform.master')
@section('title', $invoice->number)

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-body">
            <h4>{{ $invoice->number }}</h4>
            <p>{{ __('platform.invoices.tenant') }}: <strong>{{ $invoice->tenant->name }}</strong></p>
            <p>{{ __('platform.invoices.amount') }}: {{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</p>
            <p>{{ __('platform.invoices.status') }}: <span class="badge bg-{{ $invoice->status_color }}">{{ __('platform.invoices.status_'.$invoice->status) }}</span></p>
            <p>{{ __('platform.invoices.due') }}: {{ $invoice->due_at?->format('Y-m-d') }}</p>
            @if($invoice->status !== 'paid')
            <form action="{{ route('platform.invoices.mark-paid', $invoice) }}" method="POST">@csrf
                <button class="btn btn-success">{{ __('platform.invoices.mark_paid') }}</button>
            </form>
            @endif
            <a href="{{ route('platform.invoices.index') }}" class="btn btn-secondary mt-2">{{ __('platform.plans.cancel') }}</a>
        </div>
    </div>
</div>
@endsection
