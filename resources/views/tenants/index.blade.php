@extends('layouts.master')
@section('title', __('tenants.title'))

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="left-content">
            <div>
                <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">{{ __('tenants.title') }}</h2>
                <p class="mg-b-0">{{ __('tenants.subtitle') }}</p>
            </div>
        </div>
        <div class="main-dashboard-header-right">
            <a href="{{ route('tenants.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> {{ __('tenants.add_tenant') }}
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="container-fluid">

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
                            <th>{{ __('tenants.name') }}</th>
                            <th>{{ __('tenants.domain') }}</th>
                            <th>{{ __('tenants.plan') }}</th>
                            <th>{{ __('tenants.status') }}</th>
                            <th>{{ __('tenants.billing') }}</th>
                            <th>
                                <span class="d-block">{{ __('tenants.users') }}</span>
                                <small class="text-muted fw-normal">{{ __('tenants.users_usage_column_hint') }}</small>
                            </th>
                            <th>{{ __('tenants.expires') }}</th>
                            <th>{{ __('tenants.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenants as $tenant)
                            <tr>
                                <td>
                                    <a href="{{ route('tenants.show', $tenant) }}">{{ $tenant->name }}</a>
                                    <br><small class="text-muted">{{ $tenant->slug }}</small>
                                </td>
                                <td>{{ $tenant->domain ?? '-' }}</td>
                                <td>
                                    @if($tenant->plan)
                                        <span class="badge bg-primary">{{ $tenant->plan->name }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('tenants.no_plan') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $tenant->status_color }}">{{ $tenant->status_label }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $tenant->payment_status === 'paid' ? 'success' : ($tenant->payment_status === 'failed' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($tenant->payment_status) }}
                                    </span>
                                    @if($tenant->price > 0)
                                        <br><small class="text-muted">${{ number_format($tenant->price, 2) }}/{{ $tenant->billing_cycle }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $tenant->users()->count() }}</span>
                                    @if($tenant->plan)
                                        <span class="text-muted"> / {{ $tenant->plan->max_users }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($tenant->isOnTrial() && $tenant->trial_ends_at)
                                        <span class="text-warning">Trial: {{ $tenant->trial_ends_at->format('M d') }}</span>
                                    @elseif($tenant->subscription_ends_at)
                                        <span class="{{ $tenant->subscription_ends_at->isPast() ? 'text-danger' : '' }}">
                                            {{ $tenant->subscription_ends_at->format('M d, Y') }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <div class="btn-group btn-group-sm flex-wrap gap-1" role="group" style="max-width: 100%;">
                                        <a href="{{ route('tenants.show', $tenant) }}" class="btn btn-info" title="{{ __('tenants.view') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-primary" title="{{ __('tenants.edit_action') }}">
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
                                <td colspan="8" class="text-center">{{ __('tenants.empty_table') }}</td>
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
