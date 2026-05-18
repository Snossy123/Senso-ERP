function initPosTerminal() {
    if (window.posSystemInitialized) return;
    window.posSystemInitialized = true;

    function posModal(sel, action) {
        try {
            if (typeof window.jQuery === 'undefined' || !window.jQuery.fn?.modal) return;
            const $el = window.jQuery(sel);
            if ($el?.modal) $el.modal(action || 'show');
        } catch (e) {
            console.warn('[POS] modal:', e);
        }
    }

    const CART_DRAFT_KEY = 'pos_cart_draft_v3';

    /** Normalize to finite number; invalid inputs → fallback (default 0). */
    function posFinite(n, fallback = 0) {
        if (n === '' || n === null || typeof n === 'undefined') return fallback;
        const x = typeof n === 'number' ? n : parseFloat(n);
        return Number.isFinite(x) ? x : fallback;
    }

    /** For money display: preserve invalid as NaN so callers can show — */
    function posFiniteOrNaN(n) {
        if (n === '' || n === null || typeof n === 'undefined') return NaN;
        const x = typeof n === 'number' ? n : parseFloat(n);
        return Number.isFinite(x) ? x : NaN;
    }

    const productStore = {
        products: [],
        filteredProducts: [],
        categories: [],
        selectedCategory: 'all',
        searchQuery: '',
        loadingProducts: false,
        hasMoreProducts: true,
        currentPage: 1,
        perPage: 24,
        lastLoadedAt: null,
        catalogStale: false,
        _catalogFetchController: null,
        _staleTimer: null,

        _abortCatalogFetch() {
            try {
                this._catalogFetchController?.abort();
            } catch {
                /* ignore */
            }
            this._catalogFetchController = null;
        },

        async fetchProducts(reset = false, extra = {}) {
            if (!this.routes.productsFeed || this.loadingProducts) return;
            if (!reset && !this.hasMoreProducts) return;

            this._abortCatalogFetch();
            const controller = new AbortController();
            this._catalogFetchController = controller;

            this.loadingProducts = true;
            try {
                const page = reset ? 1 : this.currentPage;
                const params = new URLSearchParams({
                    page: String(page),
                    per_page: String(this.perPage),
                    ...(this.searchQuery.trim() ? { q: this.searchQuery.trim() } : {}),
                    ...(this.selectedCategory !== 'all'
                        ? { category_id: String(this.selectedCategory) }
                        : {}),
                    ...extra,
                });

                const response = await fetch(`${this.routes.productsFeed}?${params.toString()}`, {
                    method: 'GET',
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error('Unable to fetch products.');

                const data = await response.json();
                const rows = data.data || [];

                this.products = reset ? rows : this.products.concat(rows);
                this.filteredProducts = this.products;
                this.currentPage = (data.current_page || page) + 1;
                this.hasMoreProducts = Boolean(data.next_page_url);
                this.lastLoadedAt = Date.now();
                this.catalogStale = false;
                if (reset && typeof this.catalogUiPage === 'number') {
                    this.catalogUiPage = 1;
                }
                if (typeof this._syncKeyboardCatalogIndex === 'function') {
                    this._syncKeyboardCatalogIndex(reset);
                }
            } catch (error) {
                if (error?.name === 'AbortError') return;
                console.error(error);
                if (window.Pos?.Telemetry) {
                    Pos.Telemetry.toast('Unable to load products — check connection.', 'danger');
                } else {
                    alert('Unable to load products.');
                }
            } finally {
                this.loadingProducts = false;
                this._catalogFetchController = null;
            }
        },

        async onSearch() {
            await this.fetchProducts(true);
        },

        closePosAppRail() {
            document.body.classList.remove('pos-app-rail-open');
            document.body.dispatchEvent(new CustomEvent('pos-app-rail-toggled'));
        },

        togglePosAppRail() {
            document.body.classList.toggle('pos-app-rail-open');
            document.body.dispatchEvent(new CustomEvent('pos-app-rail-toggled'));
        },

        closePosAppCart() {
            document.body.classList.remove('pos-app-cart-open');
        },

        togglePosAppCart() {
            document.body.classList.toggle('pos-app-cart-open');
        },

        openPosAppCart() {
            document.body.classList.add('pos-app-cart-open');
        },

        async setCategory(categoryId) {
            this.selectedCategory = categoryId;
            await this.fetchProducts(true);
            if (window.matchMedia('(max-width: 991px)').matches) {
                this.closePosAppRail();
            }
        },

        async onCatalogScroll(event) {
            const target = event.target;
            const nearBottom =
                target.scrollTop + target.clientHeight >= target.scrollHeight - 280;
            if (nearBottom) {
                await this.fetchProducts(false);
            }
        },

        async barcodeSearch() {
            const code = this.searchQuery.trim();
            if (!code) return;

            const localExact = this.products.find((p) => p.barcode === code || p.sku === code);
            if (localExact) {
                this.addToCart(localExact);
                this.searchQuery = '';
                if (typeof this._flashSearchScanOk === 'function') this._flashSearchScanOk();
                return;
            }

            await this.fetchProducts(true, { barcode: code, per_page: '1' });
            const exact = this.products[0];
            if (exact && (exact.barcode === code || exact.sku === code)) {
                this.addToCart(exact);
                this.searchQuery = '';
                if (typeof this._flashSearchScanOk === 'function') this._flashSearchScanOk();
                this.fetchProducts(true);
            }
        },
    };

    const cartStore = {
        cart: [],
        orderDiscount: 0,
        orderDiscountMode: 'amount',
        notes: '',
        customerId: '',

        addToCart(product) {
            if (!this.shiftId) {
                if (window.Pos?.Telemetry) Pos.Telemetry.toast('Open a shift first.', 'warning');
                else alert('Please open a shift first.');
                return;
            }
            if (product.out_of_stock) {
                if (window.Pos?.Telemetry) Pos.Telemetry.toast('Out of stock.', 'warning');
                else alert('Out of stock.');
                return;
            }
            if (product.has_variants && product.variants?.length > 0) {
                if (typeof this.openCatalogProductDetail === 'function') {
                    this.openCatalogProductDetail(null, product);
                    return;
                }
                this.activeProduct = product;
                this.activeProductVariants = product.variants;
                posModal('#variantModal', 'show');
                return;
            }

            const existing = this.cart.find((item) => item.id === product.id && !item.variant_id);
            if (existing) {
                if (existing.qty < product.stock) existing.qty++;
                else if (window.Pos?.Telemetry) Pos.Telemetry.toast('Stock limit reached.', 'warning');
                else alert('Stock limit reached.');
            } else {
                this.cart.push({
                    ...product,
                    qty: 1,
                    discount_pct: 0,
                    discount_amount: 0,
                    discount_mode: 'pct',
                    variant_id: null,
                });
            }

            const lineIdx = existing ? this.cart.indexOf(existing) : this.cart.length - 1;
            if (typeof this.cartFocusedIndex === 'number') {
                this.cartFocusedIndex = lineIdx;
            }
            if (typeof this._flashCatalogProduct === 'function') this._flashCatalogProduct(product.id);
            if (typeof this._pulseCartRow === 'function') this._pulseCartRow(lineIdx);
            this.scrollToBottomCart();
            if (typeof this._scheduleCustomerDisplayBroadcast === 'function') {
                this._scheduleCustomerDisplayBroadcast();
            }
            if (typeof this._persistCartDraft === 'function') this._persistCartDraft();
        },

        addVariantToCart(variant) {
            const product = this.activeProduct;
            if (!product) return;

            const existing = this.cart.find(
                (item) => item.id === product.id && item.variant_id === variant.id
            );
            if (existing) {
                if (existing.qty < product.stock) existing.qty++;
                else if (window.Pos?.Telemetry) Pos.Telemetry.toast('Stock limit reached.', 'warning');
                else alert('Stock limit reached.');
            } else {
                this.cart.push({
                    id: product.id,
                    name: `${product.name} - ${variant.name}`,
                    price: variant.price,
                    stock: product.stock,
                    qty: 1,
                    discount_pct: 0,
                    variant_id: variant.id,
                });
            }

            posModal('#variantModal', 'hide');
            const lineIdx = existing ? this.cart.indexOf(existing) : this.cart.length - 1;
            if (typeof this.cartFocusedIndex === 'number') {
                this.cartFocusedIndex = lineIdx;
            }
            if (typeof this._pulseCartRow === 'function') this._pulseCartRow(lineIdx);
            if (typeof this._flashCatalogProduct === 'function') this._flashCatalogProduct(product.id);
            this.scrollToBottomCart();
            if (typeof this._scheduleCustomerDisplayBroadcast === 'function') {
                this._scheduleCustomerDisplayBroadcast();
            }
            if (typeof this._persistCartDraft === 'function') this._persistCartDraft();
        },

        _syncCartExpandedAfterRemove(removedIdx) {
            if (this.cartExpandedIndex === null || this.cartExpandedIndex === undefined) return;
            if (removedIdx === this.cartExpandedIndex) {
                this.cartExpandedIndex = null;
            } else if (removedIdx < this.cartExpandedIndex) {
                this.cartExpandedIndex--;
            }
        },

        toggleCartLineExpand(idx) {
            if (typeof idx !== 'number' || idx < 0 || idx >= this.cart.length) return;
            if (typeof this.cartFocusedIndex === 'number') this.cartFocusedIndex = idx;
            this.cartExpandedIndex = this.cartExpandedIndex === idx ? null : idx;
        },

        updateQty(idx, delta) {
            const item = this.cart[idx];
            if (!item) return;
            const pulseAt = idx;
            const next = item.qty + delta;
            if (next <= 0) {
                this.cart.splice(idx, 1);
                this._syncCartExpandedAfterRemove(idx);
                if (typeof this.cartFocusedIndex === 'number') {
                    if (this.cart.length === 0) this.cartFocusedIndex = 0;
                    else if (this.cartFocusedIndex > idx) this.cartFocusedIndex--;
                    else if (this.cartFocusedIndex >= this.cart.length) {
                        this.cartFocusedIndex = this.cart.length - 1;
                    }
                }
            } else if (next > item.stock) {
                if (window.Pos?.Telemetry) Pos.Telemetry.toast('Stock limit reached.', 'warning');
                else alert('Stock limit reached.');
            } else item.qty = next;
            if (typeof this._pulseCartRow === 'function') {
                const row = this.cart.length ? Math.min(pulseAt, this.cart.length - 1) : -1;
                this._pulseCartRow(row);
            }
            if (typeof this._scheduleCustomerDisplayBroadcast === 'function') {
                this._scheduleCustomerDisplayBroadcast();
            }
            if (typeof this._persistCartDraft === 'function') this._persistCartDraft();
        },

        validateQty(idx) {
            const item = this.cart[idx];
            if (!item) return;
            if (item.qty > item.stock) item.qty = item.stock;
            if (item.qty <= 0) item.qty = 1;
        },

        removeItem(idx) {
            this.cart.splice(idx, 1);
            this._syncCartExpandedAfterRemove(idx);
            if (typeof this.cartFocusedIndex === 'number') {
                if (this.cart.length === 0) this.cartFocusedIndex = 0;
                else if (this.cartFocusedIndex >= this.cart.length) {
                    this.cartFocusedIndex = this.cart.length - 1;
                } else if (idx < this.cartFocusedIndex) {
                    this.cartFocusedIndex--;
                }
            }
            if (typeof this._scheduleCustomerDisplayBroadcast === 'function') {
                this._scheduleCustomerDisplayBroadcast();
            }
            if (typeof this._persistCartDraft === 'function') this._persistCartDraft();
        },

        clearCartState() {
            this.cart = [];
            this.closePosAppCart();
            this.cartExpandedIndex = null;
            this.orderDiscount = 0;
            this.orderDiscountMode = 'amount';
            this.customerId = '';
            this.notes = '';
            this.paymentMethod = 'cash';
            this.amountTendered = 0;
            this.lastReceiptPreview = null;
            this.lastSaleChangeDue = 0;
            if (typeof this.cartFocusedIndex === 'number') this.cartFocusedIndex = 0;
            if (typeof this._scheduleCustomerDisplayBroadcast === 'function') {
                this._scheduleCustomerDisplayBroadcast();
            }
            try {
                localStorage.removeItem(CART_DRAFT_KEY);
            } catch {
                /* ignore */
            }
        },

        scrollToBottomCart() {
            setTimeout(() => {
                const area = document.getElementById('cart-scroll-area');
                if (area) area.scrollTop = area.scrollHeight;
            }, 60);
        },

        itemTotal(item) {
            const price = posFinite(item?.price, 0);
            const qty = posFinite(item?.qty, 0);
            const gross = price * qty;
            const disc = this.lineDiscountGross(item);
            const line = gross - disc;
            return Number.isFinite(line) ? Math.max(0, line) : 0;
        },
    };

    /**
     * Financial getters MUST live on the final Alpine.store object — not on cartStore.
     * `{ ...cartStore }` evaluates getters once and copies snapshot numbers (typically 0),
     * so Due/subtotal/tax never updated after cart changes.
     */
    Alpine.store('pos', {
        ...productStore,
        ...cartStore,

        get subtotal() {
            const s = this.cart.reduce((sum, item) => sum + this.itemTotal(item), 0);
            return Number.isFinite(s) ? s : 0;
        },

        get totalDiscount() {
            const lineDiscount = this.cart.reduce(
                (sum, item) => sum + (Number.isFinite(this.lineDiscountGross(item)) ? this.lineDiscountGross(item) : 0),
                0
            );
            return lineDiscount + this.orderDiscountGross();
        },

        get tax() {
            const sub = posFinite(this.subtotal, 0);
            const od = this.orderDiscountGross();
            const rate = posFinite(this.taxRate, 0) / 100;
            const taxable = sub - od;
            const t = taxable * rate;
            return Number.isFinite(t) ? t : 0;
        },

        get total() {
            const sub = posFinite(this.subtotal, 0);
            const od = this.orderDiscountGross();
            const tx = posFinite(this.tax, 0);
            const t = sub - od + tx;
            return Number.isFinite(t) ? Math.max(0, t) : 0;
        },

        paymentMethod: 'cash',
        amountTendered: 0,
        processing: false,
        _paymentLock: false,
        lastSaleId: 0,
        lastChangeDue: 0,
        /** Snapshot for success modal after cart cleared */
        lastSaleChangeDue: 0,
        lastReceiptPreview: null,
        heldOrders: [],
        shiftId: null,
        shiftOpenFloat: 0,
        shiftCloseFloat: 0,
        shiftNotes: '',
        taxRate: 0,
        newCustomers: [],
        newCustomer: { name: '', phone: '', email: '', error: '' },
        activeProduct: null,
        activeProductVariants: [],
        currencySymbol: '$',
        tenantName: '',
        cashierName: '',
        customers: [],
        routes: {},
        csrfToken: '',
        online: typeof navigator !== 'undefined' ? navigator.onLine : true,
        pendingSyncCount: 0,
        queueStatsPending: 0,
        queueStatsFailed: 0,
        offlineQueueRows: [],
        posShowShortcuts: false,
        _paymentIdempotencyKey: null,
        splitRows: [
            { method: 'cash', amount: '' },
            { method: 'card', amount: '' },
        ],
        _customerDisplayTimer: null,
        _cartDraftTimer: null,
        _eventHooksBound: false,
        _offlineQueueBusBound: false,
        _syncTimer: null,
        _realtimeInventoryBound: false,
        _syncInFlight: false,

        keyboardProductIndex: 0,
        /** Client-side catalog UI pagination (slices filteredProducts). */
        catalogUiPage: 1,
        catalogPageSize: 12,
        _catalogResizeTimer: null,
        _catalogResizeBound: false,
        detailModalQty: 1,
        detailModalDiscountPct: 0,
        detailModalDiscountAmount: 0,
        detailModalDiscountMode: 'pct',
        posTheme: 'light',
        customerSearchQuery: '',
        customerSearchResults: [],
        detailModalNotes: '',
        detailSelectedVariantId: null,
        cartFocusedIndex: 0,
        cartExpandedIndex: null,
        recentFlashProductId: null,
        _cartPulseIndex: -1,
        _recentFlashTimer: null,
        _cartPulseTimer: null,

        get paymentBlocked() {
            return Boolean(this._paymentLock);
        },

        get splitEnteredTotal() {
            return this.splitRows.reduce((s, r) => s + posFinite(parseFloat(r.amount), 0), 0);
        },

        get splitRemainingAmount() {
            const tot = posFinite(this.total, 0);
            const ent = posFinite(this.splitEnteredTotal, 0);
            const r = tot - ent;
            return Number.isFinite(r) ? r : 0;
        },

        /** Safe formatted currency for templates (invalid → symbol + —). */
        moneyLabel(value) {
            const x = posFiniteOrNaN(value);
            if (!Number.isFinite(x)) return this.currencySymbol + '\u2014';
            return this.currencySymbol + x.toFixed(2);
        },

        /** Product image URL — relative /storage paths follow the current host:port (Docker, 127.0.0.1, etc.). */
        productImageUrl(url) {
            if (!url) return '';
            if (String(url).startsWith('data:')) return url;
            try {
                if (/^https?:\/\//i.test(url)) {
                    return new URL(url).pathname;
                }
            } catch (_) { /* fall through */ }
            const path = String(url).replace(/\\/g, '/');
            if (path.startsWith('/')) return path;
            return '/storage/' + path.replace(/^\/+/, '');
        },

        /** Compact thumbnail initials when no product image (catalog tiles). */
        initialsFromName(name) {
            const n = String(name || '').trim();
            if (!n) return '?';
            const parts = n.split(/\s+/).filter(Boolean);
            if (parts.length >= 2) {
                return (parts[0][0] + parts[1][0]).toUpperCase();
            }
            return n.slice(0, 2).toUpperCase();
        },

        discountLabel(value) {
            return '\u2212 ' + this.moneyLabel(value);
        },

        changeDueDisplay() {
            return this.moneyLabel(Math.max(0, posFinite(this.changeDue, 0)));
        },

        get taxRateDisplay() {
            const t = posFinite(this.taxRate, 0);
            return Number.isFinite(t) ? String(t) : '0';
        },

        lineDiscountGross(item) {
            const price = posFinite(item?.price, 0);
            const qty = posFinite(item?.qty, 0);
            const gross = price * qty;
            const mode = item?.discount_mode === 'amount' ? 'amount' : 'pct';
            if (mode === 'amount') {
                const amt = Math.min(gross, Math.max(0, posFinite(item?.discount_amount, 0)));
                return Number.isFinite(amt) ? amt : NaN;
            }
            const disc = posFinite(item?.discount_pct, 0);
            const v = (gross * disc) / 100;
            return Number.isFinite(v) ? v : NaN;
        },

        setLineDiscountMode(idx, mode) {
            const item = this.cart[idx];
            if (!item) return;
            item.discount_mode = mode === 'amount' ? 'amount' : 'pct';
            if (item.discount_mode === 'amount') {
                item.discount_pct = 0;
            } else {
                item.discount_amount = 0;
            }
        },

        orderDiscountGross() {
            const sub = posFinite(this.subtotal, 0);
            const val = posFinite(parseFloat(this.orderDiscount), 0);
            const mode = this.orderDiscountMode === 'pct' ? 'pct' : 'amount';
            if (mode === 'amount') {
                return Math.min(sub, Math.max(0, val));
            }
            const cappedPct = Math.min(100, Math.max(0, val));
            const v = (sub * cappedPct) / 100;
            return Number.isFinite(v) ? Math.min(sub, Math.max(0, v)) : 0;
        },

        setOrderDiscountMode(mode) {
            this.orderDiscountMode = mode === 'pct' ? 'pct' : 'amount';
            if (typeof this._persistCartDraft === 'function') this._persistCartDraft();
            if (typeof this._scheduleCustomerDisplayBroadcast === 'function') {
                this._scheduleCustomerDisplayBroadcast();
            }
        },

        recalcCatalogPageSize() {
            const viewport = document.getElementById('product-scroll-area');
            const grid = document.getElementById('pos-product-grid');
            if (!viewport || !grid) return;

            const cols = this._readCatalogGridCols();
            const card = grid.querySelector('.pos-catalog-card-modern:not(.pos-catalog-card-modern--skel)');
            const cardHeight = card ? card.getBoundingClientRect().height : 132;
            const gap = 12;
            const pagination = viewport.closest('.pos-catalog-grid-view')?.querySelector('.pos-catalog-pagination');
            const paginationH = pagination ? pagination.offsetHeight : 52;
            const avail = Math.max(200, viewport.clientHeight - paginationH - 8);
            const rows = Math.max(2, Math.floor(avail / (cardHeight + gap)));
            const next = Math.min(48, Math.max(12, cols * rows));

            if (next !== this.catalogPageSize) {
                const oldPage = this.catalogUiPage;
                this.catalogPageSize = next;
                const maxPage = Math.max(1, Math.ceil((this.filteredProducts?.length || 0) / next));
                this.catalogUiPage = Math.min(oldPage, maxPage);
            }
        },

        _bindCatalogResize() {
            if (this._catalogResizeBound) return;
            this._catalogResizeBound = true;
            const debounced = () => {
                clearTimeout(this._catalogResizeTimer);
                this._catalogResizeTimer = setTimeout(() => this.recalcCatalogPageSize(), 150);
            };
            window.addEventListener('resize', debounced);
            if (typeof ResizeObserver !== 'undefined') {
                const el = document.getElementById('product-scroll-area');
                if (el) {
                    this._catalogResizeObserver = new ResizeObserver(debounced);
                    this._catalogResizeObserver.observe(el);
                }
            }
            document.body.addEventListener('pos-app-rail-toggled', debounced);
        },

        _bindPosAppRailOverlay() {
            if (this._posAppRailOverlayBound) return;
            this._posAppRailOverlayBound = true;

            const backdrop = document.getElementById('pos-app-rail-backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', () => this.closePosAppRail());
            }

            document.querySelectorAll('[data-pos-rail-close]').forEach((btn) => {
                btn.addEventListener('click', () => this.closePosAppRail());
            });

            document.querySelectorAll('[data-pos-rail-toggle]').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.togglePosAppRail();
                });
            });
        },

        _bindPosAppCartSheet() {
            if (this._posAppCartSheetBound) return;
            this._posAppCartSheetBound = true;

            const backdrop = document.getElementById('pos-app-cart-backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', () => this.closePosAppCart());
            }

            document.querySelectorAll('[data-pos-cart-close]').forEach((btn) => {
                btn.addEventListener('click', () => this.closePosAppCart());
            });

            document.querySelectorAll('[data-pos-cart-toggle]').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.togglePosAppCart();
                });
            });
        },

        /** Stable receipt shape for Alpine templates — prevents null field errors */
        get receiptPreviewNormalized() {
            try {
                return Pos?.Contracts?.normalizeReceipt?.(this.lastReceiptPreview || {}) ?? {};
            } catch {
                return {};
            }
        },

        /** Cart lines exceed available catalog stock */
        get cartStockBlocked() {
            if (!this.cart.length) return false;
            return this.cart.some((line) => {
                const p = this.products.find((x) => x.id === line.id);
                const stock = parseFloat(p?.stock ?? line.stock ?? 0) || 0;
                return !p || line.qty > stock || (p.out_of_stock && line.qty > 0);
            });
        },

        revalidateCartAgainstCatalog() {
            this.cart.forEach((line) => {
                const p = this.products.find((x) => x.id === line.id);
                if (p) {
                    line.stock = parseFloat(p.stock) || 0;
                    if (line.qty > line.stock) line.qty = Math.max(0, line.stock);
                }
            });
            this.cart = this.cart.filter((line) => line.qty > 0);
        },

        /** Current page of products for grid + keyboard (client-side slice). */
        get catalogPagedProducts() {
            const list = this.filteredProducts || [];
            const size = Math.max(1, Math.floor(posFinite(this.catalogPageSize, 12)));
            const page = Math.max(1, Math.floor(posFinite(this.catalogUiPage, 1)));
            const start = (page - 1) * size;
            return list.slice(start, start + size);
        },

        /** Pages available from loaded rows (ceil). */
        get catalogTotalLoadedPages() {
            const list = this.filteredProducts || [];
            const size = Math.max(1, Math.floor(posFinite(this.catalogPageSize, 12)));
            return Math.max(1, Math.ceil(list.length / size));
        },

        selectSplitPayment() {
            this.paymentMethod = 'split';
            const t = parseFloat(this.total) || 0;
            const a = (t / 2).toFixed(2);
            const b = (t - parseFloat(a)).toFixed(2);
            this.splitRows = [
                { method: 'cash', amount: a },
                { method: 'card', amount: b },
            ];
            this.amountTendered = t.toFixed(2);
        },

        addSplitRow() {
            this.splitRows.push({ method: 'bank_transfer', amount: '' });
        },

        removeLastSplitRow() {
            if (this.splitRows.length > 2) this.splitRows.pop();
        },

        _scheduleCustomerDisplayBroadcast() {
            clearTimeout(this._customerDisplayTimer);
            this._customerDisplayTimer = setTimeout(() => this._broadcastCustomerDisplay(), 120);
        },

        _broadcastCustomerDisplay() {
            if (!window.Pos?.CustomerDisplayBridge) return;
            const lines = this.cart.map((i) => ({
                name: i.name,
                qty: i.qty,
                unitPrice: Number(i.price) || 0,
                lineTotal: this.itemTotal(i),
            }));
            Pos.CustomerDisplayBridge.broadcast({
                lines,
                subtotal: this.subtotal,
                tax: this.tax,
                orderDiscount: this.orderDiscountGross(),
                totalDiscount: this.totalDiscount,
                total: this.total,
                currencySymbol: this.currencySymbol,
                tenantName: this.tenantName,
                customerName: this._customerDisplayName(),
            });
        },

        _persistCartDraft() {
            clearTimeout(this._cartDraftTimer);
            this._cartDraftTimer = setTimeout(() => {
                try {
                    const draft = {
                        cart: this.cart,
                        orderDiscount: this.orderDiscount,
                        orderDiscountMode: this.orderDiscountMode,
                        notes: this.notes,
                        customerId: this.customerId,
                        savedAt: Date.now(),
                    };
                    localStorage.setItem(CART_DRAFT_KEY, JSON.stringify(draft));
                } catch {
                    /* ignore */
                }
            }, 200);
        },

        _restoreCartDraft() {
            try {
                const raw = localStorage.getItem(CART_DRAFT_KEY);
                if (!raw) return;
                const d = JSON.parse(raw);
                if (d.cart?.length && this.shiftId) {
                    this.cart = d.cart;
                    this.orderDiscount = d.orderDiscount || 0;
                    this.orderDiscountMode = d.orderDiscountMode === 'pct' ? 'pct' : 'amount';
                    this.notes = d.notes || '';
                    this.customerId = d.customerId || '';
                    if (window.Pos?.Telemetry) {
                        Pos.Telemetry.toast('Restored saved cart from this register.', 'info', 3500);
                    }
                }
            } catch {
                /* ignore */
            }
        },

        _bindConnectivity() {
            window.addEventListener('online', () => {
                this.online = true;
                if (window.Pos?.Telemetry) Pos.Telemetry.toast('Back online — syncing…', 'success');
                this.syncPendingSales();
            });
            window.addEventListener('offline', () => {
                this.online = false;
                if (window.Pos?.Telemetry) Pos.Telemetry.toast('Offline mode — sales will queue.', 'warning');
            });
            this.online = navigator.onLine;
            this._refreshQueueStats();
            if (window.Pos?.EventBus && !this._offlineQueueBusBound) {
                this._offlineQueueBusBound = true;
                Pos.EventBus.on('offline_queue_changed', () => {
                    this._refreshQueueStats();
                    this.refreshOfflineQueueRows();
                });
            }
            clearInterval(this._syncTimer);
            this._syncTimer = setInterval(() => this.syncPendingSales(), 45000);
        },

        _bindInventoryListeners() {
            if (this._eventHooksBound || !window.Pos?.EventBus) return;
            this._eventHooksBound = true;
            Pos.EventBus.on('inventory_cache_invalidate', () => {
                if (window.Pos?.Telemetry) Pos.Telemetry.toast('Refreshing catalog…', 'info', 2000);
                this.fetchProducts(true);
            });
            Pos.EventBus.on('inventory_local_adjust', ({ productId, delta, variantId }) => {
                const p = this.products.find((x) => x.id === productId);
                if (!p) return;
                const next = Math.max(0, (parseFloat(p.stock) || 0) + delta);
                p.stock = next;
                p.out_of_stock = next <= 0;
                p.low_stock = next > 0 && next <= (p.min_stock || 0) + 0.001;
            });
        },

        _startCatalogStaleWatch() {
            clearInterval(this._staleTimer);
            this._staleTimer = setInterval(() => {
                if (!this.lastLoadedAt) return;
                const age = Date.now() - this.lastLoadedAt;
                this.catalogStale = age > 120000;
            }, 15000);
        },

        buildSalePayload() {
            const items = this.cart.map((i) => ({
                id: i.id,
                qty: i.qty,
                price: i.price,
                discount_pct: i.discount_mode === 'amount' ? 0 : i.discount_pct || 0,
                discount_amount: i.discount_mode === 'amount' ? i.discount_amount || 0 : 0,
                discount_mode: i.discount_mode === 'amount' ? 'amount' : 'pct',
                variant_id: i.variant_id,
            }));
            let amount_tendered = posFinite(this.amountTendered, 0);
            if (this.paymentMethod !== 'cash') {
                amount_tendered = posFinite(this.total, 0);
            }
            const body = {
                items,
                payment_method: this.paymentMethod,
                discount: this.orderDiscountGross(),
                tax_rate: posFinite(this.taxRate, 0),
                amount_tendered,
                customer_id: this.customerId || null,
                shift_id: this.shiftId,
                notes: this.notes,
            };
            if (this._paymentIdempotencyKey) {
                body.client_idempotency_key = this._paymentIdempotencyKey;
            }
            if (this.paymentMethod === 'split') {
                body.split_tenders = this.splitRows.map((r) => ({
                    method: r.method,
                    amount: posFinite(parseFloat(r.amount), 0),
                }));
            }
            return body;
        },

        _customerDisplayName() {
            if (!this.customerId) return 'Walk-in';
            const id = String(this.customerId);
            const found = this.customers.find((c) => String(c.id) === id);
            if (found) return found.name || 'Customer';
            const nc = this.newCustomers.find((c) => String(c.id) === id);
            return nc?.name || 'Customer';
        },

        _refreshQueueStats() {
            if (!window.Pos?.OfflineQueue?.stats) return;
            const s = Pos.OfflineQueue.stats();
            this.pendingSyncCount = s.total;
            this.queueStatsPending = s.pending;
            this.queueStatsFailed = s.failed;
        },

        refreshOfflineQueueRows() {
            this.offlineQueueRows = window.Pos?.OfflineQueue?.loadAll?.() || [];
        },

        openOfflineQueueModal() {
            this.refreshOfflineQueueRows();
            posModal('#posOfflineQueueModal', 'show');
        },

        removeOfflineQueueRow(rowId) {
            Pos?.OfflineQueue?.removeById?.(rowId);
            this._refreshQueueStats();
            this.refreshOfflineQueueRows();
            Pos?.Telemetry?.toast('Removed from offline queue.', 'info');
        },

        async retryAllQueuedSales() {
            await this.syncPendingSales();
            this.refreshOfflineQueueRows();
            posModal('#posOfflineQueueModal', 'hide');
        },

        _applyLocalSaleStockAdjust() {
            this.cart.forEach((line) => {
                Pos?.ProductCache?.bumpStock?.(line.id, -line.qty, line.variant_id);
                const p = this.products.find((x) => x.id === line.id);
                if (p) {
                    const next = Math.max(0, (parseFloat(p.stock) || 0) - line.qty);
                    p.stock = next;
                    p.out_of_stock = next <= 0;
                }
            });
            Pos?.Realtime?.publish?.('inventory.changed', { terminal: 'local' });
        },

        _captureReceiptPreview(data) {
            const tenant =
                document.querySelector('[data-pos-tenant-name]')?.getAttribute('data-pos-tenant-name') ||
                this.tenantName ||
                '';
            const now = new Date();
            const raw = {
                tenant_name: tenant,
                sale_id: data?.sale_id ?? this.lastSaleId ?? null,
                sale_number: data?.sale_number ?? null,
                sale_date: now.toLocaleString(undefined, {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                }),
                cashier_name: this.cashierName || '',
                customer_name: this._customerDisplayName(),
                lines: this.cart.map((i) => ({
                    name: i.name,
                    qty: i.qty,
                    unit_price: this.moneyLabel(posFiniteOrNaN(i.price)),
                    total: this.moneyLabel(this.itemTotal(i)),
                })),
                subtotal: this.moneyLabel(this.subtotal),
                discount:
                    this.totalDiscount > 0
                        ? this.moneyLabel(this.totalDiscount)
                        : '',
                tax: this.moneyLabel(this.tax),
                total: this.moneyLabel(this.total),
                payment: this.paymentMethod,
                change: this.moneyLabel(
                    Math.max(0, posFinite(data?.change_due ?? this.changeDue, 0))
                ),
            };
            this.lastReceiptPreview = Pos?.Contracts?.normalizeReceipt?.(raw) ?? raw;
        },

        async syncPendingSales() {
            if (!navigator.onLine || !this.routes.storeSale || !window.Pos?.OfflineQueue) {
                this._refreshQueueStats();
                return;
            }
            if (this._syncInFlight) return;
            this._syncInFlight = true;
            const rows = Pos.OfflineQueue.loadAll();
            if (!rows.length) {
                this._syncInFlight = false;
                this._refreshQueueStats();
                return;
            }
            const now = Date.now();
            for (const row of rows) {
                const na = row.nextAttemptAt ? Number(row.nextAttemptAt) : 0;
                if (na && now < na) continue;
                try {
                    const data = await this.apiCall(this.routes.storeSale, {
                        body: JSON.stringify(row.payload),
                    });
                    if (data?.success) {
                        Pos.OfflineQueue.removeById(row.id);
                        if (data.duplicate) {
                            Pos?.Telemetry?.toast(
                                'Queued sale matched existing receipt (idempotent).',
                                'info'
                            );
                        } else {
                            Pos?.Telemetry?.toast('Queued sale synced successfully.', 'success');
                        }
                        Pos.EventBus.emit('sale_synced', { localId: row.id });
                        Pos?.Ops?.record?.('sync_ok', { localId: row.id });
                    }
                } catch (e) {
                    const msg =
                        (typeof e === 'object' && e?.error) ||
                        e?.message ||
                        String(e);
                    const attempts = (row.attempts || 0) + 1;
                    const delay = Math.min(120000, 400 * Math.pow(2, Math.min(attempts, 8)));
                    Pos.OfflineQueue.updateAttempts(row.id, {
                        attempts,
                        lastError: typeof msg === 'string' ? msg : JSON.stringify(msg),
                        nextAttemptAt: Date.now() + delay,
                    });
                    Pos?.Ops?.record?.('sync_fail', { id: row.id, attempts });
                    Pos?.Telemetry?.toast(
                        'Pending sale sync failed — will retry. ' +
                            (typeof msg === 'string' ? msg.slice(0, 120) : ''),
                        'warning',
                        5000
                    );
                }
            }
            this._syncInFlight = false;
            this._refreshQueueStats();
            this.refreshOfflineQueueRows();
        },

        _bindRemoteInventory() {
            if (this._realtimeInventoryBound || !window.Pos?.EventBus) return;
            this._realtimeInventoryBound = true;
            Pos.EventBus.on('inventory_remote_bulk', (payload) => {
                const updates = payload?.updates || [];
                let touched = false;
                updates.forEach((u) => {
                    const pid = u.product_id;
                    const qty = parseFloat(u.stock_quantity);
                    const prod = this.products.find((x) => x.id === pid);
                    if (prod && Number.isFinite(qty)) {
                        prod.stock = Math.max(0, qty);
                        prod.out_of_stock = prod.stock <= 0;
                        touched = true;
                    }
                });
                if (touched) {
                    Pos?.Telemetry?.toast('Stock updated from another register.', 'info', 3200);
                    this.revalidateCartAgainstCatalog();
                    Pos?.ProductCache?.invalidate?.('remote_bulk');
                }
            });
            Pos.EventBus.on('pos_sale_completed_remote', () => {
                Pos?.ProductCache?.invalidate?.('remote_sale');
            });
        },

        async initStore(config) {
            if (config) {
                this.products = config.products || [];
                this.heldOrders = config.heldOrders || [];
                this.categories = config.categories || [];
                this.shiftId = config.shiftId || null;
                this.taxRate = posFinite(config.taxRate, 0);
                this.currencySymbol = config.currencySymbol || '$';
                this.csrfToken = config.csrfToken || '';
                this.routes = config.routes || {};
                this.customers = config.customers || [];
                this.tenantName = config.tenantName || '';
                this.cashierName = config.cashierName || '';
            }
            if (window.Pos?.Telemetry) {
                Pos.Telemetry.mount('pos-toast-root');
            }
            this._bindConnectivity();
            this._bindInventoryListeners();
            this._bindRemoteInventory();
            this._restoreCartDraft();
            await this.fetchProducts(true);
            this._bindCatalogResize();
            this._bindPosAppRailOverlay();
            this._bindPosAppCartSheet();
            this._applyPosTheme();
            setTimeout(() => this.recalcCatalogPageSize(), 80);
            this._refreshQueueStats();
            this.syncPendingSales();
            this._bindPosBootstrapHooks();
            this._registerKeyboardShortcuts();
            this._startCatalogStaleWatch();
            this._scheduleCustomerDisplayBroadcast();
            this.refreshOfflineQueueRows();
        },

        _anyModalOpen() {
            return Boolean(document.querySelector('.modal.show'));
        },

        _closeTopBootstrapModal() {
            try {
                if (typeof window.jQuery === 'undefined' || !window.jQuery.fn?.modal) return;
                const $m = window.jQuery('.modal.show').last();
                if ($m.length) $m.modal('hide');
            } catch {
                /* ignore */
            }
        },

        _flashSearchScanOk() {
            const el = document.getElementById('pos-search');
            if (!el) return;
            el.classList.add('pos-scan-flash');
            setTimeout(() => el.classList.remove('pos-scan-flash'), 220);
        },

        _syncKeyboardCatalogIndex(reset) {
            if (reset && typeof this.catalogUiPage === 'number') {
                this.catalogUiPage = 1;
            }
            const pageList = this.catalogPagedProducts;
            const len = pageList.length;
            if (!len) {
                this.keyboardProductIndex = 0;
                return;
            }
            if (reset) this.keyboardProductIndex = 0;
            if (this.keyboardProductIndex >= len) this.keyboardProductIndex = len - 1;
            if (this.keyboardProductIndex < 0) this.keyboardProductIndex = 0;
            setTimeout(() => this._scrollCatalogCellIntoView(), 0);
        },

        _readCatalogGridCols() {
            const grid = document.getElementById('pos-product-grid');
            if (!grid) return 3;
            const gtc = window.getComputedStyle(grid).gridTemplateColumns || '';
            const repeat = /^repeat\s*\(\s*(\d+)/i.exec(gtc);
            if (repeat) {
                const n = parseInt(repeat[1], 10);
                return Number.isFinite(n) ? Math.max(1, n) : 3;
            }
            const parts = gtc.split(/\s+/).filter(Boolean).length;
            return Math.max(parts || 3, 1);
        },

        _scrollCatalogCellIntoView() {
            const idx = this.keyboardProductIndex;
            const el = document.querySelector(`[data-pos-prod-idx="${idx}"]`);
            if (el) el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        },

        _navigateProductGrid(direction) {
            const list = this.catalogPagedProducts;
            if (!list.length) return;
            const cols = this._readCatalogGridCols();
            const len = list.length;
            let idx = this.keyboardProductIndex;
            if (idx < 0) idx = 0;
            if (idx >= len) idx = len - 1;

            if (direction === 'ArrowRight') idx = Math.min(idx + 1, len - 1);
            else if (direction === 'ArrowLeft') idx = Math.max(idx - 1, 0);
            else if (direction === 'ArrowDown') idx = Math.min(idx + cols, len - 1);
            else if (direction === 'ArrowUp') idx = Math.max(idx - cols, 0);

            this.keyboardProductIndex = idx;
            this._scrollCatalogCellIntoView();
        },

        _keyboardAddFocusedProduct() {
            const list = this.catalogPagedProducts;
            if (!list.length) return;
            const idx = Math.min(this.keyboardProductIndex, list.length - 1);
            const p = list[idx];
            if (!p || p.out_of_stock) return;
            this.keyboardProductIndex = idx;
            if (typeof this.quickAddCatalogProduct === 'function') {
                this.quickAddCatalogProduct(p);
            } else {
                this.addToCart(p);
            }
        },

        /**
         * Card opens detail configurator. + button quick-adds (variants → detail).
         */
        openCatalogProductDetail(idx, product) {
            if (!product) return;
            if (!this.shiftId) {
                if (window.Pos?.Telemetry) Pos.Telemetry.toast('Open a shift first.', 'warning');
                else alert('Please open a shift first.');
                return;
            }
            if (product.out_of_stock) return;
            if (typeof idx === 'number') this.keyboardProductIndex = idx;
            this.activeProduct = product;
            this.activeProductVariants = product.variants || [];
            this.detailModalQty = 1;
            this.detailModalDiscountPct = 0;
            this.detailModalDiscountAmount = 0;
            this.detailModalDiscountMode = 'pct';
            this.detailModalNotes = '';
            if (product.has_variants && product.variants?.length > 0) {
                this.detailSelectedVariantId = product.variants[0].id;
            } else {
                this.detailSelectedVariantId = null;
            }
            posModal('#productDetailModal', 'show');
        },

        closeProductDetailModal() {
            posModal('#productDetailModal', 'hide');
        },

        quickAddCatalogProduct(product) {
            if (!product || product.out_of_stock) return;
            if (!this.shiftId) {
                if (window.Pos?.Telemetry) Pos.Telemetry.toast('Open a shift first.', 'warning');
                else alert('Please open a shift first.');
                return;
            }
            if (product.has_variants && product.variants?.length > 0) {
                this.openCatalogProductDetail(null, product);
                return;
            }
            this.addToCart(product);
        },

        async catalogNextPage() {
            const size = Math.max(1, Math.floor(posFinite(this.catalogPageSize, 12)));
            const nextStart = this.catalogUiPage * size;
            if (
                nextStart >= this.filteredProducts.length &&
                this.hasMoreProducts &&
                !this.loadingProducts &&
                this.routes.productsFeed
            ) {
                await this.fetchProducts(false);
            }
            const maxPage = Math.max(1, Math.ceil(this.filteredProducts.length / size));
            if (this.catalogUiPage < maxPage) {
                this.catalogUiPage++;
                this.keyboardProductIndex = 0;
            }
        },

        catalogPrevPage() {
            if (this.catalogUiPage > 1) {
                this.catalogUiPage--;
                this.keyboardProductIndex = 0;
            }
        },

        _addSimpleLineFromDetail(product, qty, discountOpts, notes) {
            const q = Math.max(1, Math.floor(posFinite(qty, 1)));
            const mode = discountOpts?.mode === 'amount' ? 'amount' : 'pct';
            const discPct = Math.min(100, Math.max(0, posFinite(discountOpts?.pct, 0)));
            const discAmt = Math.max(0, posFinite(discountOpts?.amount, 0));
            const note = typeof notes === 'string' ? notes.trim() : '';
            const existing = this.cart.find((item) => item.id === product.id && !item.variant_id);
            if (existing) {
                const nextQty = existing.qty + q;
                if (nextQty > product.stock) {
                    if (window.Pos?.Telemetry) Pos.Telemetry.toast('Stock limit reached.', 'warning');
                    else alert('Stock limit reached.');
                    existing.qty = product.stock;
                } else {
                    existing.qty = nextQty;
                }
                existing.discount_mode = mode;
                existing.discount_pct = mode === 'pct' ? discPct : 0;
                existing.discount_amount = mode === 'amount' ? discAmt : 0;
                existing.notes = note;
                const lineIdx = this.cart.indexOf(existing);
                if (typeof this.cartFocusedIndex === 'number') this.cartFocusedIndex = lineIdx;
                if (typeof this._pulseCartRow === 'function') this._pulseCartRow(lineIdx);
            } else {
                this.cart.push({
                    ...product,
                    qty: q,
                    discount_pct: mode === 'pct' ? discPct : 0,
                    discount_amount: mode === 'amount' ? discAmt : 0,
                    discount_mode: mode,
                    notes: note,
                    variant_id: null,
                });
                const lineIdx = this.cart.length - 1;
                if (typeof this.cartFocusedIndex === 'number') this.cartFocusedIndex = lineIdx;
                if (typeof this._pulseCartRow === 'function') this._pulseCartRow(lineIdx);
            }
            this.scrollToBottomCart();
            if (typeof this._scheduleCustomerDisplayBroadcast === 'function') {
                this._scheduleCustomerDisplayBroadcast();
            }
            if (typeof this._persistCartDraft === 'function') this._persistCartDraft();
        },

        _addVariantLineFromDetail(product, variant, qty, discountOpts, notes) {
            const q = Math.max(1, Math.floor(posFinite(qty, 1)));
            const mode = discountOpts?.mode === 'amount' ? 'amount' : 'pct';
            const discPct = Math.min(100, Math.max(0, posFinite(discountOpts?.pct, 0)));
            const discAmt = Math.max(0, posFinite(discountOpts?.amount, 0));
            const note = typeof notes === 'string' ? notes.trim() : '';
            const existing = this.cart.find(
                (item) => item.id === product.id && item.variant_id === variant.id
            );
            if (existing) {
                const nextQty = existing.qty + q;
                if (nextQty > product.stock) {
                    if (window.Pos?.Telemetry) Pos.Telemetry.toast('Stock limit reached.', 'warning');
                    else alert('Stock limit reached.');
                    existing.qty = product.stock;
                } else {
                    existing.qty = nextQty;
                }
                existing.discount_mode = mode;
                existing.discount_pct = mode === 'pct' ? discPct : 0;
                existing.discount_amount = mode === 'amount' ? discAmt : 0;
                existing.notes = note;
                const lineIdx = this.cart.indexOf(existing);
                if (typeof this.cartFocusedIndex === 'number') this.cartFocusedIndex = lineIdx;
                if (typeof this._pulseCartRow === 'function') this._pulseCartRow(lineIdx);
            } else {
                this.cart.push({
                    id: product.id,
                    name: `${product.name} - ${variant.name}`,
                    price: variant.price,
                    stock: product.stock,
                    qty: q,
                    discount_pct: mode === 'pct' ? discPct : 0,
                    discount_amount: mode === 'amount' ? discAmt : 0,
                    discount_mode: mode,
                    notes: note,
                    variant_id: variant.id,
                });
                const lineIdx = this.cart.length - 1;
                if (typeof this.cartFocusedIndex === 'number') this.cartFocusedIndex = lineIdx;
                if (typeof this._pulseCartRow === 'function') this._pulseCartRow(lineIdx);
            }
            this.scrollToBottomCart();
            if (typeof this._scheduleCustomerDisplayBroadcast === 'function') {
                this._scheduleCustomerDisplayBroadcast();
            }
            if (typeof this._persistCartDraft === 'function') this._persistCartDraft();
        },

        confirmProductDetailAdd() {
            const product = this.activeProduct;
            if (!product || !this.shiftId || product.out_of_stock) return;
            const maxStock = Math.max(0, Math.floor(posFinite(product.stock, 0)));
            const qtyRaw = Math.max(1, Math.floor(posFinite(this.detailModalQty, 1)));
            const qty = Math.min(maxStock || 1, qtyRaw);
            this.detailModalQty = qty;
            const discOpts = {
                mode: this.detailModalDiscountMode === 'amount' ? 'amount' : 'pct',
                pct: Math.min(100, Math.max(0, posFinite(this.detailModalDiscountPct, 0))),
                amount: Math.max(0, posFinite(this.detailModalDiscountAmount, 0)),
            };
            if (product.has_variants && product.variants?.length > 0) {
                const v =
                    product.variants.find((x) => x.id === this.detailSelectedVariantId) ||
                    product.variants[0];
                this._addVariantLineFromDetail(product, v, qty, discOpts, this.detailModalNotes);
            } else {
                this._addSimpleLineFromDetail(product, qty, discOpts, this.detailModalNotes);
            }
            posModal('#productDetailModal', 'hide');
            if (typeof this._flashCatalogProduct === 'function') this._flashCatalogProduct(product.id);
        },

        /** @deprecated Use openCatalogProductDetail / quickAddCatalogProduct */
        tapCatalogProduct(idx, product) {
            this.openCatalogProductDetail(idx, product);
        },

        _flashCatalogProduct(productId) {
            this.recentFlashProductId = productId;
            clearTimeout(this._recentFlashTimer);
            this._recentFlashTimer = setTimeout(() => {
                this.recentFlashProductId = null;
            }, 520);
        },

        _pulseCartRow(rowIdx) {
            if (rowIdx < 0) return;
            this._cartPulseIndex = rowIdx;
            clearTimeout(this._cartPulseTimer);
            this._cartPulseTimer = setTimeout(() => {
                this._cartPulseIndex = -1;
            }, 400);
        },

        _adjustFocusedCartQty(delta) {
            if (!this.cart.length || !this.shiftId) return;
            this._clampCartFocusIndex();
            this.updateQty(this.cartFocusedIndex, delta);
        },

        _removeFocusedCartRow() {
            if (!this.cart.length || !this.shiftId) return;
            this._clampCartFocusIndex();
            const idx = this.cartFocusedIndex;
            if (idx >= 0 && idx < this.cart.length) this.removeItem(idx);
        },

        _clampCartFocusIndex() {
            if (!this.cart.length) {
                this.cartFocusedIndex = 0;
                return;
            }
            if (this.cartFocusedIndex >= this.cart.length) {
                this.cartFocusedIndex = this.cart.length - 1;
            }
            if (this.cartFocusedIndex < 0) this.cartFocusedIndex = 0;
        },

        _bindPosBootstrapHooks() {
            if (typeof window.jQuery === 'undefined' || !window.jQuery.fn?.modal) return;

            window.jQuery('#checkoutModal').on('shown.bs.modal', () => {
                window.setTimeout(() => document.getElementById('checkoutPayCash')?.focus(), 100);
            });

            window.jQuery('#productDetailModal').on('shown.bs.modal', () => {
                window.setTimeout(() => document.getElementById('detailModalQty')?.focus(), 120);
            });

            window.jQuery('#successModal').on('shown.bs.modal', () => {
                window.setTimeout(() => {
                    const btn = document.querySelector(
                        '#successModal button.btn-primary[data-dismiss="modal"]'
                    );
                    if (btn) btn.focus();
                }, 200);
            });
        },

        _registerKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                const activeEl = document.activeElement;
                const searchEl = document.getElementById('pos-search');
                const tag = activeEl?.tagName || '';
                const isPosSearchInput = activeEl?.id === 'pos-search';

                const gridNavBlocked =
                    tag === 'TEXTAREA' ||
                    tag === 'SELECT' ||
                    (tag === 'INPUT' && !isPosSearchInput);

                const cartHotkeyBlocked =
                    tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';

                const searchFocused = activeEl === searchEl;

                if (e.key === 'F2') {
                    e.preventDefault();
                    if (searchEl) {
                        searchEl.focus();
                        searchEl.select();
                    }
                    return;
                }
                if (e.key === 'F4' && this.cart.length > 0 && this.shiftId) {
                    e.preventDefault();
                    posModal('#checkoutModal', 'show');
                    return;
                }
                if (e.key === 'F6') {
                    e.preventDefault();
                    posModal('#heldOrdersModal', 'show');
                    return;
                }

                if (e.key === 'Escape') {
                    if (document.body.classList.contains('pos-app-cart-open')) {
                        e.preventDefault();
                        this.closePosAppCart();
                        return;
                    }
                    if (document.body.classList.contains('pos-app-rail-open')) {
                        e.preventDefault();
                        this.closePosAppRail();
                        return;
                    }
                    if (this._anyModalOpen()) {
                        e.preventDefault();
                        this._closeTopBootstrapModal();
                        return;
                    }
                    if (searchFocused && this.searchQuery) {
                        e.preventDefault();
                        this.searchQuery = '';
                        this.fetchProducts(true);
                        return;
                    }
                    if (searchEl && document.body.contains(searchEl)) {
                        searchEl.focus();
                        searchEl.select();
                    }
                    return;
                }

                if (!this._anyModalOpen() && !cartHotkeyBlocked) {
                    const isPlus =
                        e.key === '+' || e.key === '=' || e.code === 'NumpadAdd';
                    const isMinus = e.key === '-' || e.code === 'NumpadSubtract';
                    if (isPlus || isMinus) {
                        e.preventDefault();
                        this._adjustFocusedCartQty(isPlus ? 1 : -1);
                        return;
                    }
                    if (e.key === 'Delete') {
                        e.preventDefault();
                        this._removeFocusedCartRow();
                        return;
                    }
                }

                if (!this._anyModalOpen() && !gridNavBlocked) {
                    const moveKeys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
                    if (moveKeys.includes(e.key)) {
                        e.preventDefault();
                        this._navigateProductGrid(e.key);
                        return;
                    }
                    if (e.key === 'Enter' && !searchFocused) {
                        e.preventDefault();
                        this._keyboardAddFocusedProduct();
                        return;
                    }
                }
            });
        },

        async apiCall(url, options = {}) {
            const defaultOptions = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
            };
            const t0 = typeof performance !== 'undefined' ? performance.now() : Date.now();
            try {
                const response = await fetch(url, { ...defaultOptions, ...options });
                const text = await response.text();
                let data = null;
                try {
                    data = JSON.parse(text);
                } catch {
                    /* ignore */
                }
                const ms =
                    (typeof performance !== 'undefined' ? performance.now() : Date.now()) - t0;
                Pos?.Ops?.record?.('api_latency', {
                    url: String(url).split('?')[0].slice(-48),
                    ms: Math.round(ms),
                    ok: response.ok,
                });
                if (!response.ok) throw data || { error: text || 'Server Error' };
                return data;
            } catch (err) {
                const ms =
                    (typeof performance !== 'undefined' ? performance.now() : Date.now()) - t0;
                Pos?.Ops?.record?.('api_error', {
                    url: String(url).split('?')[0].slice(-48),
                    ms: Math.round(ms),
                });
                console.error('API Error:', err);
                throw err;
            }
        },

        get changeDue() {
            const tendered = posFinite(this.amountTendered, 0);
            const due = posFinite(this.total, 0);
            const d = tendered - due;
            return Number.isFinite(d) ? d : 0;
        },

        get cashChangeStripState() {
            if (this.paymentMethod !== 'cash') return 'exact';
            const d = posFinite(this.changeDue, 0);
            if (d < -0.005) return 'short';
            if (d > 0.005) return 'change';
            return 'exact';
        },

        get cashChangeStripLabel() {
            return this.cashChangeStripState === 'short' ? 'Still due' : 'Change due';
        },

        get cashChangeStripAmountDisplay() {
            const d = posFinite(this.changeDue, 0);
            if (d < -0.005) return this.moneyLabel(Math.abs(d));
            return this.moneyLabel(Math.max(0, d));
        },

        addTendered(delta) {
            const d = posFinite(delta, 0);
            const cur = posFinite(this.amountTendered, 0);
            const next = cur + d;
            this.amountTendered = Number.isFinite(next) ? Math.round(next * 100) / 100 : cur;
        },

        resetCheckout() {
            this.paymentMethod = 'cash';
            this.amountTendered = posFinite(this.total, 0).toFixed(2);
            const t = parseFloat(this.total) || 0;
            const a = (t / 2).toFixed(2);
            const b = (t - parseFloat(a)).toFixed(2);
            this.splitRows = [
                { method: 'cash', amount: a },
                { method: 'card', amount: b },
            ];
        },

        async processPayment() {
            if (this.cart.length === 0) return;
            if (this.processing || this._paymentLock) return;
            if (this.paymentMethod === 'split') {
                if (Math.abs(this.splitRemainingAmount) > 0.02) {
                    Pos?.Telemetry?.toast('Split tender totals must match amount due.', 'warning');
                    return;
                }
            }

            this.revalidateCartAgainstCatalog();
            if (this.cartStockBlocked) {
                Pos?.Telemetry?.toast(
                    'Cart has stock issues — adjust quantities before checkout.',
                    'danger'
                );
                return;
            }

            this._paymentIdempotencyKey = Pos.uuid();
            const payload = this.buildSalePayload();

            const enqueueOffline = (reason) => {
                const id = Pos.uuid();
                const queued = Pos.OfflineQueue.enqueue({
                    id,
                    idempotencyKey: this._paymentIdempotencyKey,
                    payload: { ...payload },
                    createdAt: Date.now(),
                    attempts: 0,
                    status: 'pending',
                    reason,
                });
                if (!queued) {
                    Pos?.Telemetry?.toast('Duplicate queue prevented.', 'info');
                    return;
                }
                this._refreshQueueStats();
                this._captureReceiptPreview({});
                this.lastSaleChangeDue = 0;
                this._paymentLock = true;
                this.cart = [];
                this.orderDiscount = 0;
                this.orderDiscountMode = 'amount';
                this.notes = '';
                this.customerId = '';
                this.resetCheckout();
                if (typeof this.cartFocusedIndex === 'number') this.cartFocusedIndex = 0;
                this._persistCartDraft();
                this.closePosAppCart();
                posModal('#checkoutModal', 'hide');
                Pos?.Telemetry?.toast(
                    `${reason} — sale queued; accounting pending server confirmation.`,
                    'warning',
                    5200
                );
                this._paymentLock = false;
                this._scheduleCustomerDisplayBroadcast();
            };

            if (!navigator.onLine) {
                enqueueOffline('Offline');
                return;
            }

            this.processing = true;
            this._paymentLock = true;
            try {
                const data = await this.apiCall(this.routes.storeSale, {
                    body: JSON.stringify(payload),
                });
                if (data.success) {
                    this.lastSaleId = data.sale_id;
                    this.lastChangeDue = data.change_due ?? this.changeDue;
                    this.lastSaleChangeDue = Math.max(
                        0,
                        posFinite(data.change_due ?? this.changeDue, 0)
                    );
                    this._captureReceiptPreview(data);
                    if (!data.duplicate) {
                        this._applyLocalSaleStockAdjust();
                    }
                    Pos?.EventBus?.emit('sale_completed', {
                        saleId: data.sale_id,
                        saleNumber: data.sale_number,
                        duplicate: Boolean(data.duplicate),
                    });
                    posModal('#checkoutModal', 'hide');
                    this.closePosAppCart();
                    setTimeout(() => posModal('#successModal', 'show'), 400);
                    this.cart = [];
                    this.orderDiscount = 0;
                    this.orderDiscountMode = 'amount';
                    this.notes = '';
                    this.customerId = '';
                    try {
                        localStorage.removeItem(CART_DRAFT_KEY);
                    } catch {
                        /* ignore */
                    }
                    if (typeof this.cartFocusedIndex === 'number') this.cartFocusedIndex = 0;
                    this.resetCheckout();
                    this._scheduleCustomerDisplayBroadcast();
                    await this.fetchProducts(true);
                    if (data.duplicate) {
                        Pos?.Telemetry?.toast('This payment was already recorded (idempotent).', 'info');
                    }
                }
            } catch (err) {
                const msg = err?.error || err?.message || '';
                const offlineish =
                    !navigator.onLine ||
                    (typeof TypeError !== 'undefined' && err instanceof TypeError) ||
                    /network|fetch|Failed to fetch/i.test(String(msg));
                if (offlineish) {
                    enqueueOffline('Network error');
                } else {
                    Pos?.Telemetry?.toast(String(msg || 'Payment failed'), 'danger');
                }
            } finally {
                this.processing = false;
                this._paymentLock = false;
            }
        },

        printLastReceipt() {
            const saleId = this.lastSaleId;
            if (saleId) {
                const url = `/pos/sales/${saleId}/receipt?print=1`;
                const w = window.open(url, '_blank', 'noopener,noreferrer,width=400,height=720');
                if (w) return;
            }
            const el = document.getElementById('pos-receipt-print-mount');
            if (!el) return;
            const w = window.open('', '_blank', 'noopener,noreferrer');
            if (!w) return;
            w.document.write(
                `<!DOCTYPE html><html><head><title>Receipt</title><link rel="stylesheet" href="${window.location.origin}/css/pos/receipt.css" /></head><body onload="setTimeout(function(){window.print();},400)">${el.innerHTML}</body></html>`
            );
            w.document.close();
            w.focus();
        },

        _applyPosTheme() {
            let theme = 'light';
            try {
                theme = localStorage.getItem('posTheme') || '';
            } catch {
                /* ignore */
            }
            if (!theme) {
                theme =
                    window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
                        ? 'dark'
                        : 'light';
            }
            this.posTheme = theme === 'dark' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-pos-theme', this.posTheme);
            document.body.classList.toggle('pos-theme-dark', this.posTheme === 'dark');
        },

        togglePosTheme() {
            this.posTheme = this.posTheme === 'dark' ? 'light' : 'dark';
            try {
                localStorage.setItem('posTheme', this.posTheme);
            } catch {
                /* ignore */
            }
            document.documentElement.setAttribute('data-pos-theme', this.posTheme);
            document.body.classList.toggle('pos-theme-dark', this.posTheme === 'dark');
        },

        async searchCustomersDebounced() {
            const q = String(this.customerSearchQuery || '').trim();
            if (!this.routes.customerSearch) return;
            try {
                const params = q ? `?q=${encodeURIComponent(q)}` : '';
                const res = await fetch(`${this.routes.customerSearch}${params}`, {
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) return;
                const data = await res.json();
                this.customerSearchResults = data.data || [];
            } catch {
                /* ignore */
            }
        },

        selectCustomerFromSearch(customer) {
            if (!customer?.id) return;
            const exists = this.customers.find((c) => String(c.id) === String(customer.id));
            if (!exists) {
                this.customers.push(customer);
            }
            this.customerId = customer.id;
            this.customerSearchQuery = '';
            this.customerSearchResults = [];
        },

        async saveQuickCustomer() {
            if (!this.newCustomer.name) return;
            try {
                const data = await this.apiCall(this.routes.quickCustomer, {
                    body: JSON.stringify(this.newCustomer),
                });
                if (data.success) {
                    if (!this.customers.find((c) => String(c.id) === String(data.customer.id))) {
                        this.customers.push(data.customer);
                    }
                    this.customerId = data.customer.id;
                    posModal('#quickCustomerModal', 'hide');
                }
            } catch (err) {
                this.newCustomer.error = err.error || 'Error';
            }
        },

        async holdCurrentOrder() {
            if (this.cart.length === 0) return;
            const label = prompt('Label:') || '';
            try {
                const data = await this.apiCall(this.routes.holdOrder, {
                    body: JSON.stringify({ cart: this.cart, label }),
                });
                if (data.success) {
                    this.heldOrders.unshift(data.held);
                    this.cart = [];
                    Pos?.Telemetry?.toast('Order held.', 'success');
                    this._scheduleCustomerDisplayBroadcast();
                    this._persistCartDraft();
                }
            } catch (err) {
                Pos?.Telemetry?.toast('Error holding order.', 'danger');
            }
        },

        async resumeOrder(id) {
            try {
                const data = await this.apiCall(this.routes.resumeOrder.replace(':id', id), {
                    method: 'POST',
                });
                if (data.success) {
                    this.cart = data.cart;
                    if (typeof this.cartFocusedIndex === 'number') {
                        this.cartFocusedIndex = Math.max(0, this.cart.length - 1);
                    }
                    this.heldOrders = this.heldOrders.filter((h) => h.id !== id);
                    posModal('#heldOrdersModal', 'hide');
                    this._scheduleCustomerDisplayBroadcast();
                    this._persistCartDraft();
                }
            } catch (err) {
                Pos?.Telemetry?.toast('Error resuming order.', 'danger');
            }
        },

        async openShift() {
            try {
                const data = await this.apiCall(this.routes.openShift, {
                    body: JSON.stringify({ opening_float: this.shiftOpenFloat }),
                });
                if (data.success) window.location.reload();
            } catch (err) {
                Pos?.Telemetry?.toast('Error opening shift.', 'danger');
            }
        },

        async closeShift() {
            if (!confirm('Are you sure you want to close the register?')) return;
            try {
                const data = await this.apiCall(
                    this.routes.closeShift.replace(':id', this.shiftId),
                    {
                        body: JSON.stringify({
                            closing_float: this.shiftCloseFloat,
                            notes: this.shiftNotes,
                        }),
                    }
                );
                if (data.success) window.location.reload();
            } catch (err) {
                Pos?.Telemetry?.toast('Error closing shift.', 'danger');
            }
        },
    });
}

if (window.Alpine) {
    initPosTerminal();
} else {
    document.addEventListener('alpine:init', initPosTerminal);
}
