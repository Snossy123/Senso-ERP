@extends('layouts.platform.master')
@section('title', __('platform.subscriptions.title'))

@section('page-header')
<div class="breadcrumb-header justify-content-between">
	<div class="left-content">
		<div>
			<nav aria-label="breadcrumb">
				<ol class="breadcrumb breadcrumb-style2 mb-1">
					<li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}">{{ __('platform.sidebar.dashboard') }}</a></li>
					<li class="breadcrumb-item active">{{ __('platform.subscriptions.title') }}</li>
				</ol>
			</nav>
			<h2 class="main-content-title tx-24 mg-b-1">{{ __('platform.subscriptions.title') }}</h2>
			<p class="mg-b-0 text-muted">{{ __('platform.subscriptions.subtitle') }}</p>
		</div>
		</div>
	</div>
	<div class="main-dashboard-header-right">
		<a href="{{ route('platform.plans.create') }}" class="btn btn-primary">
			<i class="fe fe-plus"></i> {{ __('platform.subscriptions.create_plan') }}
		</a>
	</div>
		</div>
	</div>
@endsection

@section('content')
<div class="container-fluid">
	@if(session('success'))
		<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
	@endif
	@if(session('error'))
		<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
	@endif

	<div class="row">
		<div class="col-md-3 col-6 mb-3">
			<div class="card shadow h-100">
				<div class="card-body">
					<p class="text-muted mb-1 small">{{ __('platform.subscriptions.kpi_total_tenants') }}</p>
					<h3 class="mb-0">{{ $kpis['total_tenants'] }}</h3>
					@if($kpis['tenants_this_month'] > 0)
						<small class="text-success"><i class="fe fe-arrow-up"></i> +{{ $kpis['tenants_this_month'] }} {{ __('platform.subscriptions.this_month') }}</small>
					@endif
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 mb-3">
			<div class="card shadow h-100 border-start border-4 border-primary">
				<div class="card-body">
					<p class="text-muted mb-1 small">{{ __('platform.subscriptions.kpi_monthly_revenue') }}</p>
					<h3 class="mb-0">${{ number_format($kpis['monthly_revenue'], 2) }}</h3>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 mb-3">
			<div class="card shadow h-100">
				<div class="card-body">
					<p class="text-muted mb-1 small">{{ __('platform.subscriptions.kpi_active_plans') }}</p>
					<h3 class="mb-0">{{ $kpis['active_plans'] }} <small class="text-muted fs-6">/ {{ $kpis['total_plans'] }}</small></h3>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 mb-3">
			<div class="card shadow h-100">
				<div class="card-body">
					<p class="text-muted mb-1 small">{{ __('platform.subscriptions.kpi_active_subscriptions') }}</p>
					<h3 class="mb-0">{{ $kpis['active_subscriptions'] }} <small class="text-muted fs-6">/ {{ $kpis['total_tenants_for_subs'] }}</small></h3>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-8 mb-4">
			<div class="card shadow">
				<div class="card-header">
					<h5 class="mb-0">{{ __('platform.subscriptions.latest_subscriptions') }}</h5>
				</div>
				<div class="card-body p-0">
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead>
								<tr>
									<th>{{ __('platform.subscriptions.col_tenant') }}</th>
									<th>{{ __('platform.subscriptions.col_plan') }}</th>
									<th>{{ __('platform.subscriptions.col_status') }}</th>
									<th>{{ __('platform.subscriptions.col_renewal') }}</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								@forelse($latestSubscriptions as $tenant)
								@php $badge = $tenant->subscription_badge; @endphp
								<tr>
									<td>
										<strong>{{ $tenant->name }}</strong>
										@if($tenant->domain)
											<br><small class="text-muted">{{ $tenant->domain }}</small>
										@endif
									</td>
									<td>{{ $tenant->plan->name ?? '—' }}</td>
									<td>
										<span class="badge bg-{{ match($badge) { 'active' => 'success', 'expiring_soon' => 'warning', 'overdue' => 'danger', default => 'secondary' } }}">
											{{ __('platform.subscriptions.status_'.$badge) }}
										</span>
									</td>
									<td>{{ $tenant->subscription_ends_at?->format('Y-m-d') ?? '—' }}</td>
									<td class="text-end">
										<a href="{{ route('platform.tenants.show', $tenant) }}" class="btn btn-sm btn-outline-primary">{{ __('platform.subscriptions.view') }}</a>
									</td>
								</tr>
								@empty
								<tr><td colspan="5" class="text-center text-muted py-4">{{ __('platform.subscriptions.no_subscriptions') }}</td></tr>
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
					<h5 class="mb-0">{{ __('platform.subscriptions.module_usage') }}</h5>
				</div>
				<div class="card-body">
					<canvas id="moduleUsageChart" height="220"></canvas>
					<ul class="list-unstyled mt-3 mb-0 small">
						@foreach($moduleChart['modules'] as $i => $mod)
						<li class="d-flex justify-content-between py-1">
							<span><i class="{{ $mod->icon }} me-1"></i> {{ $mod->name }}</span>
							<span class="text-muted">{{ $moduleChart['percentages'][$i] ?? 0 }}%</span>
						</li>
						@endforeach
					</ul>
					<p class="text-center text-muted small mt-2 mb-0">{{ __('platform.subscriptions.total_modules') }}: {{ $moduleChart['total'] }}</p>
				</div>
			</div>
		</div>
	</div>

	<div class="card shadow">
		<div class="card-header">
			<h5 class="mb-0">{{ __('platform.plans.catalog') }}</h5>
		</div>
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-hover mb-0">
					<thead>
						<tr>
							<th>{{ __('platform.plans.name') }}</th>
							<th>{{ __('platform.plans.price') }}</th>
							<th>{{ __('platform.plans.billing') }}</th>
							<th>{{ __('platform.plans.tenants') }}</th>
							<th>{{ __('platform.plans.status') }}</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						@foreach($plans as $plan)
						<tr>
							<td>
								<strong>{{ $plan->name }}</strong>
								@if($plan->description)<br><small class="text-muted">{{ \Illuminate\Support\Str::limit($plan->description, 60) }}</small>@endif
							</td>
							<td>{{ $plan->formatted_price }}</td>
							<td>{{ __('platform.plans.cycle_'.$plan->billing_cycle) }}</td>
							<td>{{ $plan->tenants_count }}</td>
							<td>
								<span class="badge bg-{{ $plan->is_active ? 'success' : 'secondary' }}">
									{{ $plan->is_active ? __('platform.plans.active') : __('platform.plans.inactive') }}
								</span>
							</td>
							<td class="text-end">
								<a href="{{ route('platform.plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary"><i class="fe fe-edit"></i></a>
								@if(!$plan->tenants_count)
								<form action="{{ route('platform.plans.destroy', $plan) }}" method="POST" class="d-inline" onsubmit="return confirm(@json(__('platform.plans.confirm_delete')))">
									@csrf @method('DELETE')
									<button type="submit" class="btn btn-sm btn-outline-danger"><i class="fe fe-trash-2"></i></button>
								</form>
								@endif
							</td>
						</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
@endsection

@section('js')
<script src="{{ URL::asset('assets/plugins/chart.js/Chart.bundle.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	const ctx = document.getElementById('moduleUsageChart');
	if (!ctx) return;
	new Chart(ctx, {
		type: 'doughnut',
		data: {
			labels: @json($moduleChart['labels']),
			datasets: [{
				data: @json($moduleChart['data']),
				backgroundColor: ['#6259ca','#53caed','#38cb89','#f7b731','#fc544b','#6610f2','#e83e8c','#20c997']
			}]
		},
		options: {
			maintainAspectRatio: false,
			legend: { display: false },
			cutoutPercentage: 70
		}
	});
});
</script>
@endsection
