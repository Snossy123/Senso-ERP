@extends('layouts.master')

@section('page-header')
<div class="pos-sale-show__header breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex flex-wrap align-items-center">
            <h4 class="content-title mb-0 my-auto">POS</h4>
            <span class="text-muted tx-13">/ {{ __('messages.sidebar.sales_history') }}</span>
            <span class="pos-sale-show__badge">{{ $sale->sale_number }}</span>
        </div>
    </div>
    <div class="pos-sale-show__actions right-content">
        <a href="{{ route('pos.sales.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="fe fe-arrow-left me-1"></i> Back
        </a>
        <button type="button" onclick="window.print()" class="btn btn-primary btn-sm rounded-pill px-3">
            <i class="fe fe-printer me-1"></i> Print
        </button>
        @if(!$sale->isVoided() && $sale->items->sum(fn ($i) => $i->refundableQty()) > 0)
            <button type="button" class="btn btn-warning btn-sm rounded-pill px-3" onclick="$('#refundModal').modal('show')">
                <i class="fe fe-rotate-ccw me-1"></i> Return
            </button>
            <button type="button" class="btn btn-danger btn-sm rounded-pill px-3" onclick="$('#voidModal').modal('show')">
                <i class="fe fe-slash me-1"></i> Void
            </button>
        @endif
    </div>
</div>
@endsection

@section('css')
<link href="{{ asset('css/pos/sale-show.css') }}?v=1" rel="stylesheet">
@endsection

@section('content')
@php
    $currency = config('app.currency');
    $tendered = $sale->payment_method === 'cash'
        ? (float) (($sale->amount_tendered ?? 0) > 0 ? $sale->amount_tendered : $sale->total)
        : (float) ($sale->amount_tendered ?? 0);
    $changeDue = (float) ($sale->change_due ?? 0);
@endphp

<div class="pos-sale-show">
    <div class="pos-sale-show__layout">
        <div class="pos-sale-show__main">
            <section class="pos-sale-show__panel">
                <div class="pos-sale-show__panel-head">
                    <div>
                        <h5>{{ $sale->sale_number }}</h5>
                        <div class="pos-sale-show__meta">
                            <span><i class="fe fe-calendar me-1"></i>{{ $sale->created_at->format('M d, Y · H:i') }}</span>
                            <span><i class="fe fe-user me-1"></i>{{ $sale->user?->name ?? '—' }}</span>
                        </div>
                    </div>
                    <span class="pos-sale-show__status pos-sale-show__status--{{ $sale->status }}">{{ $sale->status }}</span>
                </div>

                <div class="pos-sale-show__table-wrap">
                    <table class="pos-sale-show__table">
                        <thead>
                            <tr>
                                <th class="col-product">Product</th>
                                <th class="col-qty">Qty</th>
                                <th class="col-money">Unit</th>
                                <th class="col-money">Disc</th>
                                <th class="col-total">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->items as $item)
                                <tr>
                                    <td class="col-product">
                                        <span class="pos-sale-show__product-name">{{ $item->product?->name ?: 'Unavailable SKU' }}</span>
                                        @if($item->product_variant_id)
                                            <br><small class="text-muted">Variant #{{ $item->product_variant_id }}</small>
                                        @endif
                                    </td>
                                    <td class="col-qty">{{ $item->quantity }}</td>
                                    <td class="col-money">{{ $currency }} {{ number_format($item->unit_price, 2) }}</td>
                                    <td class="col-money text-muted">
                                        @if($item->discount_pct > 0)
                                            −{{ $currency }} {{ number_format($item->discount_amount, 2) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="col-total">{{ $currency }} {{ number_format($item->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            @if($sale->refunds->count())
                <h6 class="pos-sale-show__section-title"><i class="fe fe-repeat me-1 text-warning"></i> Refunds</h6>
                @foreach($sale->refunds as $refund)
                    <div class="pos-sale-show__refund">
                        <div class="pos-sale-show__refund-head">
                            <span class="font-weight-bold">{{ $refund->refund_number }}</span>
                            <span class="font-weight-bold text-warning">{{ $currency }} {{ number_format($refund->amount, 2) }}</span>
                        </div>
                        <small class="text-muted d-block">{{ $refund->created_at->format('M d · H:i') }} · {{ $refund->user?->name }} · {{ ucfirst($refund->method) }}</small>
                        @if($refund->reason)
                            <p class="mb-0 mt-2 small">{{ $refund->reason }}</p>
                        @endif
                    </div>
                @endforeach
            @endif

            @if($sale->isVoided())
                <div class="pos-sale-show__void-alert">
                    <strong>Voided</strong> — {{ $sale->void_reason }}
                    · {{ $sale->voidedBy?->name ?? '—' }}
                    · {{ optional($sale->voided_at)->format('M d · H:i') }}
                </div>
            @endif
        </div>

        <aside class="pos-sale-show__aside">
            <div class="pos-sale-show__totals">
                <div class="pos-sale-show__totals-title">Payment summary</div>
                <div class="pos-sale-show__row">
                    <span class="pos-sale-show__row-label">Subtotal</span>
                    <span class="pos-sale-show__row-value">{{ $currency }} {{ number_format($sale->subtotal, 2) }}</span>
                </div>
                @if($sale->discount_amount > 0)
                    <div class="pos-sale-show__row pos-sale-show__row--discount">
                        <span class="pos-sale-show__row-label">Discount</span>
                        <span class="pos-sale-show__row-value">− {{ $currency }} {{ number_format($sale->discount_amount, 2) }}</span>
                    </div>
                @endif
                @if($sale->tax_amount > 0)
                    <div class="pos-sale-show__row">
                        <span class="pos-sale-show__row-label">Tax</span>
                        <span class="pos-sale-show__row-value">{{ $currency }} {{ number_format($sale->tax_amount, 2) }}</span>
                    </div>
                @endif
                <div class="pos-sale-show__grand">
                    <span class="pos-sale-show__grand-label">Total</span>
                    <span class="pos-sale-show__grand-value">{{ $currency }} {{ number_format($sale->total, 2) }}</span>
                </div>
                @if($sale->payment_method === 'cash')
                    <div class="pos-sale-show__cash-extra">
                        <div class="pos-sale-show__row">
                            <span class="pos-sale-show__row-label">Tendered</span>
                            <span class="pos-sale-show__row-value">{{ $currency }} {{ number_format($tendered, 2) }}</span>
                        </div>
                        <div class="pos-sale-show__row">
                            <span class="pos-sale-show__row-label">Change due</span>
                            <span class="pos-sale-show__row-value">{{ $currency }} {{ number_format($changeDue, 2) }}</span>
                        </div>
                    </div>
                @endif
            </div>

            <div class="pos-sale-show__meta-card">
                <div class="pos-sale-show__totals-title">Details</div>
                <div class="pos-sale-show__meta-row">
                    <span class="pos-sale-show__meta-label">Customer</span>
                    <span class="pos-sale-show__meta-value">{{ $sale->customer?->name ?? 'Walk-in' }}</span>
                </div>
                <div class="pos-sale-show__meta-row">
                    <span class="pos-sale-show__meta-label">Tender</span>
                    <span class="pos-sale-show__meta-value">{{ ucwords(str_replace('_', ' ', $sale->payment_method)) }}</span>
                </div>
                <div class="pos-sale-show__meta-row">
                    <span class="pos-sale-show__meta-label">Shift</span>
                    <span class="pos-sale-show__meta-value">
                        @if($sale->shift)
                            {{ $sale->shift->terminal_id }} · {{ $sale->shift->opened_at->format('M d, H:i') }}
                        @else
                            —
                        @endif
                    </span>
                </div>
            </div>
        </aside>
    </div>
</div>

@if(!$sale->isVoided() && $sale->items->sum(fn ($i) => $i->refundableQty()) > 0)
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-xl shadow-xl">
            <div class="modal-header bg-warning text-white border-0">
                <h5 class="modal-title mb-0">Return items — {{ $sale->sale_number }}</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body px-4 py-4 bg-light">
                <table class="table table-sm bg-white rounded-lg mb-3">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Product</th>
                            <th class="text-center">Sold</th>
                            <th class="text-center">Return</th>
                            <th class="text-end">Est.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $item)
                            @php
                                $max = $item->refundableQty();
                                $unit = $item->quantity > 0 ? $item->total / $item->quantity : $item->unit_price;
                            @endphp
                            @if($max > 0)
                                <tr data-sale-item-id="{{ $item->id }}" data-unit-price="{{ $unit }}">
                                    <td><input type="checkbox" class="refund-line-check" data-max="{{ $max }}"></td>
                                    <td>{{ $item->product?->name ?? 'Item' }}</td>
                                    <td class="text-center">{{ $item->quantity }} ({{ $max }} left)</td>
                                    <td class="text-center">
                                        <input type="number" class="form-control form-control-sm refund-line-qty text-center" min="0" max="{{ $max }}" value="0" disabled>
                                    </td>
                                    <td class="text-end refund-line-est">{{ $currency }} 0.00</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                <div class="d-flex justify-content-between font-weight-bold mb-3">
                    <span>Refund total</span>
                    <span id="refundTotalPreview">{{ $currency }} 0.00</span>
                </div>
                <label class="font-weight-semibold">Method</label>
                <select id="refundMethod" class="form-control mb-3">
                    <option value="original">Original ({{ ucwords(str_replace('_', ' ', $sale->payment_method)) }})</option>
                    <option value="cash">Cash</option>
                    <option value="credit">Store credit</option>
                </select>
                <label class="font-weight-semibold">Reason *</label>
                <textarea id="refundReason" rows="2" class="form-control rounded-lg"></textarea>
                <div class="custom-control custom-switch mt-3">
                    <input type="checkbox" class="custom-control-input" id="refundRestock" checked>
                    <label class="custom-control-label" for="refundRestock">Restock</label>
                </div>
                <div id="refundError" class="alert alert-danger rounded-lg mt-3 d-none"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light px-4" data-dismiss="modal">Cancel</button>
                <button type="button" onclick="submitRefund()" class="btn btn-warning px-4 font-weight-bold">Confirm return</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="voidModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-xl shadow-xl">
            <div class="modal-header bg-danger text-white border-0 rounded-top">
                <h5 class="modal-title mb-0">Void sale</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-4 bg-white">
                <p class="text-muted small">Stock returns and accounting reversing entries run immediately.</p>
                <label class="font-weight-semibold text-danger">Audit reason</label>
                <textarea id="voidReason" class="form-control rounded-lg mb-3" rows="3"></textarea>
                <div id="voidError" class="alert alert-danger rounded-lg d-none"></div>
            </div>
            <div class="modal-footer border-0 pb-4">
                <button class="btn btn-light px-4" type="button" data-dismiss="modal">Cancel</button>
                <button type="button" onclick="submitVoid()" class="btn btn-danger px-5 font-weight-semibold">Void</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('js')
<script>
    const CSRF    = '{{ csrf_token() }}';
    const SALE_ID = {{ $sale->id }};
    const CURRENCY = @json(config('app.currency'));

    function recalcRefundPreview() {
        let total = 0;
        document.querySelectorAll('[data-sale-item-id]').forEach(row => {
            const chk = row.querySelector('.refund-line-check');
            const qtyIn = row.querySelector('.refund-line-qty');
            const est = row.querySelector('.refund-line-est');
            const unit = parseFloat(row.dataset.unitPrice) || 0;
            const qty = chk.checked ? Math.max(0, parseInt(qtyIn.value, 10) || 0) : 0;
            const line = unit * qty;
            total += line;
            if (est) est.textContent = CURRENCY + ' ' + line.toFixed(2);
        });
        const el = document.getElementById('refundTotalPreview');
        if (el) el.textContent = CURRENCY + ' ' + total.toFixed(2);
    }

    document.querySelectorAll('.refund-line-check').forEach(chk => {
        chk.addEventListener('change', () => {
            const row = chk.closest('tr');
            const qtyIn = row.querySelector('.refund-line-qty');
            qtyIn.disabled = !chk.checked;
            if (chk.checked && (parseInt(qtyIn.value, 10) || 0) < 1) {
                qtyIn.value = chk.dataset.max || 1;
            }
            recalcRefundPreview();
        });
    });
    document.querySelectorAll('.refund-line-qty').forEach(inp => {
        inp.addEventListener('input', recalcRefundPreview);
    });

    function submitRefund() {
        const reason  = document.getElementById('refundReason').value.trim();
        const method  = document.getElementById('refundMethod').value;
        const restock = document.getElementById('refundRestock').checked;
        const errEl   = document.getElementById('refundError');
        const items = [];
        document.querySelectorAll('[data-sale-item-id]').forEach(row => {
            const chk = row.querySelector('.refund-line-check');
            const qtyIn = row.querySelector('.refund-line-qty');
            if (!chk.checked) return;
            const qty = parseInt(qtyIn.value, 10) || 0;
            if (qty > 0) items.push({ sale_item_id: parseInt(row.dataset.saleItemId, 10), qty });
        });
        if (!reason) {
            errEl.textContent = 'Reason required.';
            errEl.classList.remove('d-none');
            return;
        }
        if (!items.length) {
            errEl.textContent = 'Select at least one line to return.';
            errEl.classList.remove('d-none');
            return;
        }
        fetch(`/pos/sales/${SALE_ID}/refund`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ items, reason, method, restock })
        }).then(r => r.json()).then(d => {
            if (d.success) location.reload();
            else { errEl.textContent = d.error || 'Error'; errEl.classList.remove('d-none'); }
        }).catch(() => { errEl.textContent = 'Request failed'; errEl.classList.remove('d-none'); });
    }

    function submitVoid() {
        const reason = document.getElementById('voidReason').value.trim();
        const errEl  = document.getElementById('voidError');
        if (!reason) {
            errEl.textContent = 'Reason required.';
            errEl.classList.remove('d-none');
            return;
        }
        fetch(`/pos/sales/${SALE_ID}/void`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ reason })
        }).then(r => r.json()).then(d => {
            if (d.success) location.reload();
            else { errEl.textContent = d.error || 'Error'; errEl.classList.remove('d-none'); }
        }).catch(() => { errEl.textContent = 'Request failed'; errEl.classList.remove('d-none'); });
    }
</script>
@endsection
