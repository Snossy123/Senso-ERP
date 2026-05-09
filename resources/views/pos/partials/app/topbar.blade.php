{{-- Standalone POS top bar — search, status, actions (no ERP chrome) --}}
<header class="pos-app-topbar">
    <div class="pos-app-topbar__inner">
        <div class="pos-app-brand">
            <button type="button" class="pos-app-icon-btn d-xl-none" aria-label="Categories" onclick="document.body.classList.toggle('pos-app-rail-open')">
                <i class="fe fe-menu"></i>
            </button>
            <div class="pos-app-brand-mark" aria-hidden="true">{{ strtoupper(substr($tenantName ?? 'P', 0, 1)) }}</div>
            <div class="pos-app-brand-text">
                <span class="pos-app-brand-name">{{ $tenantName ?? config('app.name') }}</span>
                <span class="pos-app-brand-meta">{{ $cashierName ?? '' }}</span>
            </div>
        </div>

        <div class="pos-app-search-wrap">
            <label class="sr-only" for="pos-search">Search products</label>
            <div class="pos-app-search">
                <i class="fe fe-search pos-app-search-icon"></i>
                <input
                    id="pos-search"
                    type="text"
                    autocomplete="off"
                    autocapitalize="none"
                    x-model="$store.pos.searchQuery"
                    @input.debounce.300ms="$store.pos.onSearch()"
                    @keydown.enter.prevent="$store.pos.barcodeSearch()"
                    class="pos-app-search-input"
                    placeholder="Search products · scan barcode · F2"
                >
            </div>
        </div>

        <div class="pos-app-customer-pick">
            <select class="pos-app-customer-select" x-model="$store.pos.customerId" title="Customer">
                <option value="">Walk-in</option>
                @foreach(($customers ?? []) as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
                <template x-for="c in $store.pos.newCustomers" :key="c.id">
                    <option :value="c.id" x-text="c.name"></option>
                </template>
            </select>
            <button type="button" class="pos-app-icon-btn" data-toggle="modal" data-target="#quickCustomerModal" title="Add customer">
                <i class="fe fe-user-plus"></i>
            </button>
        </div>

        <div class="pos-app-actions">
            <button type="button" class="pos-app-pill"
                @click="$store.pos.holdCurrentOrder()"
                :disabled="!$store.pos.shiftId || !$store.pos.cart.length">
                <i class="fe fe-pause-circle"></i><span class="d-none d-lg-inline">Hold</span>
            </button>
            <button type="button" class="pos-app-pill" data-toggle="modal" data-target="#heldOrdersModal">
                <i class="fe fe-inbox"></i><span class="d-none d-lg-inline">Held</span>
                <span class="pos-app-badge" x-show="$store.pos.heldOrders.length > 0" x-text="$store.pos.heldOrders.length"></span>
            </button>
            <a href="{{ route('pos.sales.index') }}" class="pos-app-pill pos-app-pill--ghost" title="Sales history">
                <i class="fe fe-archive"></i><span class="d-none d-xl-inline">Sales</span>
            </a>
            <a href="{{ route('pos.display') }}" target="_blank" rel="noopener" class="pos-app-pill pos-app-pill--accent">
                <i class="fe fe-monitor"></i><span class="d-none d-lg-inline">Display</span>
            </a>
            <button type="button" class="pos-app-pill pos-app-pill--warn"
                @click="$store.pos.openOfflineQueueModal()"
                x-show="$store.pos.pendingSyncCount > 0" x-cloak>
                <i class="fe fe-cloud-off"></i>
                <span class="pos-app-badge pos-app-badge--warn" x-text="$store.pos.pendingSyncCount"></span>
            </button>
            <button type="button" class="pos-app-icon-btn" title="Toggle fullscreen" id="pos-app-fullscreen-btn">
                <i class="fe fe-maximize-2"></i>
            </button>
            <a href="{{ route('dashboard') }}" class="pos-app-pill pos-app-pill--ghost" title="Back to dashboard">
                <i class="fe fe-grid"></i><span class="d-none d-xl-inline">ERP</span>
            </a>
        </div>

        <div class="pos-app-status">
            @if($activeShift)
                <span class="pos-app-chip pos-app-chip--ok"><i class="fe fe-unlock"></i> {{ $activeShift->opened_at->format('H:i') }}</span>
            @else
                <span class="pos-app-chip pos-app-chip--bad"><i class="fe fe-lock"></i> Closed</span>
            @endif
            <span class="pos-app-chip" :class="$store.pos.online ? 'pos-app-chip--live' : 'pos-app-chip--bad'">
                <i class="fe" :class="$store.pos.online ? 'fe-wifi' : 'fe-wifi-off'"></i>
                <span x-text="$store.pos.online ? 'Online' : 'Off'"></span>
            </span>
            <span class="pos-app-chip pos-app-chip--queue" x-show="$store.pos.pendingSyncCount > 0" x-cloak>
                <i class="fe fe-upload-cloud"></i> <span x-text="$store.pos.pendingSyncCount"></span>
            </span>
            <span class="pos-app-chip pos-app-chip--muted" x-show="$store.pos.catalogStale" x-cloak>
                <i class="fe fe-refresh-cw"></i>
            </span>
            <button type="button" class="pos-app-icon-btn tx-13" @click="$store.pos.posShowShortcuts = !$store.pos.posShowShortcuts">
                <i class="fe fe-help-circle"></i>
            </button>
        </div>
    </div>

    <div class="pos-app-shortcuts" x-show="$store.pos.posShowShortcuts" x-transition x-cloak>
        <span><kbd>F2</kbd> Search</span>
        <span><kbd>F4</kbd> Checkout</span>
        <span><kbd>F6</kbd> Held</span>
        <span><kbd>Esc</kbd> Focus search</span>
        <span><kbd>±</kbd> Qty · <kbd>Del</kbd> Line</span>
    </div>
</header>

<script>
    (function () {
        var btn = document.getElementById('pos-app-fullscreen-btn');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var el = document.documentElement;
            if (!document.fullscreenElement) {
                if (el.requestFullscreen) el.requestFullscreen();
            } else if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        });
    })();
</script>
