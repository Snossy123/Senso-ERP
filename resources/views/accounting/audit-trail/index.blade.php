@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">Accounting Audit Trail</h2>
    </div>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row mb-4">
            <div class="col-md-3">
                <input type="text" name="reference" class="form-control" placeholder="Reference" value="{{ request('reference') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary" type="submit">Filter</button>
            </div>
        </form>
        @foreach($entries as $entry)
        <div class="border rounded p-3 mb-3">
            <div class="d-flex justify-content-between">
                <div>
                    <strong>{{ $entry->reference }}</strong> — {{ $entry->description }}
                    <span class="badge badge-{{ $entry->status === 'posted' ? 'success' : 'warning' }}">{{ $entry->status }}</span>
                </div>
                <div class="text-muted">{{ $entry->date->format('Y-m-d') }}</div>
            </div>
            <small class="text-muted">
                Created by {{ $entry->creator?->name ?? 'System' }}
                @if($entry->source_type)
                    | Source: {{ class_basename($entry->source_type) }} #{{ $entry->source_id }}
                @endif
            </small>
            <table class="table table-sm mt-2 mb-0">
                <thead><tr><th>Account</th><th>Debit</th><th>Credit</th></tr></thead>
                <tbody>
                    @foreach($entry->lines as $line)
                    <tr>
                        <td>{{ $line->account->code }} {{ $line->account->name }}</td>
                        <td>{{ number_format($line->debit, 2) }}</td>
                        <td>{{ number_format($line->credit, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if(auth()->user()->isAdmin() && $entry->status === 'draft')
            <form action="{{ route('accounting.journal-entries.approve', $entry) }}" method="POST" class="d-inline mt-2">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary">Approve</button>
            </form>
            @endif
            @if(auth()->user()->isAdmin() && in_array($entry->status, ['draft', 'approved']))
            <form action="{{ route('accounting.journal-entries.post', $entry) }}" method="POST" class="d-inline mt-2">
                @csrf
                <button type="submit" class="btn btn-sm btn-success">Post to GL</button>
            </form>
            @endif
        </div>
        @endforeach
        {{ $entries->links() }}
    </div>
</div>
@endsection
