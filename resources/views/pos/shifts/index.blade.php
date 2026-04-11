@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">POS</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ Shift Reports</span>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between">
                    <h4 class="card-title mg-b-0">Pos Shifts Histroy</h4>
                </div>
                <!-- Filters -->
                <form action="{{ route('pos.shifts.index') }}" method="GET" class="mt-3">
                    <div class="row row-sm">
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label>Cashier</label>
                                <select name="user_id" class="form-control">
                                    <option value="">All Cashiers</option>
                                    @foreach($cashiers as $id => $name)
                                        <option value="{{ $id }}" {{ request('user_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All</option>
                                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group">
                                <label>From Date</label>
                                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group">
                                <label>To Date</label>
                                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary btn-block"><i class="fe fe-filter mr-1"></i> Filter</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-md-nowrap">
                        <thead>
                            <tr>
                                <th>Terminal</th>
                                <th>Cashier</th>
                                <th>Opened At</th>
                                <th>Closed At</th>
                                <th>Opening Float</th>
                                <th>Closing Float</th>
                                <th>Expected</th>
                                <th>Variance</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shifts as $shift)
                            <tr>
                                <td>{{ $shift->terminal_id }}</td>
                                <td>{{ $shift->user?->name }}</td>
                                <td>{{ $shift->opened_at->format('M d, H:i') }}</td>
                                <td>{{ $shift->closed_at ? $shift->closed_at->format('M d, H:i') : '—' }}</td>
                                <td>{{ number_format($shift->opening_float, 2) }}</td>
                                <td>{{ $shift->closing_float ? number_format($shift->closing_float, 2) : '—' }}</td>
                                <td>{{ $shift->expected_cash ? number_format($shift->expected_cash, 2) : '—' }}</td>
                                <td>
                                    @if($shift->status === 'closed')
                                        <span class="{{ $shift->variance == 0 ? 'text-success' : 'text-danger' }} font-weight-bold">
                                            {{ number_format($shift->variance, 2) }}
                                        </span>
                                    @else
                                        <span class="text-muted small">Live</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $shift->status === 'open' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($shift->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('pos.shifts.show', $shift) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fe fe-eye"></i> View Report
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">No shifts found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $shifts->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
