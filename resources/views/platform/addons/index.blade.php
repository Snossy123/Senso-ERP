@extends('layouts.platform.master')
@section('title', __('platform.addons.title'))

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <h2 class="main-content-title tx-24 mg-b-1">{{ __('platform.addons.title') }}</h2>
    </div>
    <div class="main-dashboard-header-right">
        <a href="{{ route('platform.addons.create') }}" class="btn btn-primary"><i class="fe fe-plus"></i> {{ __('platform.addons.create') }}</a>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card shadow">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>{{ __('platform.addons.name') }}</th><th>{{ __('platform.addons.price') }}</th><th>{{ __('platform.addons.status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($addons as $addon)
                    <tr>
                        <td>{{ $addon->name }}</td>
                        <td>{{ $addon->formatted_price }} / {{ $addon->billing_cycle }}</td>
                        <td><span class="badge bg-{{ $addon->is_active ? 'success' : 'secondary' }}">{{ $addon->is_active ? __('platform.plans.active') : __('platform.plans.inactive') }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('platform.addons.edit', $addon) }}" class="btn btn-sm btn-outline-primary"><i class="fe fe-edit"></i></a>
                            <form action="{{ route('platform.addons.destroy', $addon) }}" method="POST" class="d-inline">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('?')"><i class="fe fe-trash-2"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">{{ __('platform.addons.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($addons->hasPages())<div class="card-footer">{{ $addons->links() }}</div>@endif
    </div>
</div>
@endsection
