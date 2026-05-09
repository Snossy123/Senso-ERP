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

        async setCategory(categoryId) {
            this.selectedCategory = categoryId;
            await this.fetchProducts(true);
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
                this.cart.push({ ...product, qty: 1, discount_pct: 0, variant_id: null });
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

        updateQty(idx, delta) {
            const item = this.cart[idx];
            if (!item) return;
            const pulseAt = idx;
            const next = item.qty + delta;
            if (next <= 0) {
                this.cart.splice(idx, 1);
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
            this.orderDiscount = 0;
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
            const gross = item.price * item.qty;
            return gross - (gross * (item.discount_pct || 0)) / 100;
        },

        get subtotal() {
            return this.cart.reduce((sum, item) => sum + this.itemTotal(item), 0);
        },

        get totalDiscount() {
            const lineDiscount = this.cart.reduce(
                (sum, item) => sum + (item.price * item.qty * (item.discount_pct || 0)) / 100,
                0
            );
            return lineDiscount + (parseFloat(this.orderDiscount) || 0);
        },

        get tax() {
            return (
                (this.subtotal - (parseFloat(this.orderDiscount) || 0)) * (this.taxRate / 100)
            );
        },

        get total() {
            return Math.max(0, this.subtotal - (parseFloat(this.orderDiscount) || 0) + this.tax);
        },
    };

    Alpine.store('pos', {
        ...productStore,
        ...cartStore,
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
        cartFocusedIndex: 0,
        recentFlashProductId: null,
        _cartPulseIndex: -1,
        _recentFlashTimer: null,
        _cartPulseTimer: null,

        get paymentBlocked() {
            return Boolean(this._paymentLock);
        },

        get splitEnteredTotal() {
            return this.splitRows.reduce((s, r) => s + (parseFloat(r.amount) || 0), 0);
        },

        get splitRemainingAmount() {
            return (parseFloat(this.total) || 0) - this.splitEnteredTotal;
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
                orderDiscount: parseFloat(this.orderDiscount) || 0,
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
                discount_pct: i.discount_pct || 0,
                variant_id: i.variant_id,
            }));
            let amount_tendered = parseFloat(this.amountTendered) || 0;
            if (this.paymentMethod !== 'cash') {
                amount_tendered = this.total;
            }
            const body = {
                items,
                payment_method: this.paymentMethod,
                discount: this.orderDiscount,
                tax_rate: this.taxRate,
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
                    amount: parseFloat(r.amount) || 0,
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
                    unit_price: this.currencySymbol + Number(i.price).toFixed(2),
                    total: this.currencySymbol + this.itemTotal(i).toFixed(2),
                })),
                subtotal: this.currencySymbol + this.subtotal.toFixed(2),
                discount:
                    this.totalDiscount > 0
                        ? this.currencySymbol + this.totalDiscount.toFixed(2)
                        : '',
                tax: this.currencySymbol + this.tax.toFixed(2),
                total: this.currencySymbol + this.total.toFixed(2),
                payment: this.paymentMethod,
                change:
                    this.currencySymbol +
                    Math.max(0, parseFloat(data?.change_due ?? this.changeDue) || 0).toFixed(2),
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
                this.taxRate = config.taxRate || 0;
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
            const len = this.filteredProducts.length;
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
            const list = this.filteredProducts;
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
            const list = this.filteredProducts;
            if (!list.length) return;
            const idx = Math.min(this.keyboardProductIndex, list.length - 1);
            const p = list[idx];
            if (!p || p.out_of_stock) return;
            this.keyboardProductIndex = idx;
            this.addToCart(p);
        },

        tapCatalogProduct(idx, product) {
            if (typeof idx === 'number') this.keyboardProductIndex = idx;
            this.addToCart(product);
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
            const tendered = parseFloat(this.amountTendered) || 0;
            const due = parseFloat(this.total) || 0;
            return tendered - due;
        },

        resetCheckout() {
            this.paymentMethod = 'cash';
            this.amountTendered = this.total.toFixed(2);
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
                this.notes = '';
                this.customerId = '';
                this.resetCheckout();
                if (typeof this.cartFocusedIndex === 'number') this.cartFocusedIndex = 0;
                this._persistCartDraft();
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
                        parseFloat(data.change_due ?? this.changeDue) || 0
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
                    setTimeout(() => posModal('#successModal', 'show'), 400);
                    this.cart = [];
                    this.orderDiscount = 0;
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
            const el = document.getElementById('pos-receipt-print-mount');
            if (!el) return;
            const w = window.open('', '_blank', 'noopener,noreferrer');
            if (!w) return;
            w.document.write(
                `<!DOCTYPE html><html><head><title>Receipt</title><link rel="stylesheet" href="${window.location.origin}/css/pos/main.css" /></head><body>${el.innerHTML}</body></html>`
            );
            w.document.close();
            w.focus();
            w.print();
        },

        async saveQuickCustomer() {
            if (!this.newCustomer.name) return;
            try {
                const data = await this.apiCall(this.routes.quickCustomer, {
                    body: JSON.stringify(this.newCustomer),
                });
                if (data.success) {
                    this.newCustomers.push(data.customer);
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
