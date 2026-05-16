{{-- Standalone POS top bar — search, status, actions (no ERP chrome) --}}
<header class="pos-app-topbar">
    <div class="pos-app-topbar__inner">
        <div class="pos-app-topbar__cluster pos-app-topbar__cluster--lead">
            <button type="button" class="pos-app-icon-btn pos-app-menu-btn" data-pos-rail-toggle aria-label="Categories">
                <i class="fe fe-menu pos-app-menu-btn__icon-open" aria-hidden="true"></i>
                <i class="fe fe-x pos-app-menu-btn__icon-close" aria-hidden="true"></i>
            </button>
            <div class="pos-app-brand">
                <div class="pos-app-brand-mark" aria-hidden="true">{{ strtoupper(substr($tenantName ?? 'P', 0, 1)) }}</div>
                <div class="pos-app-brand-text">
                    <span class="pos-app-brand-name">{{ $tenantName ?? config('app.name') }}</span>
                    <span class="pos-app-brand-meta">{{ $cashierName ?? '' }}</span>
                </div>
            </div>
        </div>

        <div class="pos-app-topbar__cluster pos-app-topbar__cluster--search">
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

        <div class="pos-app-topbar__cluster pos-app-topbar__cluster--customer pos-customer-search-wrap">
            <input type="search" class="pos-app-customer-select"
                placeholder="Customer — search"
                x-model="$store.pos.customerSearchQuery"
                @input.debounce.300ms="$store.pos.searchCustomersDebounced()"
                @focus="$store.pos.searchCustomersDebounced()">
            <select class="sr-only" x-model="$store.pos.customerId" aria-hidden="true" tabindex="-1">
                <option value="">Walk-in</option>
                @foreach(($customers ?? []) as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
                <template x-for="c in $store.pos.newCustomers" :key="c.id">
                    <option :value="c.id" x-text="c.name"></option>
                </template>
            </select>
            <div class="pos-customer-search-dropdown" x-show="$store.pos.customerSearchResults.length" x-cloak>
                <template x-for="c in $store.pos.customerSearchResults" :key="'sr-'+c.id">
                    <button type="button" class="pos-customer-search-item"
                        @click="$store.pos.selectCustomerFromSearch(c)">
                        <span x-text="c.name"></span>
                        <small class="text-muted" x-text="c.phone || c.email || ''"></small>
                    </button>
                </template>
            </div>
            <button type="button" class="pos-app-icon-btn" @click="$store.pos.customerId = ''" title="Walk-in">
                <i class="fe fe-user"></i>
            </button>
            <button type="button" class="pos-app-icon-btn" data-toggle="modal" data-target="#quickCustomerModal" title="Add customer">
                <i class="fe fe-user-plus"></i>
            </button>
        </div>

        <div class="pos-app-topbar__cluster pos-app-topbar__cluster--actions">
            <div class="pos-app-actions">
                <button type="button" class="pos-app-pill"
                    @click="$store.pos.holdCurrentOrder()"
                    :disabled="!$store.pos.shiftId || !$store.pos.cart.length"
                    title="Hold order">
                    <i class="fe fe-pause-circle"></i><span class="pos-app-pill-label">Hold</span>
                </button>
                <button type="button" class="pos-app-pill" data-toggle="modal" data-target="#heldOrdersModal" title="Held orders">
                    <i class="fe fe-inbox"></i><span class="pos-app-pill-label">Held</span>
                    <span class="pos-app-badge" x-show="$store.pos.heldOrders.length > 0" x-text="$store.pos.heldOrders.length"></span>
                </button>
                <a href="{{ route('pos.display') }}" target="_blank" rel="noopener" class="pos-app-pill pos-app-pill--accent" title="Customer display">
                    <i class="fe fe-monitor"></i><span class="pos-app-pill-label">Display</span>
                </a>
                <button type="button" class="pos-app-pill pos-app-pill--warn"
                    @click="$store.pos.openOfflineQueueModal()"
                    x-show="$store.pos.pendingSyncCount > 0" x-cloak
                    title="Offline queue">
                    <i class="fe fe-cloud-off"></i>
                    <span class="pos-app-badge pos-app-badge--warn" x-text="$store.pos.pendingSyncCount"></span>
                </button>

                <span class="pos-app-actions-extra">
                    <a href="{{ route('pos.sales.index') }}" class="pos-app-pill pos-app-pill--ghost" title="Sales history">
                        <i class="fe fe-archive"></i><span class="pos-app-pill-label">Sales</span>
                    </a>
                    <button type="button" class="pos-app-icon-btn" title="Light / dark mode"
                        @click="$store.pos.togglePosTheme()">
                        <i class="fe" :class="$store.pos.posTheme === 'dark' ? 'fe-sun' : 'fe-moon'"></i>
                    </button>
                    <button type="button" class="pos-app-icon-btn" title="Toggle fullscreen" id="pos-app-fullscreen-btn">
                        <i class="fe fe-maximize-2"></i>
                    </button>
                    <a href="{{ route('dashboard') }}" class="pos-app-pill pos-app-pill--ghost" title="Back to dashboard">
                        <i class="fe fe-grid"></i><span class="pos-app-pill-label">ERP</span>
                    </a>
                    <button type="button" class="pos-app-icon-btn" title="Keyboard shortcuts"
                        @click="$store.pos.posShowShortcuts = !$store.pos.posShowShortcuts">
                        <i class="fe fe-help-circle"></i>
                    </button>
                </span>
            </div>

            <details class="pos-app-more-menu">
                <summary class="pos-app-icon-btn pos-app-more-menu__trigger" aria-label="More actions">
                    <i class="fe fe-more-horizontal"></i>
                </summary>
                <div class="pos-app-more-menu__panel" role="menu">
                    <a href="{{ route('pos.sales.index') }}" class="pos-app-more-menu__item" role="menuitem">
                        <i class="fe fe-archive"></i> Sales
                    </a>
                    <button type="button" class="pos-app-more-menu__item" role="menuitem"
                        @click="$store.pos.togglePosTheme()">
                        <i class="fe fe-moon"></i> Theme
                    </button>
                    <button type="button" class="pos-app-more-menu__item" role="menuitem" id="pos-app-fullscreen-btn-mobile">
                        <i class="fe fe-maximize-2"></i> Fullscreen
                    </button>
                    <a href="{{ route('dashboard') }}" class="pos-app-more-menu__item" role="menuitem">
                        <i class="fe fe-grid"></i> ERP
                    </a>
                    <button type="button" class="pos-app-more-menu__item" role="menuitem"
                        @click="$store.pos.posShowShortcuts = !$store.pos.posShowShortcuts">
                        <i class="fe fe-help-circle"></i> Shortcuts
                    </button>
                </div>
            </details>
        </div>

        <div class="pos-app-topbar__cluster pos-app-topbar__cluster--status">
            @if($activeShift)
                <span class="pos-app-chip pos-app-chip--ok" title="Shift open"><i class="fe fe-unlock"></i><span class="pos-app-chip-label">{{ $activeShift->opened_at->format('H:i') }}</span></span>
            @else
                <span class="pos-app-chip pos-app-chip--bad" title="Shift closed"><i class="fe fe-lock"></i><span class="pos-app-chip-label">Closed</span></span>
            @endif
            <span class="pos-app-chip" :class="$store.pos.online ? 'pos-app-chip--live' : 'pos-app-chip--bad'" :title="$store.pos.online ? 'Online' : 'Offline'">
                <i class="fe" :class="$store.pos.online ? 'fe-wifi' : 'fe-wifi-off'"></i>
                <span class="pos-app-chip-label" x-text="$store.pos.online ? 'Online' : 'Off'"></span>
            </span>
            <span class="pos-app-chip pos-app-chip--queue" x-show="$store.pos.pendingSyncCount > 0" x-cloak title="Pending sync">
                <i class="fe fe-upload-cloud"></i> <span class="pos-app-chip-label" x-text="$store.pos.pendingSyncCount"></span>
            </span>
            <span class="pos-app-chip pos-app-chip--muted" x-show="$store.pos.catalogStale" x-cloak title="Catalog stale">
                <i class="fe fe-refresh-cw"></i>
            </span>
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
        function bindFullscreen(btn) {
            if (!btn) return;
            btn.addEventListener('click', function () {
                var el = document.documentElement;
                if (!document.fullscreenElement) {
                    if (el.requestFullscreen) el.requestFullscreen();
                } else if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            });
        }
        bindFullscreen(document.getElementById('pos-app-fullscreen-btn'));
        bindFullscreen(document.getElementById('pos-app-fullscreen-btn-mobile'));
    })();
</script>
