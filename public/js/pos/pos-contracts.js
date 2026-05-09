/**
 * POS payload contracts — stable shapes for receipt, customer display, cart snapshots.
 * All consumers should use Pos.Contracts.* (via Pos.normalizeSalePayload aliases).
 */
(function (global) {
    'use strict';

    function numOr(v, d) {
        const n = Number(v);
        return Number.isFinite(n) ? n : d;
    }

    function strOr(v, d) {
        if (v == null) return d;
        return String(v);
    }

    function normalizeReceiptLine(line) {
        const el = line && typeof line === 'object' ? line : {};
        return {
            name: strOr(el.name, ''),
            qty: Math.max(0, numOr(el.qty, 0)),
            unit_price: strOr(el.unit_price, ''),
            total: strOr(el.total, ''),
        };
    }

    function normalizeReceipt(raw) {
        const r = raw && typeof raw === 'object' ? raw : {};
        const lines = Array.isArray(r.lines) ? r.lines.map(normalizeReceiptLine) : [];
        return {
            tenant_name: strOr(r.tenant_name, ''),
            sale_id: r.sale_id != null ? r.sale_id : null,
            sale_number: r.sale_number != null ? strOr(r.sale_number, '') : null,
            sale_date: strOr(r.sale_date, ''),
            cashier_name: strOr(r.cashier_name, ''),
            customer_name: strOr(r.customer_name, ''),
            lines,
            subtotal: strOr(r.subtotal, '$0.00'),
            discount: r.discount != null && r.discount !== '' ? strOr(r.discount, '') : '',
            tax: strOr(r.tax, '$0.00'),
            total: strOr(r.total, '$0.00'),
            payment: strOr(r.payment, ''),
            change: strOr(r.change, '$0.00'),
        };
    }

    /** Alias requested for legacy naming */
    function normalizeSalePayload(raw) {
        return normalizeReceipt(raw);
    }

    function normalizeCartLine(line, currencySymbol) {
        const sym = currencySymbol || '$';
        const el = line && typeof line === 'object' ? line : {};
        const qty = Math.max(0, numOr(el.qty, 0));
        const price = numOr(el.price, 0);
        const disc = numOr(el.discount_pct, 0);
        const gross = price * qty;
        const lineTot = gross - (gross * disc) / 100;
        return {
            id: el.id != null ? el.id : null,
            name: strOr(el.name, ''),
            price,
            qty,
            stock: numOr(el.stock, 0),
            discount_pct: disc,
            variant_id: el.variant_id != null ? el.variant_id : null,
            unit_price_label: sym + price.toFixed(2),
            line_total_label: sym + lineTot.toFixed(2),
        };
    }

    function normalizeCart(ctx) {
        const c = ctx && typeof ctx === 'object' ? ctx : {};
        const sym = strOr(c.currencySymbol, '$');
        const rows = Array.isArray(c.cart) ? c.cart.map((l) => normalizeCartLine(l, sym)) : [];
        return {
            currencySymbol: sym,
            cart: rows,
            taxRate: numOr(c.taxRate, 0),
            orderDiscount: numOr(c.orderDiscount, 0),
        };
    }

    function normalizeCustomerDisplay(raw) {
        const s = raw && typeof raw === 'object' ? raw : {};
        const linesIn = Array.isArray(s.lines) ? s.lines : [];
        const lines = linesIn.map((line) => {
            const el = line && typeof line === 'object' ? line : {};
            return {
                name: strOr(el.name, ''),
                qty: Math.max(0, numOr(el.qty, 0)),
                unitPrice: numOr(el.unitPrice ?? el.unit_price, 0),
                lineTotal: numOr(el.lineTotal ?? el.line_total, 0),
            };
        });
        return {
            tenantName: strOr(s.tenantName ?? s.tenant_name, ''),
            currencySymbol: strOr(s.currencySymbol, '$'),
            customerName: strOr(s.customerName ?? s.customer_name, ''),
            lines,
            subtotal: numOr(s.subtotal, 0),
            tax: numOr(s.tax, 0),
            orderDiscount: numOr(s.orderDiscount, 0),
            totalDiscount: numOr(s.totalDiscount, 0),
            total: numOr(s.total, 0),
            ts: numOr(s.ts, Date.now()),
        };
    }

    function normalizeSale(raw) {
        const r = raw && typeof raw === 'object' ? raw : {};
        return {
            success: Boolean(r.success),
            duplicate: Boolean(r.duplicate),
            sale_id: r.sale_id != null ? r.sale_id : null,
            sale_number: r.sale_number != null ? strOr(r.sale_number, '') : null,
            change_due: r.change_due != null ? numOr(r.change_due, 0) : null,
        };
    }

    const Contracts = {
        normalizeReceipt,
        normalizeSalePayload,
        normalizeCart,
        normalizeCustomerDisplay,
        normalizeSale,
        normalizeReceiptLine,
    };

    const PosRoot = global.Pos || {};
    PosRoot.Contracts = Contracts;
    PosRoot.normalizeSalePayload = normalizeSalePayload;
    global.Pos = PosRoot;
})(typeof window !== 'undefined' ? window : globalThis);
