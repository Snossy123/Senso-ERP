@extends('layouts.pos-app')

@section('title', 'Register')

@section('content')
<script>
    window.posRealtimeEchoConfig = {
        tenantId: @json(Auth::user()?->tenant_id),
        shiftId: @json($activeShift?->id),
    };
</script>
<script>
    window.posTerminalConfig = {
        products: [],
        customers: @json($customers),
        categories: @json($categories),
        heldOrders: @json($heldOrders->values()),
        shiftId: {{ $activeShift?->id ?? 'null' }},
        taxRate: {{ config('app.tax_rate', 0) }},
        currencySymbol: '{{ config('app.currency_symbol', '$') }}',
        csrfToken: '{{ csrf_token() }}',
        tenantName: @json($tenantName),
        cashierName: @json($cashierName ?? ''),
        appOrigin: @json(rtrim((string) config('app.url'), '/')),
        routes: {
            storeSale: '{{ route('pos.sale.store') }}',
            quickCustomer: '{{ route('pos.customer.quick-store') }}',
            holdOrder: '{{ route('pos.hold') }}',
            resumeOrder: '/pos/held/:id/resume',
            productsFeed: '{{ route('pos.products.feed') }}',
            openShift: '{{ route('pos.shift.open') }}',
            closeShift: '/pos/shift/:id/close',
            customerDisplay: '{{ route('pos.display') }}',
            customerSearch: '{{ route('pos.customers.search') }}',
            saleReceipt: '/pos/sales/:id/receipt'
        }
    };
</script>

<div
    class="pos-app pos-terminal-wrapper flex flex-col min-h-0"
    id="pos-app-root"
    x-data
    x-init="$store.pos.initStore(window.posTerminalConfig)"
>
    <div
        class="pos-offline-strip pos-app-offline-strip"
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

    @include('pos.partials.app.topbar', [
        'activeShift' => $activeShift,
        'tenantName' => $tenantName,
        'cashierName' => $cashierName,
        'customers' => $customers,
    ])

    <button type="button" id="pos-app-rail-backdrop" class="pos-app-rail-backdrop" aria-label="Close categories"></button>

    <div class="pos-app-main">
        @include('pos.partials.app.category-rail', ['categories' => $categories, 'activeShift' => $activeShift])

        <div class="pos-app-catalog-col min-h-0">
            @include('pos.partials.terminal.catalog', ['categories' => $categories, 'activeShift' => $activeShift, 'posAppShell' => true])
        </div>

        <div class="pos-app-cart-col min-h-0">
            @include('pos.partials.terminal.cart', ['posAppShell' => true])
        </div>
    </div>

    <button type="button" id="pos-app-cart-backdrop" class="pos-app-cart-backdrop" aria-label="Close cart"></button>

    <button type="button" id="pos-app-cart-fab" class="pos-app-cart-fab" data-pos-cart-toggle aria-label="Open cart">
        <span class="pos-app-cart-fab__icon" aria-hidden="true"><i class="fe fe-shopping-cart"></i></span>
        <span class="pos-app-cart-fab__meta">
            <span class="pos-app-cart-fab__count" x-show="$store.pos.cart.length > 0" x-text="$store.pos.cart.length"></span>
            <span class="pos-app-cart-fab__total pos-tabular" x-text="$store.pos.moneyLabel($store.pos.total)"></span>
        </span>
    </button>

    @include('pos.partials.modals')
</div>
@endsection

@section('js')
<script src="{{ asset('js/pos/pos-contracts.js') }}"></script>
<script src="{{ asset('js/pos/pos-runtime.js') }}"></script>
<script src="{{ asset('js/pos/pos-hardware.js') }}"></script>
<script src="{{ asset('js/pos/pos-realtime-echo.js') }}"></script>
<script src="{{ asset('js/pos-terminal.js') }}"></script>
@endsection
