@extends('layouts.app')
@section('title', 'Edit Tenant')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Tenant</h1>
        <a href="{{ route('tenants.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

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

                <div class="mb-3">
                    <label for="settings" class="form-label">Settings (JSON)</label>
                    <textarea class="form-control @error('settings') is-invalid @enderror" id="settings" name="settings" rows="3">{{ old('settings', json_encode($tenant->settings ?? '{}')) }}</textarea>
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
