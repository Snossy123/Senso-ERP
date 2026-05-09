/**
 * POS Phase 3 runtime — Event bus, offline queue, realtime adapter hooks,
 * customer-display bridge, telemetry toasts. No backend coupling.
 */
(function (global) {
    'use strict';

    function uuid() {
        if (global.crypto?.randomUUID) return crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = (Math.random() * 16) | 0;
            const v = c === 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    }

    const EventBus = {
        _handlers: Object.create(null),
        on(type, fn) {
            if (!this._handlers[type]) this._handlers[type] = [];
            this._handlers[type].push(fn);
            return () => this.off(type, fn);
        },
        off(type, fn) {
            const list = this._handlers[type];
            if (!list) return;
            const i = list.indexOf(fn);
            if (i !== -1) list.splice(i, 1);
        },
        emit(type, payload) {
            const list = this._handlers[type];
            if (!list) return;
            list.slice().forEach((fn) => {
                try {
                    fn(payload);
                } catch (e) {
                    console.warn('[POS EventBus]', type, e);
                }
            });
        },
        emitAsync(type, payload) {
            return Promise.resolve().then(() => this.emit(type, payload));
        },
    };

    const STORAGE_PENDING = 'pos_pending_sales_v5';
    const LEGACY_KEYS = ['pos_pending_sales_v3', 'pos_pending_sales_v4'];

    const Ops = {
        _metrics: [],
        max: 200,
        record(type, data) {
            try {
                this._metrics.push({ type, data, at: Date.now() });
                if (this._metrics.length > this.max) this._metrics.shift();
                EventBus.emit('ops_metric', { type, data });
            } catch {
                /* ignore */
            }
        },
        snapshot() {
            return this._metrics.slice();
        },
    };

    const OfflineQueue = {
        loadAll() {
            try {
                let raw = localStorage.getItem(STORAGE_PENDING);
                if (!raw) {
                    for (let i = 0; i < LEGACY_KEYS.length; i++) {
                        const leg = localStorage.getItem(LEGACY_KEYS[i]);
                        if (leg) {
                            raw = leg;
                            localStorage.setItem(STORAGE_PENDING, leg);
                            break;
                        }
                    }
                }
                const rows = raw ? JSON.parse(raw) : [];
                return rows.map((r) => ({
                    schemaVersion: 5,
                    nextAttemptAt: 0,
                    ...r,
                }));
            } catch {
                return [];
            }
        },
        saveAll(rows) {
            try {
                localStorage.setItem(STORAGE_PENDING, JSON.stringify(rows));
            } catch (e) {
                EventBus.emit('offline_queue_corrupt', { error: String(e) });
                console.warn('[POS OfflineQueue] save failed', e);
            }
            EventBus.emit('offline_queue_changed', { count: rows.length });
        },
        enqueue(record) {
            const rows = this.loadAll();
            const exists = rows.some((r) => r.idempotencyKey === record.idempotencyKey);
            if (exists) return false;
            const ck = record.payload?.client_idempotency_key;
            if (ck && rows.some((r) => r.payload?.client_idempotency_key === ck)) return false;
            rows.push({
                schemaVersion: 5,
                nextAttemptAt: 0,
                ...record,
            });
            this.saveAll(rows);
            return true;
        },
        removeById(id) {
            const rows = this.loadAll().filter((r) => r.id !== id);
            this.saveAll(rows);
        },
        updateAttempts(id, patch) {
            const rows = this.loadAll().map((r) => (r.id === id ? { ...r, ...patch } : r));
            this.saveAll(rows);
        },
        count() {
            return this.loadAll().length;
        },
        /** Pending = never failed sync; failed = has attempts + lastError */
        stats() {
            const rows = this.loadAll();
            const failed = rows.filter((r) => (r.attempts || 0) > 0 && r.lastError).length;
            const pending = rows.length - failed;
            return { total: rows.length, pending, failed };
        },
        /** Dedupe by client_idempotency_key inside queued payloads */
        hasClientKey(clientKey) {
            if (!clientKey) return false;
            return this.loadAll().some((r) => r.payload?.client_idempotency_key === clientKey);
        },
    };

    /**
     * Transport plugin shape: { connect(config), disconnect(), publish(topic,payload), subscribe(topic,cb) }
     * Default: no-op until backend registers Pos.Realtime.registerTransport(factory).
     */
    const Realtime = {
        _transport: null,
        _subs: [],
        registerTransport(factoryFn) {
            if (typeof factoryFn !== 'function') return;
            try {
                this._transport = factoryFn({ EventBus });
                EventBus.emit('realtime_transport_registered', {});
            } catch (e) {
                console.warn('[POS Realtime] transport init failed', e);
            }
        },
        publish(topic, payload) {
            try {
                this._transport?.publish?.(topic, payload);
            } catch (e) {
                console.warn('[POS Realtime] publish', e);
            }
            EventBus.emit('realtime_publish', { topic, payload });
        },
        subscribe(topic, cb) {
            try {
                return this._transport?.subscribe?.(topic, cb);
            } catch (e) {
                console.warn('[POS Realtime] subscribe', e);
            }
            return () => {};
        },
    };

    const Telemetry = {
        _toastEl: null,
        mount(containerId) {
            const root = document.getElementById(containerId || 'pos-toast-root');
            if (!root) return;
            this._toastEl = root;
        },
        toast(message, variant = 'info', ttlMs = 3800) {
            EventBus.emit('telemetry_toast', { message, variant });
            const host = this._toastEl || document.getElementById('pos-toast-root');
            if (!host) return;
            const div = document.createElement('div');
            div.className = `pos-toast pos-toast--${variant}`;
            div.setAttribute('role', 'status');
            div.textContent = message;
            host.appendChild(div);
            requestAnimationFrame(() => div.classList.add('pos-toast--show'));
            setTimeout(() => {
                div.classList.remove('pos-toast--show');
                setTimeout(() => div.remove(), 320);
            }, ttlMs);
        },
    };

    const CHANNEL_NAME = 'pos-customer-display-v1';
    const STORAGE_CD = 'pos_customer_display_snapshot_v1';

    const CustomerDisplayBridge = {
        _bc: null,
        init() {
            try {
                if (typeof BroadcastChannel !== 'undefined') {
                    this._bc = new BroadcastChannel(CHANNEL_NAME);
                }
            } catch {
                this._bc = null;
            }
        },
        broadcast(snapshot) {
            let normalized = snapshot;
            try {
                if (global.Pos?.Contracts?.normalizeCustomerDisplay) {
                    normalized = Pos.Contracts.normalizeCustomerDisplay(snapshot);
                }
            } catch (e) {
                console.warn('[POS] normalizeCustomerDisplay', e);
            }
            const payload = { ...normalized, ts: Date.now() };
            try {
                localStorage.setItem(STORAGE_CD, JSON.stringify(payload));
            } catch {
                /* ignore quota */
            }
            try {
                this._bc?.postMessage(payload);
            } catch {
                /* ignore */
            }
            window.dispatchEvent(new CustomEvent('pos_customer_display_push', { detail: payload }));
        },
        subscribe(cb) {
            const wrap = (data) => {
                try {
                    const n = global.Pos?.Contracts?.normalizeCustomerDisplay?.(data);
                    cb(n != null ? n : data);
                } catch {
                    cb(data);
                }
            };
            if (this._bc) {
                this._bc.onmessage = (ev) => wrap(ev.data);
            }
            const onStorage = (e) => {
                if (e.key !== STORAGE_CD || !e.newValue) return;
                try {
                    wrap(JSON.parse(e.newValue));
                } catch {
                    /* ignore */
                }
            };
            /** Same-window updates (BroadcastChannel + storage skip the active tab). */
            const onWinPush = (ev) => {
                try {
                    if (ev?.detail) wrap(ev.detail);
                } catch {
                    /* ignore */
                }
            };
            window.addEventListener('storage', onStorage);
            window.addEventListener('pos_customer_display_push', onWinPush);
            return () => {
                window.removeEventListener('storage', onStorage);
                window.removeEventListener('pos_customer_display_push', onWinPush);
                if (this._bc) this._bc.onmessage = null;
            };
        },
    };

    CustomerDisplayBridge.init();

    const ProductCache = {
        /** Mark client-side stale — POS store listens and refetches */
        invalidate(reason) {
            EventBus.emit('inventory_cache_invalidate', { reason, at: Date.now() });
        },
        bumpStock(productId, delta, variantId = null) {
            EventBus.emit('inventory_local_adjust', { productId, delta, variantId });
        },
    };

    global.Pos = {
        uuid,
        EventBus,
        OfflineQueue,
        Realtime,
        Telemetry,
        CustomerDisplayBridge,
        ProductCache,
        Ops,
        STORAGE_KEYS: { pending: STORAGE_PENDING, customerDisplay: STORAGE_CD },
    };
})(typeof window !== 'undefined' ? window : globalThis);
