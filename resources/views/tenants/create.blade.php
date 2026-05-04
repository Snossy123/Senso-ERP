@extends('layouts.master')
@section('title', __('tenants.create_title'))

@section('page-header')
				<div class="breadcrumb-header justify-content-between">
					<div class="left-content">
						<div>
						  <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">{{ __('tenants.create_title') }}</h2>
						  <p class="mg-b-0">{{ __('tenants.create_subtitle') }}</p>
						</div>
					</div>
					<div class="main-dashboard-header-right">
						<a href="{{ route('tenants.index') }}" class="btn btn-secondary">
							<i class="fas fa-arrow-left"></i> {{ __('tenants.back') }}
						</a>
					</div>
				</div>
@endsection

@section('content')
<div class="container-fluid">

    <div class="card shadow">
        <div class="card-body">
            <form action="{{ route('tenants.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">{{ __('tenants.field_name') }} *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="domain" class="form-label">{{ __('tenants.field_domain') }}</label>
                        <input type="text" class="form-control @error('domain') is-invalid @enderror" id="domain" name="domain" value="{{ old('domain') }}" placeholder="tenant.example.com">
                        @error('domain')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="plan_id" class="form-label">{{ __('tenants.initial_plan') }}</label>
                        <select class="form-select @error('plan_id') is-invalid @enderror" id="plan_id" name="plan_id">
                            <option value="">{{ __('tenants.no_plan_option') }}</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} (${{ number_format($plan->price, 2) }})
                                </option>
                            @endforeach
                        </select>
                        @error('plan_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="trial_days" class="form-label">{{ __('tenants.trial_period_days') }}</label>
                        <input type="number" class="form-control @error('trial_days') is-invalid @enderror" id="trial_days" name="trial_days" value="{{ old('trial_days', 14) }}" min="0" max="60">
                        @error('trial_days')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="currency" class="form-label">{{ __('tenants.field_currency') }}</label>
                        <input type="text" class="form-control @error('currency') is-invalid @enderror" id="currency" name="currency" value="{{ old('currency', 'USD') }}" maxlength="3">
                        @error('currency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="language" class="form-label">{{ __('tenants.language') }}</label>
                        <select class="form-select @error('language') is-invalid @enderror" id="language" name="language">
                            @foreach(config('locales.supported', []) as $code => $meta)
                                <option value="{{ $code }}" {{ old('language', 'en') == $code ? 'selected' : '' }}>{{ $meta['native'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="timezone" class="form-label">{{ __('tenants.field_timezone') }}</label>
                        <select class="form-select @error('timezone') is-invalid @enderror" id="timezone" name="timezone">
                            <option value="UTC" {{ old('timezone') == 'UTC' ? 'selected' : '' }}>UTC</option>
                            <option value="America/New_York" {{ old('timezone') == 'America/New_York' ? 'selected' : '' }}>EST/EDT</option>
                            <option value="Asia/Tokyo" {{ old('timezone') == 'Asia/Tokyo' ? 'selected' : '' }}>JST</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="settings" class="form-label">Initial Settings (Optional Metadata)</label>
                    <textarea class="form-control @error('settings') is-invalid @enderror" id="settings" name="settings" rows="2" placeholder='{"key": "value"}'>{{ old('settings') }}</textarea>
                    <small class="text-muted">Enter valid JSON or leave blank.</small>
                    @error('settings')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> {{ __('tenants.create_submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
