@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Admin</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Activity Log</span>
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
                    <h4 class="card-title mg-b-0">Activity Log</h4>
                </div>
                <p class="tx-12 tx-gray-500 mb-2">Track all user actions and system activities.</p>
            </div>
            <div class="card-body">
                <form method="GET" class="row mb-4">
                    <div class="col-md-2">
                        <label>Type</label>
                        <select name="type" class="form-control">
                            <option value="">All Types</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>User</label>
                        <select name="user_id" class="form-control">
                            <option value="">All Users</option>
                            @foreach($users as $id => $name)
                                <option value="{{ $id }}" {{ request('user_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label>Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <a href="{{ route('admin.activity.index') }}" class="btn btn-secondary btn-block">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-md-nowrap">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activities as $activity)
                            <tr>
                                <td>
                                    <small>{{ $activity->created_at->format('M d, H:i') }}</small>
                                </td>
                                <td>
                                    @if($activity->user)
                                        <a href="{{ route('admin.activity.user', $activity->user_id) }}">
                                            {{ $activity->user->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">System</span>
                                    @endif
                                </td>
                                <td>
                                    @if($activity->type === 'auth')
                                        <span class="badge badge-primary">Auth</span>
                                    @elseif($activity->type === 'crud')
                                        <span class="badge badge-info">CRUD</span>
                                    @elseif($activity->type === 'sale')
                                        <span class="badge badge-success">Sale</span>
                                    @elseif($activity->type === 'order')
                                        <span class="badge badge-warning">Order</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $activity->type }}</span>
                                    @endif
                                </td>
                                <td><small>{{ $activity->action }}</small></td>
                                <td>
                                    <span>{{ $activity->description }}</span>
                                </td>
                                <td><small class="text-muted">{{ $activity->ip_address ?? '-' }}</small></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fa fa-history fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No activity recorded yet</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $activities->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
