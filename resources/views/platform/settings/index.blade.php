@extends('layouts.platform.master')
@section('title', __('platform.settings.title'))

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">{{ __('platform.settings.title') }}</h2>
        <p class="mg-b-0 text-muted">{{ __('platform.settings.subtitle') }}</p>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="{{ route('platform.settings.update') }}">
                @csrf @method('PATCH')
                <div class="mb-3">
                    <label class="form-label">{{ __('platform.settings.platform_name') }}</label>
                    <input type="text" name="platform_name" class="form-control" value="{{ old('platform_name', $settings['platform_name'] ?? '') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('platform.settings.support_email') }}</label>
                    <input type="email" name="support_email" class="form-control" value="{{ old('support_email', $settings['support_email'] ?? '') }}" required>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('platform.settings.default_currency') }}</label>
                        <input type="text" name="default_currency" class="form-control" maxlength="3" value="{{ old('default_currency', $settings['default_currency'] ?? 'USD') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('platform.settings.trial_days') }}</label>
                        <input type="number" name="default_trial_days" class="form-control" value="{{ old('default_trial_days', $settings['default_trial_days'] ?? 14) }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('platform.settings.invoice_prefix') }}</label>
                        <input type="text" name="invoice_prefix" class="form-control" value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV-') }}">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('platform.plans.save') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
