@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">Financial Periods</h2>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h5>Open New Period</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('accounting.periods.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="FY 2026 Q1">
                    </div>
                    <div class="form-group">
                        <label>Start date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>End date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Period</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Name</th><th>Range</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($periods as $period)
                        <tr>
                            <td>{{ $period->name }}</td>
                            <td>{{ $period->start_date->format('Y-m-d') }} — {{ $period->end_date->format('Y-m-d') }}</td>
                            <td><span class="badge badge-{{ $period->status === 'open' ? 'success' : 'secondary' }}">{{ ucfirst($period->status) }}</span></td>
                            <td>
                                @if($period->status === 'open' && auth()->user()->isAdmin())
                                <form method="POST" action="{{ route('accounting.periods.close', $period) }}" class="d-inline" onsubmit="return confirm('Close this period? No new entries can be posted to these dates.');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning">Close</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted">No periods defined.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
