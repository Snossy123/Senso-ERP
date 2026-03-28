@extends('layouts.app')
@section('title', 'Tenant Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tenant Management</h1>
        <a href="{{ route('tenants.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Tenant
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Domain</th>
                            <th>Status</th>
                            <th>Users</th>
                            <th>Trial Ends</th>
                            <th>Subscription Ends</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenants as $tenant)
                            <tr>
                                <td>{{ $tenant->name }}</td>
                                <td>{{ $tenant->slug }}</td>
                                <td>{{ $tenant->domain ?? '-' }}</td>
                                <td>
                                    @if($tenant->isActive())
                                        <span class="badge bg-success">Active</span>
                                    @elseif($tenant->isOnTrial())
                                        <span class="badge bg-warning">Trial</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $tenant->users()->count() }}</td>
                                <td>{{ $tenant->trial_ends_at?->format('M d, Y') ?? '-' }}</td>
                                <td>{{ $tenant->subscription_ends_at?->format('M d, Y') ?? '-' }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('tenants.show', $tenant) }}" class="btn btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('tenants.toggle', $tenant) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-{{ $tenant->is_active ? 'warning' : 'success' }}" title="{{ $tenant->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="fas fa-{{ $tenant->is_active ? 'ban' : 'check' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('tenants.destroy', $tenant) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No tenants found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $tenants->links() }}
        </div>
    </div>
</div>
@endsection
