@extends('layouts.app')
@section('title', $tenant->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ $tenant->name }}</h1>
        <div>
            <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('tenants.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Details</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>Slug:</th>
                            <td>{{ $tenant->slug }}</td>
                        </tr>
                        <tr>
                            <th>Domain:</th>
                            <td>{{ $tenant->domain ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                @if($tenant->isActive())
                                    <span class="badge bg-success">Active</span>
                                @elseif($tenant->isOnTrial())
                                    <span class="badge bg-warning">Trial</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Trial Ends:</th>
                            <td>{{ $tenant->trial_ends_at?->format('M d, Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Subscription Ends:</th>
                            <td>{{ $tenant->subscription_ends_at?->format('M d, Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Created:</th>
                            <td>{{ $tenant->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <h3 class="text-primary">{{ $tenant->users()->count() }}</h3>
                                <small class="text-muted">Users</small>
                            </div>
                            <div class="col-6 mb-3">
                                <h3 class="text-success">{{ $tenant->products()->count() }}</h3>
                                <small class="text-muted">Products</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-info">{{ $tenant->sales()->count() }}</h3>
                                <small class="text-muted">Sales</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-warning">{{ $tenant->customers()->count() }}</h3>
                                <small class="text-muted">Customers</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Settings</h6>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-2 rounded" style="font-size: 0.75rem;">{{ json_encode($tenant->settings ?? [], JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Users</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenant->users()->latest()->take(5)->get() as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ ucfirst($user->role) }}</td>
                                <td>
                                    @if($user->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
