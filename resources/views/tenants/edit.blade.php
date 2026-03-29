@extends('layouts.master')
@section('title', 'Edit Tenant')

@section('page-header')
				<!-- breadcrumb -->
				<div class="breadcrumb-header justify-content-between">
					<div class="left-content">
						<div>
						  <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">Edit Tenant</h2>
						  <p class="mg-b-0">Update organization settings and subscription.</p>
						</div>
					</div>
					<div class="main-dashboard-header-right">
						<a href="{{ route('tenants.index') }}" class="btn btn-secondary">
							<i class="fas fa-arrow-left"></i> Back
						</a>
					</div>
				</div>
				<!-- /breadcrumb -->
@endsection

@section('content')
<div class="container-fluid">

    <div class="card shadow">
        <div class="card-body">
            <form action="{{ route('tenants.update', $tenant) }}" method="POST">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Tenant Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $tenant->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="domain" class="form-label">Domain</label>
                        <input type="text" class="form-control @error('domain') is-invalid @enderror" id="domain" name="domain" value="{{ old('domain', $tenant->domain) }}">
                        @error('domain')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="trial_ends_at" class="form-label">Trial Ends</label>
                        <input type="date" class="form-control @error('trial_ends_at') is-invalid @enderror" id="trial_ends_at" name="trial_ends_at" value="{{ old('trial_ends_at', $tenant->trial_ends_at?->format('Y-m-d')) }}">
                        @error('trial_ends_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="subscription_ends_at" class="form-label">Subscription Ends</label>
                        <input type="date" class="form-control @error('subscription_ends_at') is-invalid @enderror" id="subscription_ends_at" name="subscription_ends_at" value="{{ old('subscription_ends_at', $tenant->subscription_ends_at?->format('Y-m-d')) }}">
                        @error('subscription_ends_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="is_active" class="form-label">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $tenant->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="plan_id" class="form-label">Subscription Plan</label>
                        <select class="form-select @error('plan_id') is-invalid @enderror" id="plan_id" name="plan_id">
                            <option value="">-- No Plan --</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" {{ old('plan_id', $tenant->plan_id) == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} (${{ number_format($plan->price, 2) }})
                                </option>
                            @endforeach
                        </select>
                        @error('plan_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Lifecycle Status</label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                            <option value="trial" {{ old('status', $tenant->status) == 'trial' ? 'selected' : '' }}>Trial</option>
                            <option value="active" {{ old('status', $tenant->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="suspended" {{ old('status', $tenant->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="expired" {{ old('status', $tenant->status) == 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="payment_status" class="form-label">Payment Status</label>
                        <select class="form-select @error('payment_status') is-invalid @enderror" id="payment_status" name="payment_status">
                            <option value="pending" {{ old('payment_status', $tenant->payment_status) == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="paid" {{ old('payment_status', $tenant->payment_status) == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="failed" {{ old('payment_status', $tenant->payment_status) == 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                        @error('payment_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="currency" class="form-label">Currency</label>
                        <input type="text" class="form-control @error('currency') is-invalid @enderror" id="currency" name="currency" value="{{ old('currency', $tenant->currency) }}" maxlength="3">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="language" class="form-label">Language</label>
                        <input type="text" class="form-control @error('language') is-invalid @enderror" id="language" name="language" value="{{ old('language', $tenant->language) }}" maxlength="10">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="timezone" class="form-label">Timezone</label>
                        <input type="text" class="form-control @error('timezone') is-invalid @enderror" id="timezone" name="timezone" value="{{ old('timezone', $tenant->timezone) }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="settings" class="form-label">Metadata Settings (JSON)</label>
                    <textarea class="form-control @error('settings') is-invalid @enderror" id="settings" name="settings" rows="3">{{ old('settings', is_array($tenant->settings) ? json_encode($tenant->settings) : $tenant->settings) }}</textarea>
                    @error('settings')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Tenant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
