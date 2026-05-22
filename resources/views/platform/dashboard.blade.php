@extends('layouts.platform.master')
@section('title', __('platform.dashboard.title'))

@section('page-header')
<div class="breadcrumb-header justify-content-between">
	<div class="left-content">
		<div>
			<h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">{{ __('platform.dashboard.title') }}</h2>
			<p class="mg-b-0">{{ __('platform.dashboard.subtitle') }}</p>
		</div>
	</div>
	<div class="main-dashboard-header-right">
		<a href="{{ route('platform.tenants.create') }}" class="btn btn-primary">
			<i class="fas fa-plus"></i> {{ __('tenants.add_tenant') }}
		</a>
	</div>
</div>
@endsection

@section('content')
<div class="container-fluid">
	<div class="row">
		<div class="col-md-2 col-6 mb-3">
			<div class="card shadow h-100">
				<div class="card-body text-center">
					<p class="text-muted mb-1 small">{{ __('platform.dashboard.total_tenants') }}</p>
					<h3 class="mb-0">{{ $stats['total'] }}</h3>
				</div>
			</div>
		</div>
		<div class="col-md-2 col-6 mb-3">
			<div class="card shadow h-100 border-start border-4 border-success">
				<div class="card-body text-center">
					<p class="text-muted mb-1 small">{{ __('platform.dashboard.active') }}</p>
					<h3 class="mb-0 text-success">{{ $stats['active'] }}</h3>
				</div>
			</div>
		</div>
		<div class="col-md-2 col-6 mb-3">
			<div class="card shadow h-100 border-start border-4 border-info">
				<div class="card-body text-center">
					<p class="text-muted mb-1 small">{{ __('platform.dashboard.trial') }}</p>
					<h3 class="mb-0 text-info">{{ $stats['trial'] }}</h3>
				</div>
			</div>
		</div>
		<div class="col-md-2 col-6 mb-3">
			<div class="card shadow h-100 border-start border-4 border-danger">
				<div class="card-body text-center">
					<p class="text-muted mb-1 small">{{ __('platform.dashboard.suspended') }}</p>
					<h3 class="mb-0 text-danger">{{ $stats['suspended'] }}</h3>
				</div>
			</div>
		</div>
		<div class="col-md-2 col-6 mb-3">
			<div class="card shadow h-100">
				<div class="card-body text-center">
					<p class="text-muted mb-1 small">{{ __('platform.dashboard.expired') }}</p>
					<h3 class="mb-0">{{ $stats['expired'] }}</h3>
				</div>
			</div>
		</div>
		<div class="col-md-2 col-6 mb-3">
			<div class="card shadow h-100">
				<div class="card-body text-center">
					<p class="text-muted mb-1 small">{{ __('platform.dashboard.plans') }}</p>
					<h3 class="mb-0">{{ $plansCount }}</h3>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-8 mb-4">
			<div class="card shadow">
				<div class="card-header d-flex justify-content-between align-items-center">
					<h5 class="mb-0">{{ __('platform.dashboard.recent_tenants') }}</h5>
					<a href="{{ route('platform.tenants.index') }}" class="btn btn-sm btn-outline-primary">{{ __('platform.dashboard.view_all') }}</a>
				</div>
				<div class="card-body p-0">
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead>
								<tr>
									<th>{{ __('tenants.name') }}</th>
									<th>{{ __('tenants.plan') }}</th>
									<th>{{ __('tenants.status') }}</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								@forelse($recentTenants as $tenant)
								<tr>
									<td>{{ $tenant->name }}</td>
									<td>{{ $tenant->plan->name ?? __('tenants.no_plan') }}</td>
									<td><span class="badge bg-{{ $tenant->status_color ?? 'secondary' }}">{{ $tenant->status }}</span></td>
									<td class="text-end">
										<a href="{{ route('platform.tenants.show', $tenant) }}" class="btn btn-sm btn-info">{{ __('tenants.view') }}</a>
									</td>
								</tr>
								@empty
								<tr>
									<td colspan="4" class="text-center text-muted py-4">{{ __('tenants.empty_table') }}</td>
								</tr>
								@endforelse
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-4 mb-4">
			<div class="card shadow h-100">
				<div class="card-header">
					<h5 class="mb-0">{{ __('platform.dashboard.by_plan') }}</h5>
				</div>
				<div class="card-body">
					@forelse($byPlan as $row)
						<div class="d-flex justify-content-between mb-2">
							<span>{{ $row->plan->name ?? __('tenants.no_plan') }}</span>
							<strong>{{ $row->total }}</strong>
						</div>
					@empty
						<p class="text-muted mb-0">{{ __('platform.dashboard.no_plan_data') }}</p>
					@endforelse
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
