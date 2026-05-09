@extends('layouts.pos-display')

@section('title', 'Customer display')

@section('content')
<script>
    window.posDisplayConfig = {
        currencySymbol: @json($currencySymbol),
        tenantName: @json($tenantName),
    };

    function customerDisplayPage() {
        return {
            tenantName: window.posDisplayConfig.tenantName,
            currencySymbol: window.posDisplayConfig.currencySymbol,
            lines: [],
            subtotal: 0,
            tax: 0,
            orderDiscount: 0,
            totalDiscount: 0,
            total: 0,
            customerName: '',
            connected: false,
            applySnapshot(snap) {
                if (!snap || typeof snap !== 'object') return;
                this.connected = true;
                this.lines = Array.isArray(snap.lines) ? snap.lines : [];
                this.subtotal = snap.subtotal ?? 0;
                this.tax = snap.tax ?? 0;
                this.orderDiscount = snap.orderDiscount ?? 0;
                this.totalDiscount = snap.totalDiscount ?? 0;
                this.total = snap.total ?? 0;
                if (snap.tenantName) this.tenantName = snap.tenantName;
                if (snap.currencySymbol) this.currencySymbol = snap.currencySymbol;
                if (snap.customerName != null) this.customerName = snap.customerName;
            },
            init() {
                if (!window.Pos?.CustomerDisplayBridge) return;
                try {
                    window.Pos.CustomerDisplayBridge.subscribe((snap) => {
                        this.applySnapshot(snap);
                    });
                } catch (e) {
                    console.warn('[POS Display]', e);
                }
                try {
                    const key =
                        window.Pos?.STORAGE_KEYS?.customerDisplay ||
                        'pos_customer_display_snapshot_v1';
                    const raw = localStorage.getItem(key);
                    if (raw) {
                        const snap = JSON.parse(raw);
                        this.applySnapshot(snap);
                    }
                } catch {
                    /* ignore */
                }
            },
            brandInitials() {
                const n = (this.tenantName || 'R').trim();
                return n
                    .split(/\s+/)
                    .map((w) => w[0])
                    .join('')
                    .slice(0, 2)
                    .toUpperCase();
            },
            fmtMoney(n) {
                const v = Number(n);
                if (!Number.isFinite(v)) return '\u2014';
                return this.currencySymbol + v.toFixed(2);
            },
            amountDueDisplay() {
                if (this.lines.length === 0) return '\u2014';
                return this.fmtMoney(this.total);
            },
        };
    }
</script>

<div
    id="pos-customer-display-root"
    class="pos-cd-root"
    x-data="customerDisplayPage()"
    x-init="init()"
    x-cloak
>
    <header class="pos-cd-brand-layer pos-cd-brand-layer--compact">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="pos-cd-brand-mark" x-text="brandInitials()"></div>
                <div>
                    <div class="text-uppercase tx-11 font-weight-bold pos-cd-text-muted-soft mb-0" style="letter-spacing:0.12em;">Store</div>
                    <h1 class="m-0 font-weight-bold text-white pos-cd-store-title" x-text="tenantName"></h1>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="pos-cd-live-dot" x-show="connected"></span>
                <span class="pos-cd-status-pill pos-cd-num tx-12 font-weight-bold"
                    :class="connected ? 'pos-cd-status-pill--live' : 'pos-cd-status-pill--idle'"
                    x-text="connected ? 'Live' : 'Standby'"></span>
            </div>
        </div>
    </header>

    <div class="pos-cd-body">
        <div class="pos-cd-main-col">
            <template x-if="lines.length === 0">
                <div class="pos-cd-empty-hero" aria-live="polite">
                    <div class="pos-cd-empty-pulse" aria-hidden="true"></div>
                    <h2 class="pos-cd-empty-title text-white font-weight-bold">Waiting for cashier</h2>
                    <p class="pos-cd-empty-sub pos-cd-text-muted mb-3">
                        Items will appear here as they are scanned.
                    </p>
                    <p class="pos-cd-empty-hint pos-cd-text-muted-soft tx-13 mb-0">
                        Keep this screen facing your customers during checkout.
                    </p>
                </div>
            </template>

            <template x-if="lines.length > 0">
                <div class="pos-cd-has-lines">
                    <div class="pos-cd-promo-strip">
                        <span class="font-weight-semibold">Today’s picks · rewards · specials</span>
                        <span class="d-none d-md-inline pos-cd-text-muted ml-2">— Configure promotions (Phase 6)</span>
                    </div>
                    <section class="pos-cd-lines-panel pos-cd-lines-panel--scroll" aria-live="polite">
                        <div class="px-4 py-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                            <span class="text-uppercase tx-11 font-weight-bold pos-cd-text-muted-soft" style="letter-spacing:0.1em;">Your items</span>
                            <span class="pos-cd-text-muted tx-12" x-show="customerName" x-text="customerName"></span>
                        </div>
                        <div class="pos-cd-lines-scroll">
                            <template x-for="(line, idx) in lines" :key="idx">
                                <div class="pos-cd-line-card">
                                    <div class="pos-cd-line-name" x-text="line.name"></div>
                                    <div class="pos-cd-num pos-cd-text-muted tx-13 pos-tabular" x-text="fmtMoney(line.unitPrice)"></div>
                                    <div class="pos-cd-num pos-cd-text-soft font-weight-bold">×<span x-text="line.qty"></span></div>
                                    <div class="pos-cd-num font-weight-bold text-white tx-16 pos-tabular" x-text="fmtMoney(line.lineTotal)"></div>
                                </div>
                            </template>
                        </div>
                    </section>
                </div>
            </template>
        </div>

        <aside class="pos-cd-total-dock" aria-live="polite">
            <div class="pos-cd-total-hero" :class="{ 'pos-cd-total-hero--idle': lines.length === 0 }">
                <div class="pos-cd-total-label text-white">Amount due</div>
                <div class="pos-cd-total-value text-white pos-cd-num pos-tabular" x-text="amountDueDisplay()"></div>
                <div class="pos-cd-summary-rows text-white">
                    <div><span>Subtotal</span><span class="pos-cd-num pos-tabular" x-text="lines.length === 0 ? '\u2014' : fmtMoney(subtotal)"></span></div>
                    <div x-show="orderDiscount > 0 || totalDiscount > 0">
                        <span>Discounts</span>
                        <span class="pos-cd-num" x-text="'-' + fmtMoney(orderDiscount || totalDiscount || 0)"></span>
                    </div>
                    <div><span>Tax</span><span class="pos-cd-num pos-tabular" x-text="lines.length === 0 ? '\u2014' : fmtMoney(tax)"></span></div>
                </div>
            </div>
            <div class="pos-cd-qr" :class="{ 'pos-cd-qr--dim': lines.length === 0 }">
                <i class="fe fe-maximize-2 mb-2 tx-24 opacity-50"></i>
                <div>Scan to pay · loyalty · digital receipt</div>
                <div class="tx-11 mt-1 opacity-75" x-show="lines.length === 0">Ready when items are added</div>
                <div class="tx-11 mt-1 opacity-75" x-show="lines.length > 0">Placeholder — configure in settings</div>
            </div>
        </aside>
    </div>
</div>
@endsection
