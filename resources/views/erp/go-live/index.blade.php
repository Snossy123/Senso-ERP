@extends('layouts.master')
@section('title', 'Go-Live Checklist')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="main-content-title tx-24 mg-b-1">Go-Live Checklist</h2>
            <p class="text-muted mb-0">{{ $tenant->name }} — complete before first production sale.</p>
        </div>
        <div class="text-end">
            <div class="h3 mb-0 {{ $ready ? 'text-success' : 'text-warning' }}">{{ $percent }}%</div>
            <small class="text-muted">{{ $ready ? 'Ready for go-live' : 'Setup in progress' }}</small>
        </div>
    </div>

    @if($ready)
        <div class="alert alert-success">All checklist items are complete. Run a simulation cycle (POS sale, store order, PO receive) before handing off to the client.</div>
    @endif

    <div class="card shadow-sm">
        <div class="list-group list-group-flush">
            @foreach($items as $item)
                <div class="list-group-item d-flex justify-content-between align-items-start py-3">
                    <div class="me-3">
                        <span class="badge {{ $item['done'] ? 'bg-success' : 'bg-secondary' }} me-2">
                            {{ $item['done'] ? 'Done' : 'Pending' }}
                        </span>
                        <strong>{{ $item['label'] }}</strong>
                        @if($item['hint'])
                            <div class="text-muted small mt-1">{{ $item['hint'] }}</div>
                        @endif
                    </div>
                    @if($item['route'] && ! $item['done'])
                        <a href="{{ route($item['route']) }}" class="btn btn-sm btn-outline-primary">Configure</a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
