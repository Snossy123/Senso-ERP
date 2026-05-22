@extends('layouts.platform.master')
@php $isEdit = $gateway->exists; @endphp
@section('title', $isEdit ? __('platform.gateways.edit') : __('platform.gateways.create'))

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="{{ $isEdit ? route('platform.gateways.update', $gateway) : route('platform.gateways.store') }}">
                @csrf @if($isEdit) @method('PUT') @endif
                <div class="mb-3">
                    <label class="form-label">{{ __('platform.gateways.name') }}</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $gateway->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('platform.gateways.driver') }}</label>
                    <select name="driver" class="form-select">
                        @foreach($drivers as $driver)
                        <option value="{{ $driver }}" @selected(old('driver', $gateway->driver) === $driver)>{{ $driver }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $gateway->is_active ?? true))>
                    <label class="form-check-label">{{ __('platform.plans.field_activate') }}</label>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="is_default" value="1" class="form-check-input" @checked(old('is_default', $gateway->is_default ?? false))>
                    <label class="form-check-label">{{ __('platform.gateways.default') }}</label>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('platform.plans.save') }}</button>
                <a href="{{ route('platform.gateways.index') }}" class="btn btn-secondary">{{ __('platform.plans.cancel') }}</a>
            </form>
        </div>
    </div>
</div>
@endsection
