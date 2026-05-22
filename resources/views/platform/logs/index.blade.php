@extends('layouts.platform.master')
@section('title', __('platform.logs.title'))

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">{{ __('platform.logs.title') }}</h2>
        <p class="mg-b-0 text-muted">{{ __('platform.logs.subtitle') }}</p>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card shadow mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <select name="tenant_id" class="form-select">
                        <option value="">{{ __('platform.logs.all_tenants') }}</option>
                        @foreach($tenants as $t)
                        <option value="{{ $t->id }}" @selected(request('tenant_id') == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
                <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
                <div class="col-md-2"><button class="btn btn-primary">{{ __('platform.invoices.filter') }}</button></div>
            </form>
        </div>
    </div>
    <div class="card shadow">
        <div class="card-body p-0">
            <table class="table table-hover mb-0 small">
                <thead>
                    <tr>
                        <th>{{ __('platform.logs.date') }}</th>
                        <th>{{ __('platform.logs.tenant') }}</th>
                        <th>{{ __('platform.logs.user') }}</th>
                        <th>{{ __('platform.logs.action') }}</th>
                        <th>{{ __('platform.logs.description') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $log->tenant->name ?? '—' }}</td>
                        <td>{{ $log->user->name ?? '—' }}</td>
                        <td><span class="badge bg-secondary">{{ $log->type }}</span></td>
                        <td>{{ \Illuminate\Support\Str::limit($log->description, 80) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('platform.logs.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())<div class="card-footer">{{ $logs->links() }}</div>@endif
    </div>
</div>
@endsection
