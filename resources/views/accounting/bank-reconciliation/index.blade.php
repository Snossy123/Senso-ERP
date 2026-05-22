@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">Bank Reconciliation</h2>
        <p class="mg-b-0 text-muted">Match bank statement lines to posted GL journal lines.</p>
    </div>
</div>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.bank-reconciliation') }}" class="form-row align-items-end">
            <div class="form-group col-md-5">
                <label>Bank / card GL account</label>
                <select name="account_id" class="form-control" required>
                    @foreach($accounts as $account)
                    <option value="{{ $account->id }}" {{ $accountId == $account->id ? 'selected' : '' }}>
                        {{ $account->code }} — {{ $account->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3">
                <label>Through date</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="form-group col-md-2">
                <button type="submit" class="btn btn-primary btn-block">Load</button>
            </div>
        </form>
    </div>
</div>

@if($summary)
<div class="row mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">GL balance</small><h4>{{ number_format($summary['gl_balance'], 2) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Unmatched statement lines</small><h4>{{ $summary['unreconciled_statement_lines'] }}</h4></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Unmatched GL lines</small><h4>{{ $summary['unreconciled_gl_lines'] }}</h4></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Unmatched stmt amount</small><h4>{{ number_format($summary['unreconciled_statement_amount'], 2) }}</h4></div></div></div>
</div>
@endif

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0">Import statement line</h5>
            </div>
            <div class="card-body">
                @if($accountId)
                <form method="POST" action="{{ route('accounting.bank-reconciliation.import') }}">
                    @csrf
                    <input type="hidden" name="account_id" value="{{ $accountId }}">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="transaction_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type" class="form-control" required>
                            <option value="debit">Debit (deposit)</option>
                            <option value="credit">Credit (withdrawal)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reference</label>
                        <input type="text" name="reference" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Import line</button>
                </form>
                @else
                <p class="text-muted mb-0">Map bank/card accounts in Accounting Settings, then reload.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><h5>Unreconciled statement lines</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Date</th><th>Ref</th><th>Type</th><th class="text-right">Amount</th><th>Match</th></tr></thead>
                    <tbody>
                        @forelse($statementLines as $line)
                        <tr>
                            <td>{{ $line->transaction_date->format('Y-m-d') }}</td>
                            <td>{{ $line->reference ?? '—' }}</td>
                            <td>{{ $line->type }}</td>
                            <td class="text-right">{{ number_format($line->amount, 2) }}</td>
                            <td>
                                <form method="POST" action="{{ route('accounting.bank-reconciliation.match') }}" class="form-inline">
                                    @csrf
                                    <input type="hidden" name="statement_line_id" value="{{ $line->id }}">
                                    <select name="journal_entry_line_id" class="form-control form-control-sm" required>
                                        <option value="">GL line…</option>
                                        @foreach($glLines as $gl)
                                        <option value="{{ $gl->id }}">
                                            {{ \Illuminate\Support\Carbon::parse($gl->journalEntry->date)->format('Y-m-d') }} — {{ \Illuminate\Support\Str::limit($gl->description ?? $gl->journalEntry->reference, 24) }} ({{ number_format((float) $gl->debit + (float) $gl->credit, 2) }})
                                        </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-success ml-1">Match</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No unreconciled statement lines.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h5>Unreconciled GL lines</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Date</th><th>JE</th><th>Description</th><th class="text-right">Dr</th><th class="text-right">Cr</th></tr></thead>
                    <tbody>
                        @forelse($glLines as $gl)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($gl->journalEntry->date)->format('Y-m-d') }}</td>
                            <td>{{ $gl->journalEntry->reference }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($gl->description, 40) }}</td>
                            <td class="text-right">{{ number_format($gl->debit, 2) }}</td>
                            <td class="text-right">{{ number_format($gl->credit, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No unreconciled GL lines.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
