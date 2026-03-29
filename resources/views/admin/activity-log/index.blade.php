@extends('layouts.master')
@section('title', 'Activity Log')

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="left-content">
            <div>
                <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">Activity Log</h2>
                <p class="mg-b-0">System audit trail and change tracking.</p>
            </div>
        </div>
        <div class="main-dashboard-header-right">
            <a href="{{ request()->fullUrlWithQuery(['export' => 1]) }}" class="btn btn-success">
                <i class="fas fa-file-csv me-2"></i> Export CSV
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter me-2"></i>Filters</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.activity.index') }}" method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">User</label>
                    <select name="user_id" class="form-select">
                        <option value="">All Users</option>
                        @foreach($users as $id => $name)
                            <option value="{{ $id }}" {{ request('user_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Severity</label>
                    <select name="severity" class="form-select">
                        <option value="">All Levels</option>
                        <option value="info" {{ request('severity') == 'info' ? 'selected' : '' }}>Info</option>
                        <option value="warning" {{ request('severity') == 'warning' ? 'selected' : '' }}>Warning</option>
                        <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-2"></i>Apply</button>
                    <a href="{{ route('admin.activity.index') }}" class="btn btn-outline-secondary ms-2"><i class="fas fa-redo"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Severity</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                            <tr>
                                <td>{{ $activity->id }}</td>
                                <td>{{ $activity->created_at->format('M d, H:i:s') }}</td>
                                <td>
                                    @if($activity->user)
                                        <span class="fw-bold">{{ $activity->user->name }}</span>
                                    @else
                                        <span class="text-muted">System</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-outline-info text-info border-info">{{ strtoupper($activity->action) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ match($activity->severity) {
                                        'critical' => 'danger',
                                        'warning' => 'warning',
                                        default => 'info'
                                    } }}">{{ ucfirst($activity->severity) }}</span>
                                </td>
                                <td>
                                    {{ $activity->description }}
                                    @if($activity->model_type)
                                        <br><small class="text-muted">{{ basename($activity->model_type) }} #{{ $activity->model_id }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $activity->ip_address }}</code></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#logModal{{ $activity->id }}">
                                        <i class="fas fa-info-circle"></i>
                                    </button>

                                    <!-- Details Modal -->
                                    <div class="modal fade" id="logModal{{ $activity->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Activity Details #{{ $activity->id }}</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <strong>User Agent:</strong><br>
                                                            <small class="text-wrap">{{ $activity->user_agent }}</small>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Metadata:</strong><br>
                                                            <pre class="bg-light p-2 rounded" style="font-size: 11px;">{{ json_encode($activity->properties, JSON_PRETTY_PRINT) }}</pre>
                                                        </div>
                                                    </div>

                                                    @if($activity->before_values || $activity->after_values)
                                                        <hr>
                                                        <h6><i class="fas fa-exchange-alt me-2 text-primary"></i>Change Tracking</h6>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="p-2 border bg-light rounded" style="font-size: 11px;">
                                                                    <strong class="text-danger">BEFORE:</strong>
                                                                    <pre class="mb-0">{{ json_encode($activity->before_values, JSON_PRETTY_PRINT) }}</pre>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="p-2 border bg-white rounded shadow-sm" style="font-size: 11px;">
                                                                    <strong class="text-success">AFTER:</strong>
                                                                    <pre class="mb-0">{{ json_encode($activity->after_values, JSON_PRETTY_PRINT) }}</pre>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">No activity logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $activities->links() }}
        </div>
    </div>
</div>
@endsection
