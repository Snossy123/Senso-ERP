@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">{{ __('shipping.title') }}</h4>
            <span class="text-muted mt-1 tx-13 ml-2 mb-0">/ {{ __('shipping.integration') }}</span>
        </div>
        <p class="text-muted tx-13 mb-0 mt-1">{{ __('shipping.subtitle') }}</p>
    </div>
</div>
@endsection
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('shipping.integration') }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.shipping.update') }}">
                    @csrf
                    <div class="form-group">
                        <label>{{ __('shipping.username') }}</label>
                        <input type="text" name="username" class="form-control" value="{{ old('username', $integration?->username) }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __('shipping.password') }}</label>
                        <input type="password" name="password" class="form-control" autocomplete="new-password">
                        <small class="text-muted">{{ __('shipping.password_keep') }}</small>
                    </div>
                    <div class="form-group">
                        <label>{{ __('shipping.base_url') }}</label>
                        <input type="url" name="base_url" class="form-control" value="{{ old('base_url', $integration?->base_url) }}" placeholder="{{ config('shipping.default_base_url') }}">
                        <small class="text-muted">{{ __('shipping.base_url_hint') }}</small>
                    </div>
                    <div class="form-group">
                        <label>{{ __('shipping.default_weight') }}</label>
                        <input type="number" step="0.001" min="0.001" name="default_weight" class="form-control" value="{{ old('default_weight', $integration?->default_weight ?? 1) }}">
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $integration?->is_active) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">{{ __('shipping.active') }}</label>
                        </div>
                    </div>
                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('settings.edit'))
                    <button type="submit" class="btn btn-primary">{{ __('shipping.save_integration') }}</button>
                    @endif
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('shipping.rates') }}</h5></div>
            <div class="card-body">
                <p class="text-muted tx-13">{{ __('shipping.rates_hint') }}</p>
                @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('settings.edit'))
                <form method="POST" action="{{ route('admin.shipping.rates.store') }}" class="form-row align-items-end mb-4">
                    @csrf
                    <div class="form-group col-md-3 mb-2">
                        <label>{{ __('shipping.city') }}</label>
                        <input type="text" name="city" class="form-control" required>
                    </div>
                    <div class="form-group col-md-3 mb-2">
                        <label>{{ __('shipping.city_label') }}</label>
                        <input type="text" name="city_label" class="form-control">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label>{{ __('shipping.fee') }}</label>
                        <input type="number" step="0.01" min="0" name="fee" class="form-control" value="0" required>
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <div class="custom-control custom-switch mt-4">
                            <input type="checkbox" class="custom-control-input" id="new_rate_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="new_rate_active">{{ __('shipping.status') }}</label>
                        </div>
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary btn-block">{{ __('shipping.add_rate') }}</button>
                    </div>
                </form>
                @endif
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('shipping.city') }}</th>
                                <th>{{ __('shipping.city_label') }}</th>
                                <th>{{ __('shipping.fee') }}</th>
                                <th>{{ __('shipping.status') }}</th>
                                <th>{{ __('shipping.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($rates as $rate)
                            <tr>
                                <td colspan="5" class="p-2">
                                    <form method="POST" action="{{ route('admin.shipping.rates.update', $rate) }}" class="form-row align-items-center">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-md-3 mb-1"><input type="text" name="city" class="form-control form-control-sm" value="{{ $rate->city }}" required></div>
                                        <div class="col-md-3 mb-1"><input type="text" name="city_label" class="form-control form-control-sm" value="{{ $rate->city_label }}"></div>
                                        <div class="col-md-2 mb-1"><input type="number" step="0.01" min="0" name="fee" class="form-control form-control-sm" value="{{ $rate->fee }}" required></div>
                                        <div class="col-md-2 mb-1">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="rate_active_{{ $rate->id }}" name="is_active" value="1" {{ $rate->is_active ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="rate_active_{{ $rate->id }}"></label>
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-1 d-flex">
                                            <button class="btn btn-sm btn-outline-primary mr-1" type="submit">{{ __('shipping.save') }}</button>
                                        </div>
                                    </form>
                                    <form method="POST" action="{{ route('admin.shipping.rates.destroy', $rate) }}" class="d-inline" onsubmit="return confirm('{{ __('shipping.delete') }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('shipping.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">—</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
