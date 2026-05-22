@extends('layouts.platform.master')
@section('title', __('platform.modules.title'))

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">{{ __('platform.modules.title') }}</h2>
        <p class="mg-b-0 text-muted">{{ __('platform.modules.subtitle') }}</p>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card shadow">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('platform.modules.name') }}</th>
                        <th>{{ __('platform.modules.key') }}</th>
                        <th>{{ __('platform.modules.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($modules as $module)
                    <tr>
                        <td><i class="{{ $module->icon }} me-1"></i> {{ $module->name }}</td>
                        <td><code>{{ $module->key }}</code></td>
                        <td>
                            <form action="{{ route('platform.modules.update', $module) }}" method="POST" class="d-inline">@csrf @method('PUT')
                                <input type="hidden" name="is_active" value="0">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($module->is_active) onchange="this.form.submit()">
                                </div>
                            </form>
                        </td>
                        <td class="small text-muted">{{ count($module->limit_schema ?? []) }} {{ __('platform.modules.limit_fields') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
