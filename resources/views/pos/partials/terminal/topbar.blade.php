{{-- Premium POS command bar — actions, status, shortcut hints --}}
<div class="pos-command-bar pos-command-bar--compact pos-card" data-pos-tenant-name="{{ $tenantName ?? config('app.name') }}">
    <div class="pos-command-bar__main">
        <div class="pos-command-bar__brand">
            <h1 class="pos-command-bar__title">POS Terminal</h1>
            <p class="pos-command-bar__subtitle pos-command-bar__subtitle--collapsible">Retail checkout · barcode-first</p>
        </div>

        <div class="pos-command-bar__actions">
            <button type="button"
                class="pos-cmd-pill"
                @click="$store.pos.holdCurrentOrder()"
                :disabled="!$store.pos.shiftId || !$store.pos.cart.length">
                <i class="fe fe-pause-circle"></i><span>Hold</span>
            </button>
            <button type="button"
                class="pos-cmd-pill"
                data-toggle="modal"
                data-target="#heldOrdersModal">
                <i class="fe fe-inbox"></i><span>Held</span>
                <span class="pos-cmd-badge" x-show="$store.pos.heldOrders.length > 0" x-text="$store.pos.heldOrders.length"></span>
            </button>
            <a href="{{ route('pos.sales.index') }}" class="pos-cmd-pill pos-cmd-pill--link">
                <i class="fe fe-archive"></i><span>Sales</span>
            </a>
            <a href="{{ route('pos.display') }}" target="_blank" rel="noopener" class="pos-cmd-pill pos-cmd-pill--accent">
                <i class="fe fe-monitor"></i><span>Display</span>
            </a>
            <button type="button"
                class="pos-cmd-pill pos-cmd-pill--muted"
                @click="$store.pos.openOfflineQueueModal()"
                x-show="$store.pos.pendingSyncCount > 0">
                <i class="fe fe-cloud-off"></i><span>Queue</span>
                <span class="pos-cmd-badge pos-cmd-badge--warn" x-text="$store.pos.pendingSyncCount"></span>
            </button>
        </div>

        <div class="pos-command-bar__status">
            @if($activeShift)
                <span class="pos-status-chip pos-status-chip--ok">
                    <i class="fe fe-unlock"></i> Shift {{ $activeShift->opened_at->format('H:i') }}
                </span>
            @else
                <span class="pos-status-chip pos-status-chip--bad">
                    <i class="fe fe-lock"></i> Closed
                </span>
            @endif

            <span class="pos-status-chip"
                :class="$store.pos.online ? 'pos-status-chip--live' : 'pos-status-chip--bad'">
                <i class="fe" :class="$store.pos.online ? 'fe-wifi' : 'fe-wifi-off'"></i>
                <span x-text="$store.pos.online ? 'Online' : 'Offline'"></span>
            </span>

            <span class="pos-status-chip pos-status-chip--queue" x-show="$store.pos.pendingSyncCount > 0" x-cloak>
                <i class="fe fe-upload-cloud"></i>
                <span x-text="'Queue ' + $store.pos.pendingSyncCount"></span>
                <template x-if="$store.pos.queueStatsFailed > 0">
                    <span class="pos-cmd-badge pos-cmd-badge--danger ml-1" x-text="$store.pos.queueStatsFailed + ' err'"></span>
                </template>
            </span>

            <span class="pos-status-chip pos-status-chip--muted" x-show="$store.pos.catalogStale" x-cloak>
                <i class="fe fe-refresh-cw"></i> Refresh catalog
            </span>

            <button type="button"
                class="pos-cmd-help"
                @click="$store.pos.posShowShortcuts = !$store.pos.posShowShortcuts"
                :aria-expanded="$store.pos.posShowShortcuts ? 'true' : 'false'">
                <i class="fe fe-help-circle"></i><span class="d-none d-md-inline">Shortcuts</span>
            </button>

            <a href="{{ route('dashboard') }}" class="pos-cmd-pill pos-cmd-pill--ghost">
                <i class="fe fe-arrow-left"></i><span>Exit</span>
            </a>
        </div>
    </div>

    <div class="pos-shortcuts-row"
        x-show="$store.pos.posShowShortcuts"
        x-transition
        x-cloak>
        <span class="pos-shortcut-pill"><kbd>F2</kbd> Search</span>
        <span class="pos-shortcut-pill"><kbd>F4</kbd> Checkout</span>
        <span class="pos-shortcut-pill"><kbd>F6</kbd> Held orders</span>
        <span class="pos-shortcut-pill"><kbd>Esc</kbd> Close / focus search</span>
        <span class="pos-shortcut-pill"><kbd>±</kbd> Qty · <kbd>Del</kbd> Line</span>
    </div>
</div>
