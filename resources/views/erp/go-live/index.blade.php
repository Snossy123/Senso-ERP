@extends('layouts.master')
@section('title', __('go_live.title'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="main-content-title tx-24 mg-b-1">{{ __('go_live.title') }}</h2>
            <p class="text-muted mb-0">{{ $tenant->name }} — {{ __('go_live.subtitle') }}</p>
        </div>
        <div class="text-end">
            <div class="h3 mb-0 {{ $ready ? 'text-success' : 'text-warning' }}">{{ $percent }}%</div>
            <small class="text-muted">{{ $ready ? __('go_live.ready') : __('go_live.in_progress') }}</small>
        </div>
    </div>

    @if($ready)
        <div class="alert alert-success">{{ __('go_live.ready_alert') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="list-group list-group-flush">
            @foreach($items as $item)
                <div class="list-group-item d-flex justify-content-between align-items-start py-3">
                    <div class="me-3">
                        <span class="badge {{ $item['done'] ? 'bg-success' : 'bg-secondary' }} me-2">
                            {{ $item['done'] ? __('go_live.status_done') : __('go_live.status_pending') }}
                        </span>
                        <strong>{{ $item['label'] }}</strong>
                        @if($item['hint'])
                            <div class="text-muted small mt-1">{{ $item['hint'] }}</div>
                        @endif
                    </div>
                    @if($item['route'] && ! $item['done'])
                        <a href="{{ route($item['route']) }}" class="btn btn-sm btn-outline-primary">{{ __('go_live.configure') }}</a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
