@extends('layouts.platform.master')
@php
    $isEdit = $plan->exists;
    $action = $isEdit ? route('platform.plans.update', $plan) : route('platform.plans.store');
    $moduleSchemas = config('platform_modules.modules', []);
@endphp
@section('title', $isEdit ? __('platform.plans.edit_title') : __('platform.plans.create_title'))

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <div>
            <h2 class="main-content-title tx-24 mg-b-1">{{ $isEdit ? __('platform.plans.edit_title') : __('platform.plans.create_title') }}</h2>
            <p class="mg-b-0 text-muted">{{ __('platform.plans.wizard_subtitle') }}</p>
        </div>
    </div>
    <div class="main-dashboard-header-right">
        <a href="{{ route('platform.plans.index') }}" class="btn btn-secondary">{{ __('platform.plans.cancel') }}</a>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid" id="plan-wizard" data-module-schemas='@json($moduleSchemas)'>
    <form action="{{ $action }}" method="POST" id="plan-form">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <ul class="nav nav-pills nav-justified wizard-steps mb-4" role="tablist">
            <li class="nav-item"><button type="button" class="nav-link active" data-step="1">{{ __('platform.plans.step_basic') }}</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-step="2">{{ __('platform.plans.step_modules') }}</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-step="3">{{ __('platform.plans.step_review') }}</button></li>
        </ul>

        <div class="wizard-panel" data-panel="1">
            <div class="card shadow">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('platform.plans.field_name') }}</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $plan->name) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('platform.plans.field_price') }}</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $plan->price) }}" required>
                                <select name="currency" class="form-select" style="max-width:100px">
                                    @foreach(['USD','EUR','SAR'] as $c)
                                    <option value="{{ $c }}" @selected(old('currency', $plan->currency ?? 'USD') === $c)>{{ $c }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">{{ __('platform.plans.field_description') }}</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description', $plan->description) }}</textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('platform.plans.billing') }}</label>
                            <select name="billing_cycle" class="form-select">
                                <option value="monthly" @selected(old('billing_cycle', $plan->billing_cycle) === 'monthly')>{{ __('platform.plans.cycle_monthly') }}</option>
                                <option value="yearly" @selected(old('billing_cycle', $plan->billing_cycle) === 'yearly')>{{ __('platform.plans.cycle_yearly') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('platform.plans.field_trial_end') }}</label>
                            <input type="date" name="trial_ends_at" class="form-control" value="{{ old('trial_ends_at', $plan->trial_ends_at?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $plan->is_active ?? true))>
                                <label class="form-check-label" for="is_active">{{ __('platform.plans.field_activate') }}</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('platform.plans.users') }}</label>
                            <input type="number" name="max_users" class="form-control" value="{{ old('max_users', $plan->max_users ?? 5) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('platform.plans.products') }}</label>
                            <input type="number" name="max_products" class="form-control" value="{{ old('max_products', $plan->max_products ?? 100) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('platform.plans.orders') }}</label>
                            <input type="number" name="max_orders_per_month" class="form-control" value="{{ old('max_orders_per_month', $plan->max_orders_per_month ?? 100) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wizard-panel d-none" data-panel="2">
            <div class="card shadow">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="plan-modules-table">
                            <thead>
                                <tr>
                                    <th>{{ __('platform.plans.module_name') }}</th>
                                    <th>{{ __('platform.plans.module_status') }}</th>
                                    <th>{{ __('platform.plans.module_limits') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modules as $module)
                                @php
                                    $pm = $planModules[$module->key] ?? null;
                                    $enabled = old("modules.{$module->key}.enabled", $pm?->enabled ?? false);
                                    $limits = old("modules.{$module->key}.limits", $pm?->limits ?? $module->defaultLimits());
                                @endphp
                                <tr data-module-key="{{ $module->key }}">
                                    <td><i class="{{ $module->icon }} me-1"></i> {{ $module->name }}</td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="modules[{{ $module->key }}][enabled]" value="0">
                                            <input class="form-check-input module-toggle" type="checkbox" name="modules[{{ $module->key }}][enabled]" value="1" @checked($enabled)>
                                        </div>
                                    </td>
                                    <td class="limits-summary small text-muted">{{ $pm?->limitsSummary() ?? '—' }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-limits-btn" data-module="{{ $module->key }}" data-module-name="{{ $module->name }}" @disabled(!$enabled)>
                                            <i class="fe fe-edit"></i>
                                        </button>
                                        <input type="hidden" name="modules[{{ $module->key }}][limits]" class="limits-json" value="{{ json_encode($limits) }}">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="wizard-panel d-none" data-panel="3">
            <div class="card shadow">
                <div class="card-body" id="review-summary">
                    <p class="text-muted">{{ __('platform.plans.review_hint') }}</p>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between mt-3">
            <button type="button" class="btn btn-secondary" id="wizard-prev" disabled>{{ __('platform.plans.prev') }}</button>
            <div>
                <button type="button" class="btn btn-primary" id="wizard-next">{{ __('platform.plans.next') }}</button>
                <button type="submit" class="btn btn-success d-none" id="wizard-submit">{{ __('platform.plans.publish') }}</button>
            </div>
        </div>
    </form>
</div>

@include('platform.plans.partials.module-limits-modal')
@endsection

@section('js')
<script src="{{ asset('js/platform-plan-wizard.js') }}"></script>
@endsection
