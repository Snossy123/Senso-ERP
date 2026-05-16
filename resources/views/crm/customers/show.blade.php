@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between flex-wrap">
    <div class="my-auto">
        <h4 class="content-title mb-0">{{ $customer->name }}</h4>
        <p class="text-muted mb-0 tx-13">{{ $customer->company }} · {{ $customer->phone }} · {{ $customer->email }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('crm.customers.edit', $customer) }}" class="btn btn-primary">Edit</a>
        <a href="{{ route('crm.customers.index') }}" class="btn btn-secondary">All customers</a>
    </div>
</div>
@endsection
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row">
    <div class="col-lg-8">
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-sales">Sales</a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-notes">Notes</a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-activity">Activity</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="tab-sales">
                <div class="card"><div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Sale</th><th>Date</th><th>Cashier</th><th class="text-right">Total</th></tr></thead>
                        <tbody>
                            @forelse($sales as $sale)
                                <tr>
                                    <td><a href="{{ route('pos.sales.show', $sale) }}">{{ $sale->sale_number }}</a></td>
                                    <td>{{ $sale->created_at->format('M d, Y H:i') }}</td>
                                    <td>{{ $sale->user?->name }}</td>
                                    <td class="text-right">{{ config('app.currency') }} {{ number_format($sale->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center py-4">No POS sales yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div></div>
            </div>
            <div class="tab-pane" id="tab-notes">
                <form method="post" action="{{ route('crm.customers.notes.store', $customer) }}" class="mb-3">
                    @csrf
                    <textarea name="body" class="form-control mb-2" rows="3" placeholder="Add a note..." required></textarea>
                    <button class="btn btn-sm btn-primary">Add note</button>
                </form>
                @foreach($customer->notes as $note)
                    <div class="card mb-2 {{ $note->is_pinned ? 'border-primary' : '' }}">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">{{ $note->user?->name }} · {{ $note->created_at->diffForHumans() }}</small>
                                <form method="post" action="{{ route('crm.customers.notes.destroy', [$customer, $note]) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-link btn-sm text-danger p-0">Remove</button>
                                </form>
                            </div>
                            <p class="mb-0 mt-2">{{ $note->body }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="tab-pane" id="tab-activity">
                <div class="list-group">
                    @forelse($activities as $act)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $act->action }}</strong>
                                <small class="text-muted">{{ $act->created_at->diffForHumans() }}</small>
                            </div>
                            <p class="mb-0 text-muted tx-13">{{ $act->description }}</p>
                        </div>
                    @empty
                        <p class="text-muted">No activity logged yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <h6 class="text-uppercase text-muted tx-11">Profile</h6>
            <p class="mb-1"><strong>Source:</strong> {{ $customer->source ?? '—' }}</p>
            <p class="mb-1"><strong>Assigned:</strong> {{ $customer->assignedUser?->name ?? '—' }}</p>
            <p class="mb-0"><strong>Address:</strong> {{ $customer->address }} {{ $customer->city }}</p>
            <div class="mt-3">
                @foreach($customer->tags as $tag)
                    <span class="badge mr-1" style="background:{{ $tag->color }}20;color:{{ $tag->color }}">{{ $tag->name }}</span>
                @endforeach
            </div>
        </div></div>
    </div>
</div>
@endsection
