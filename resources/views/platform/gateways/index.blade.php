@extends('layouts.platform.master')
@section('title', __('platform.gateways.title'))

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">{{ __('platform.gateways.title') }}</h2>
    </div>
    <div class="main-dashboard-header-right">
        <a href="{{ route('platform.gateways.create') }}" class="btn btn-primary"><i class="fe fe-plus"></i> {{ __('platform.gateways.create') }}</a>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card shadow">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>{{ __('platform.gateways.name') }}</th><th>{{ __('platform.gateways.driver') }}</th><th>{{ __('platform.gateways.status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($gateways as $gateway)
                    <tr>
                        <td>{{ $gateway->name }} @if($gateway->is_default)<span class="badge bg-primary ms-1">{{ __('platform.gateways.default') }}</span>@endif</td>
                        <td>{{ $gateway->driver }}</td>
                        <td><span class="badge bg-{{ $gateway->is_active ? 'success' : 'secondary' }}">{{ $gateway->is_active ? __('platform.plans.active') : __('platform.plans.inactive') }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('platform.gateways.edit', $gateway) }}" class="btn btn-sm btn-outline-primary"><i class="fe fe-edit"></i></a>
                            @unless($gateway->is_default)
                            <form action="{{ route('platform.gateways.destroy', $gateway) }}" method="POST" class="d-inline">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fe fe-trash-2"></i></button>
                            </form>
                            @endunless
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">{{ __('platform.gateways.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
