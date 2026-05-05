@extends('layouts.master')
@section('title', $tenant->name)

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="left-content">
            <div>
                <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">{{ $tenant->name }}</h2>
                <p class="mg-b-0">{{ __('tenants.show_subtitle') }}</p>
            </div>
        </div>
        <div class="main-dashboard-header-right">
            <div class="btn-group" role="group">
                @if($tenant->status !== 'suspended')
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#suspendModal">
                        <i class="fas fa-pause"></i> {{ __('tenants.suspend') }}
                    </button>
                @else
                    <form action="{{ route('tenants.activate', $tenant) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-play"></i> {{ __('tenants.activate') }}
                        </button>
                    </form>
                @endif
                
                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#loginAsModal">
                    <i class="fas fa-sign-in-alt"></i> {{ __('tenants.login_as') }}
                </button>
                
                <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> {{ __('tenants.edit') }}
                </a>
                
                <form action="{{ route('tenants.sync-usage', $tenant) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sync"></i> {{ __('tenants.sync_usage') }}
                    </button>
                </form>
            </div>
            <a href="{{ route('tenants.index') }}" class="btn btn-secondary mr-2">
                <i class="fas fa-arrow-left"></i> {{ __('tenants.back') }}
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="container-fluid">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('tenant_support_password'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>{{ __('tenants.support_password_alert_title') }}</strong>
            <p class="mb-2 small">{{ __('tenants.support_password_alert_body') }}</p>
            <code class="user-select-all d-block p-2 bg-light rounded border">{{ session('tenant_support_password') }}</code>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Status & Plan Cards -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card shadow h-100 border-start border-4 border-{{ $tenant->status_color }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0">{{ __('tenants.status_label') }}</p>
                            <h4 class="mb-0">
                                <span class="badge bg-{{ $tenant->status_color }}">{{ $tenant->status_label }}</span>
                            </h4>
                        </div>
                        <div class="text-{{ $tenant->status_color }}">
                            <i class="fas fa-circle fa-2x"></i>
                        </div>
                    </div>
                    
                    @if($tenant->isOnTrial() && $daysUntilTrial !== null)
                        <div class="mt-3">
                            <small class="text-warning">
                                <i class="fas fa-clock"></i> {{ __('tenants.trial_ends_in', ['days' => $daysUntilTrial]) }}
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0">{{ __('tenants.current_plan') }}</p>
                            <h4 class="mb-0">{{ $tenant->plan->name ?? __('tenants.no_plan') }}</h4>
                            <small class="text-muted">{{ $tenant->plan ? '$' . number_format($tenant->plan->price, 2) . '/' . $tenant->plan->billing_cycle : '-' }}</small>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-cube fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#upgradePlanModal">
                            <i class="fas fa-arrow-up"></i> {{ __('tenants.change_plan') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0">Billing</p>
                            <h4 class="mb-0">
                                <span class="badge bg-{{ $tenant->payment_status === 'paid' ? 'success' : ($tenant->payment_status === 'failed' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($tenant->payment_status) }}
                                </span>
                            </h4>
                            @if($tenant->next_billing_at)
                                <small class="text-muted">Next: {{ $tenant->next_billing_at->format('M d, Y') }}</small>
                            @endif
                        </div>
                        <div class="text-success">
                            <i class="fas fa-credit-card fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0">Users</p>
                            <h4 class="mb-0">{{ $tenant->users()->count() }}</h4>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                    @if(isset($usage['users']))
                        <div class="mt-2">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-{{ $usage['users']['percentage'] > 90 ? 'danger' : ($usage['users']['percentage'] > 70 ? 'warning' : 'info') }}" 
                                     role="progressbar" 
                                     style="width: {{ $usage['users']['percentage'] }}%">
                                </div>
                            </div>
                            <small class="text-muted">{{ $usage['users']['current'] }} / {{ $usage['users']['limit'] }} ({{ $usage['users']['percentage'] }}%)</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Progress Cards -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Usage Overview</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Users Usage -->
                        <div class="col-md-4 mb-3">
                            <div class="usage-card p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><i class="fas fa-users text-info me-2"></i>Users</h6>
                                    <span class="badge bg-{{ isset($usage['users']) && $usage['users']['at_limit'] ? 'danger' : 'success' }}">
                                        {{ isset($usage['users']) ? $usage['users']['remaining'] : 0 }} remaining
                                    </span>
                                </div>
                                @if(isset($usage['users']))
                                    <div class="progress mb-2" style="height: 20px;">
                                        <div class="progress-bar bg-{{ $usage['users']['percentage'] > 90 ? 'danger' : ($usage['users']['percentage'] > 70 ? 'warning' : 'info') }}" 
                                             role="progressbar" 
                                             style="width: {{ $usage['users']['percentage'] }}%">
                                            {{ $usage['users']['percentage'] }}%
                                        </div>
                                    </div>
                                    <small class="text-muted">{{ $usage['users']['current'] }} of {{ $usage['users']['limit'] }} used</small>
                                @else
                                    <p class="text-muted mb-0">No usage data</p>
                                @endif
                            </div>
                        </div>

                        <!-- Products Usage -->
                        <div class="col-md-4 mb-3">
                            <div class="usage-card p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><i class="fas fa-box text-success me-2"></i>Products</h6>
                                    <span class="badge bg-{{ isset($usage['products']) && $usage['products']['at_limit'] ? 'danger' : 'success' }}">
                                        {{ isset($usage['products']) ? $usage['products']['remaining'] : 0 }} remaining
                                    </span>
                                </div>
                                @if(isset($usage['products']))
                                    <div class="progress mb-2" style="height: 20px;">
                                        <div class="progress-bar bg-{{ $usage['products']['percentage'] > 90 ? 'danger' : ($usage['products']['percentage'] > 70 ? 'warning' : 'success') }}" 
                                             role="progressbar" 
                                             style="width: {{ $usage['products']['percentage'] }}%">
                                            {{ $usage['products']['percentage'] }}%
                                        </div>
                                    </div>
                                    <small class="text-muted">{{ $usage['products']['current'] }} of {{ $usage['products']['limit'] }} used</small>
                                @else
                                    <p class="text-muted mb-0">No usage data</p>
                                @endif
                            </div>
                        </div>

                        <!-- Orders Usage -->
                        <div class="col-md-4 mb-3">
                            <div class="usage-card p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><i class="fas fa-shopping-cart text-warning me-2"></i>Orders/Month</h6>
                                    <span class="badge bg-{{ isset($usage['orders']) && $usage['orders']['at_limit'] ? 'danger' : 'success' }}">
                                        {{ isset($usage['orders']) ? $usage['orders']['remaining'] : 0 }} remaining
                                    </span>
                                </div>
                                @if(isset($usage['orders']))
                                    <div class="progress mb-2" style="height: 20px;">
                                        <div class="progress-bar bg-{{ $usage['orders']['percentage'] > 90 ? 'danger' : ($usage['orders']['percentage'] > 70 ? 'warning' : 'warning') }}" 
                                             role="progressbar" 
                                             style="width: {{ $usage['orders']['percentage'] }}%">
                                            {{ $usage['orders']['percentage'] }}%
                                        </div>
                                    </div>
                                    <small class="text-muted">{{ $usage['orders']['current'] }} of {{ $usage['orders']['limit'] }} used this month</small>
                                @else
                                    <p class="text-muted mb-0">No usage data</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details & Settings Row -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Subscription Details</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <th>Plan:</th>
                            <td>{{ $tenant->plan->name ?? 'None' }}</td>
                        </tr>
                        <tr>
                            <th>Price:</th>
                            <td>{{ $tenant->price ? '$' . number_format($tenant->price, 2) : 'Free' }} / {{ $tenant->billing_cycle }}</td>
                        </tr>
                        <tr>
                            <th>Start Date:</th>
                            <td>{{ $tenant->subscription_start_at?->format('M d, Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>End Date:</th>
                            <td>{{ $tenant->subscription_ends_at?->format('M d, Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Trial Ends:</th>
                            <td>{{ $tenant->trial_ends_at?->format('M d, Y') ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Regional Settings</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <th>Currency:</th>
                            <td>{{ $tenant->currency }}</td>
                        </tr>
                        <tr>
                            <th>Language:</th>
                            <td>{{ strtoupper($tenant->language) }}</td>
                        </tr>
                        <tr>
                            <th>Timezone:</th>
                            <td>{{ $tenant->timezone }}</td>
                        </tr>
                        @if($tenant->tax_settings)
                            <tr>
                                <th>Tax Number:</th>
                                <td>{{ $tenant->tax_settings['tax_number'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Tax Rate:</th>
                                <td>{{ $tenant->tax_settings['tax_rate'] ?? 0 }}%</td>
                            </tr>
                        @endif
                    </table>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#settingsModal">
                        <i class="fas fa-edit"></i> Edit Settings
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Stats</h6>
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
                                <h3 class="text-warning">{{ $tenant->orders()->count() }}</h3>
                                <small class="text-muted">Orders</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Users -->
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
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenant->users()->latest()->take(10)->get() as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->role?->name ?? 'No Role' }}</td>
                                <td>
                                    @if($user->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Upgrade Plan Modal -->
<div class="modal fade" id="upgradePlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('tenants.upgrade-plan', $tenant) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        @php $plans = \App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get(); @endphp
                        @foreach($plans as $plan)
                            <div class="col-md-6 mb-3">
                                <div class="card border-{{ $tenant->plan_id === $plan->id ? 'primary' : 'secondary' }}">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="plan_id" 
                                                   id="plan_{{ $plan->id }}" value="{{ $plan->id }}"
                                                   {{ $tenant->plan_id === $plan->id ? 'checked' : '' }}>
                                            <label class="form-check-label" for="plan_{{ $plan->id }}">
                                                <strong>{{ $plan->name }}</strong>
                                                <span class="badge bg-{{ $plan->is_featured ? 'warning' : 'secondary' }} float-end">
                                                    ${{ number_format($plan->price, 2) }}/{{ $plan->billing_cycle }}
                                                </span>
                                            </label>
                                        </div>
                                        <hr>
                                        <small class="text-muted">
                                            <i class="fas fa-users"></i> {{ $plan->max_users }} users<br>
                                            <i class="fas fa-box"></i> {{ $plan->max_products }} products<br>
                                            <i class="fas fa-shopping-cart"></i> {{ $plan->max_orders_per_month }} orders/mo
                                        </small>
                                        @if($plan->features)
                                            <div class="mt-2">
                                                @foreach($plan->features as $feature)
                                                    <span class="badge bg-light text-dark">{{ $feature }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Suspend Modal -->
<div class="modal fade" id="suspendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Suspend Tenant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('tenants.suspend', $tenant) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to suspend <strong>{{ $tenant->name }}</strong>?</p>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason (optional)</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Enter reason for suspension..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Suspend Tenant</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Login As Modal -->
<div class="modal fade" id="loginAsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Login as {{ $tenant->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('tenants.login-as', $tenant) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>You will be logged in as the first user of this tenant. You can also select a specific user:</p>
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Select User (optional)</label>
                        <select class="form-select" id="user_id" name="user_id">
                            <option value="">-- First available user --</option>
                            @foreach($tenant->users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        This will log you in as a tenant user. You'll need to log out and log back in as admin to return.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Login as Tenant</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Regional Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('tenants.settings', $tenant) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="currency" class="form-label">Currency</label>
                        <select class="form-select" id="currency" name="currency">
                            <option value="USD" {{ $tenant->currency === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                            <option value="EUR" {{ $tenant->currency === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                            <option value="GBP" {{ $tenant->currency === 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                            <option value="JPY" {{ $tenant->currency === 'JPY' ? 'selected' : '' }}>JPY - Japanese Yen</option>
                            <option value="CNY" {{ $tenant->currency === 'CNY' ? 'selected' : '' }}>CNY - Chinese Yuan</option>
                            <option value="INR" {{ $tenant->currency === 'INR' ? 'selected' : '' }}>INR - Indian Rupee</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="language" class="form-label">Language</label>
                        <select class="form-select" id="language" name="language">
                            <option value="en" {{ $tenant->language === 'en' ? 'selected' : '' }}>English</option>
                            <option value="es" {{ $tenant->language === 'es' ? 'selected' : '' }}>Spanish</option>
                            <option value="fr" {{ $tenant->language === 'fr' ? 'selected' : '' }}>French</option>
                            <option value="de" {{ $tenant->language === 'de' ? 'selected' : '' }}>German</option>
                            <option value="zh" {{ $tenant->language === 'zh' ? 'selected' : '' }}>Chinese</option>
                            <option value="ja" {{ $tenant->language === 'ja' ? 'selected' : '' }}>Japanese</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="timezone" class="form-label">Timezone</label>
                        <select class="form-select" id="timezone" name="timezone">
                            <option value="UTC" {{ $tenant->timezone === 'UTC' ? 'selected' : '' }}>UTC</option>
                            <option value="America/New_York" {{ $tenant->timezone === 'America/New_York' ? 'selected' : '' }}>Eastern Time (US)</option>
                            <option value="America/Chicago" {{ $tenant->timezone === 'America/Chicago' ? 'selected' : '' }}>Central Time (US)</option>
                            <option value="America/Denver" {{ $tenant->timezone === 'America/Denver' ? 'selected' : '' }}>Mountain Time (US)</option>
                            <option value="America/Los_Angeles" {{ $tenant->timezone === 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time (US)</option>
                            <option value="Europe/London" {{ $tenant->timezone === 'Europe/London' ? 'selected' : '' }}>London</option>
                            <option value="Europe/Paris" {{ $tenant->timezone === 'Europe/Paris' ? 'selected' : '' }}>Paris</option>
                            <option value="Asia/Tokyo" {{ $tenant->timezone === 'Asia/Tokyo' ? 'selected' : '' }}>Tokyo</option>
                            <option value="Asia/Shanghai" {{ $tenant->timezone === 'Asia/Shanghai' ? 'selected' : '' }}>Shanghai</option>
                        </select>
                    </div>
                    <hr>
                    <h6>Tax Settings</h6>
                    <div class="mb-3">
                        <label for="tax_number" class="form-label">Tax Number/VAT</label>
                        <input type="text" class="form-control" id="tax_number" name="tax_settings[tax_number]" 
                               value="{{ $tenant->tax_settings['tax_number'] ?? '' }}">
                    </div>
                    <div class="mb-3">
                        <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                        <input type="number" step="0.01" class="form-control" id="tax_rate" name="tax_settings[tax_rate]" 
                               value="{{ $tenant->tax_settings['tax_rate'] ?? 0 }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
