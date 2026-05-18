@extends('layouts.master')
@section('title', 'POS Terminal')
@section('css')
<link href="{{ asset('css/pos/main.css') }}" rel="stylesheet">
@endsection

@section('content')
@php
    $posTerminalConfig = [
        'products' => [],
        'customers' => $customers,
        'categories' => $categories,
        'heldOrders' => $heldOrders->values(),
        'shiftId' => $activeShift?->id,
        'taxRate' => (float) config('app.tax_rate', 0),
        'currencySymbol' => config('app.currency_symbol', '$'),
        'csrfToken' => csrf_token(),
        'tenantName' => $tenantName,
        'cashierName' => $cashierName ?? '',
        'appOrigin' => rtrim((string) config('app.url'), '/'),
        'routes' => [
            'storeSale' => route('pos.sale.store'),
            'quickCustomer' => route('pos.customer.quick-store'),
            'holdOrder' => route('pos.hold'),
            'resumeOrder' => '/pos/held/:id/resume',
            'productsFeed' => route('pos.products.feed'),
            'openShift' => route('pos.shift.open'),
            'closeShift' => '/pos/shift/:id/close',
            'customerDisplay' => route('pos.display'),
            'customerSearch' => route('pos.customers.search'),
            'saleReceipt' => '/pos/sales/:id/receipt',
        ],
    ];
    $posRealtimeEchoConfig = [
        'tenantId' => Auth::user()?->tenant_id,
        'shiftId' => $activeShift?->id,
    ];
@endphp
<script type="application/json" id="pos-realtime-echo-config">@json($posRealtimeEchoConfig)</script>
<script type="application/json" id="pos-terminal-config">@json($posTerminalConfig)</script>
<script>
    window.posRealtimeEchoConfig = JSON.parse(document.getElementById('pos-realtime-echo-config').textContent);
    window.posTerminalConfig = JSON.parse(document.getElementById('pos-terminal-config').textContent);
</script>

<div class="pos-terminal-wrapper pos-shell pos-terminal-shell p-3 flex flex-col min-h-0" x-data x-init="$store.pos.initStore(window.posTerminalConfig)">
    <div
        class="pos-offline-strip"
        x-show="!$store.pos.online || $store.pos.pendingSyncCount > 0"
        x-transition
        role="status"
    >
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <template x-if="!$store.pos.online">
                <span><i class="fe fe-wifi-off mr-1"></i> Offline — sales queue locally until connection returns.</span>
            </template>
            <template x-if="$store.pos.online && $store.pos.pendingSyncCount > 0">
                <span><i class="fe fe-upload-cloud mr-1"></i> Syncing <strong x-text="$store.pos.pendingSyncCount"></strong> pending sale(s)…</span>
            </template>
        </div>
        <span class="pos-sync-pill" x-show="$store.pos.pendingSyncCount > 0">
            Queue · <span x-text="$store.pos.pendingSyncCount"></span>
        </span>
    </div>

    <div id="pos-toast-root" aria-live="polite"></div>

    @include('pos.partials.terminal.topbar', ['activeShift' => $activeShift, 'tenantName' => $tenantName])

    <div class="grid grid-cols-12 gap-3 mt-2 flex-1 min-h-0 pos-terminal-workspace" style="min-height:min(600px,calc(100vh - 170px));">
        <div class="col-span-12 xl:col-span-3 h-full">
            @include('pos.partials.terminal.sidebar', ['activeShift' => $activeShift, 'customers' => $customers])
        </div>
        <div class="col-span-12 xl:col-span-6 h-full">
            @include('pos.partials.terminal.catalog', ['categories' => $categories, 'activeShift' => $activeShift])
        </div>
        <div class="col-span-12 xl:col-span-3 h-full">
            @include('pos.partials.terminal.cart')
        </div>
    </div>

    @include('pos.partials.modals')
</div>
@endsection

@section('js')
@include('pos.partials.echo-scripts')
<script src="{{ asset('js/pos/pos-contracts.js') }}"></script>
<script src="{{ asset('js/pos/pos-runtime.js') }}"></script>
<script src="{{ asset('js/pos/pos-hardware.js') }}"></script>
<script src="{{ asset('js/pos/pos-realtime-echo.js') }}"></script>
<script src="{{ asset('js/pos-terminal.js') }}"></script>
@endsection
