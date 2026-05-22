@extends('layouts.platform.master')
@php $isEdit = $addon->exists; @endphp
@section('title', $isEdit ? __('platform.addons.edit') : __('platform.addons.create'))

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="{{ $isEdit ? route('platform.addons.update', $addon) : route('platform.addons.store') }}">
                @csrf @if($isEdit) @method('PUT') @endif
                <div class="mb-3">
                    <label class="form-label">{{ __('platform.addons.name') }}</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $addon->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('platform.addons.price') }}</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $addon->price) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('platform.plans.billing') }}</label>
                    <select name="billing_cycle" class="form-select">
                        <option value="monthly" @selected(old('billing_cycle', $addon->billing_cycle) === 'monthly')>{{ __('platform.plans.cycle_monthly') }}</option>
                        <option value="yearly" @selected(old('billing_cycle', $addon->billing_cycle) === 'yearly')>{{ __('platform.plans.cycle_yearly') }}</option>
                    </select>
                </div>
                <div class="mb-3">
                    <textarea name="description" class="form-control" rows="3" placeholder="{{ __('platform.plans.field_description') }}">{{ old('description', $addon->description) }}</textarea>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $addon->is_active ?? true))>
                    <label class="form-check-label">{{ __('platform.plans.field_activate') }}</label>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('platform.plans.save') }}</button>
                <a href="{{ route('platform.addons.index') }}" class="btn btn-secondary">{{ __('platform.plans.cancel') }}</a>
            </form>
        </div>
    </div>
</div>
@endsection
