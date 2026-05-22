@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">General Ledger</h2>
    </div>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row mb-4">
            <div class="col-md-3">
                <label>Account</label>
                <select name="account_id" class="form-control">
                    <option value="">All accounts</option>
                    @foreach($accounts as $account)
                    <option value="{{ $account->id }}" {{ (string)$accountId === (string)$account->id ? 'selected' : '' }}>{{ $account->code }} - {{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>From</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-md-2">
                <label>To</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-md-2 align-self-end">
                <button class="btn btn-primary" type="submit">Run</button>
            </div>
        </form>
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>JE Ref</th>
                    <th>Account</th>
                    <th>Description</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Credit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $line)
                <tr>
                    <td>{{ $line->journalEntry->date->format('Y-m-d') }}</td>
                    <td>{{ $line->journalEntry->reference }}</td>
                    <td>{{ $line->account->code }} {{ $line->account->name }}</td>
                    <td>{{ $line->description ?? $line->journalEntry->description }}</td>
                    <td class="text-right">{{ number_format($line->debit, 2) }}</td>
                    <td class="text-right">{{ number_format($line->credit, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted">No lines for this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
