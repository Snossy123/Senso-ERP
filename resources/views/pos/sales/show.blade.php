@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex align-items-center">
            <h4 class="content-title mb-0 my-auto">POS</h4>
            <span class="text-muted mt-1 tx-13 mx-2 mb-0">/ Sales History</span>
            <span class="text-muted mt-1 tx-13 mb-0">/ {{ $sale->sale_number }}</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content gap-2">
        <a href="{{ route('pos.sales.index') }}" class="btn btn-secondary mr-2"><i class="fe fe-arrow-left mr-1"></i> Back</a>
        <button onclick="window.print()" class="btn btn-outline-info mr-2"><i class="fe fe-printer mr-1"></i> Print</button>
        @if(!$sale->isVoided() && !$sale->isRefunded())
            <button class="btn btn-warning mr-2" onclick="$('#refundModal').modal('show')"><i class="fe fe-rotate-ccw mr-1"></i> Refund</button>
            <button class="btn btn-danger" onclick="$('#voidModal').modal('show')"><i class="fe fe-slash mr-1"></i> Void</button>
        @endif
    </div>
</div>
@endsection

@section('css')
<style>
    .detail-card { border-radius: 12px; border: none; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
    .info-row { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid #f3f4f6; font-size: 0.93em; }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: #888; font-weight: 600; }
    .info-val { font-weight: 700; color: #222; }
    .status-pill { padding: 5px 16px; border-radius: 20px; font-weight: 700; font-size: 0.82em; letter-spacing: 0.4px; text-transform: uppercase; }
    .status-completed { background: #d4edda; color: #155724; }
    .status-refunded  { background: #fff3cd; color: #856404; }
    .status-voided    { background: #f8d7da; color: #721c24; }
    .total-block { background: linear-gradient(135deg, #1a237e, #283593); color: #fff; border-radius: 12px; padding: 20px 24px; }
    .receipt-table th { font-size: 0.78em; text-transform: uppercase; color: #888; letter-spacing: 0.5px; font-weight: 700; border-top: none; }
    .refund-item { background: #fff8e1; border-left: 4px solid #ffc107; border-radius: 6px; padding: 12px 16px; margin-bottom: 8px; }
    @media print {
        .breadcrumb-header, .btn, .breadcrumb, footer, #refundModal, #voidModal { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
    }
</style>
@endsection

@section('content')
<div class="row">

    {{-- Left: Order Details --}}
    <div class="col-lg-8">

        {{-- Items Table --}}
        <div class="card detail-card mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 font-weight-bold"><i class="fe fe-shopping-bag mr-2 text-primary"></i>Order Items</h5>
                <span class="status-pill status-{{ $sale->status }}">{{ ucfirst($sale->status) }}</span>
            </div>
            <div class="card-body p-0">
                <table class="table receipt-table mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="pl-4">Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right pr-4">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $item)
                        <tr>
                            <td class="pl-4">
                                <div class="font-weight-bold">{{ $item->product?->name ?? '<em class="text-danger">Product Deleted</em>' }}</div>
                                @if($item->product_variant_id)
                                    <small class="text-muted">Variant #{{ $item->product_variant_id }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-light border">{{ $item->quantity }}</span>
                            </td>
                            <td class="text-right">{{ config('app.currency') }} {{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-right text-danger">
                                @if($item->discount_pct > 0)
                                    {{ $item->discount_pct }}% (−{{ config('app.currency') }} {{ number_format($item->discount_amount, 2) }})
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-right pr-4 font-weight-bold">{{ config('app.currency') }} {{ number_format($item->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Refund History --}}
        @if($sale->refunds->count() > 0)
        <div class="card detail-card mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 font-weight-bold"><i class="fe fe-rotate-ccw mr-2 text-warning"></i>Refund History</h5>
            </div>
            <div class="card-body">
                @foreach($sale->refunds as $refund)
                <div class="refund-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $refund->refund_number }}</strong>
                            <span class="badge badge-warning-transparent ml-2">{{ ucfirst($refund->method) }}</span>
                        </div>
                        <strong class="text-warning tx-16">{{ config('app.currency') }} {{ number_format($refund->amount, 2) }}</strong>
                    </div>
                    <div class="mt-1 text-muted tx-12">
                        <i class="fe fe-calendar mr-1"></i>{{ $refund->created_at->format('M d, Y H:i') }}
                        <span class="mx-2">•</span>
                        <i class="fe fe-user mr-1"></i>{{ $refund->user?->name ?? 'N/A' }}
                        <span class="mx-2">•</span>
                        {{ $refund->reason }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Void Info --}}
        @if($sale->isVoided())
        <div class="card detail-card mb-4 border-danger">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <i class="fe fe-slash text-danger tx-24 mr-3"></i>
                    <div>
                        <strong class="text-danger">Sale Voided</strong>
                        <p class="mb-0 text-muted tx-13">{{ $sale->void_reason }} — by {{ $sale->voidedBy?->name ?? 'N/A' }} at {{ $sale->voided_at?->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- Right: Summary & Meta --}}
    <div class="col-lg-4">

        {{-- Totals --}}
        <div class="total-block mb-4 shadow">
            <div class="d-flex justify-content-between mb-3 opacity-75" style="font-size:0.88em; text-transform:uppercase; letter-spacing:0.5px;">
                <span>Subtotal</span>
                <span>{{ config('app.currency') }} {{ number_format($sale->subtotal, 2) }}</span>
            </div>
            @if($sale->discount_amount > 0)
            <div class="d-flex justify-content-between mb-3" style="font-size:0.88em; color:#ffcc80;">
                <span>Discount</span>
                <span>− {{ config('app.currency') }} {{ number_format($sale->discount_amount, 2) }}</span>
            </div>
            @endif
            @if($sale->tax_amount > 0)
            <div class="d-flex justify-content-between mb-3 opacity-75" style="font-size:0.88em;">
                <span>Tax</span>
                <span>{{ config('app.currency') }} {{ number_format($sale->tax_amount, 2) }}</span>
            </div>
            @endif
            <div class="d-flex justify-content-between align-items-center border-top pt-3" style="border-color:rgba(255,255,255,0.2) !important;">
                <span style="font-size:1em; opacity:0.85;" class="text-uppercase">Total</span>
                <span style="font-size:1.9em; font-weight:900; letter-spacing:-1px;">{{ config('app.currency') }} {{ number_format($sale->total, 2) }}</span>
            </div>
            @if($sale->payment_method === 'cash')
            <div class="d-flex justify-content-between mt-3 pt-2" style="border-top:1px solid rgba(255,255,255,0.15); font-size:0.85em; opacity:0.8;">
                <span>Tendered</span><span>{{ config('app.currency') }} {{ number_format($sale->amount_tendered, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between mt-1" style="font-size:0.85em; opacity:0.8;">
                <span>Change</span><span>{{ config('app.currency') }} {{ number_format($sale->change_due, 2) }}</span>
            </div>
            @endif
        </div>

        {{-- Meta Info --}}
        <div class="card detail-card mb-4">
            <div class="card-body py-3 px-4">
                <div class="info-row">
                    <span class="info-label">Sale #</span>
                    <span class="info-val">{{ $sale->sale_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date</span>
                    <span class="info-val">{{ $sale->created_at->format('M d, Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Time</span>
                    <span class="info-val">{{ $sale->created_at->format('H:i:s') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Cashier</span>
                    <span class="info-val">{{ $sale->user?->name ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer</span>
                    <span class="info-val">{{ $sale->customer?->name ?? 'Walk-in' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment</span>
                    <span class="info-val">{{ ucwords(str_replace('_', ' ', $sale->payment_method)) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="status-pill status-{{ $sale->status }}">{{ ucfirst($sale->status) }}</span>
                </div>
                @if($sale->notes)
                <div class="info-row">
                    <span class="info-label">Notes</span>
                    <span class="info-val text-muted" style="max-width:60%; text-align:right;">{{ $sale->notes }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Shift Info --}}
        @if($sale->shift)
        <div class="card detail-card">
            <div class="card-body py-3 px-4">
                <div class="tx-11 font-weight-bold text-muted text-uppercase mb-2">Shift Info</div>
                <div class="info-row">
                    <span class="info-label">Terminal</span>
                    <span class="info-val">{{ $sale->shift->terminal_id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Opened</span>
                    <span class="info-val">{{ $sale->shift->opened_at->format('H:i') }}</span>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>

{{-- Refund Modal --}}
@if(!$sale->isVoided() && !$sale->isRefunded())
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title"><i class="fe fe-rotate-ccw mr-2"></i>Issue Refund — {{ $sale->sale_number }}</h5>
                <button class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4">
                <div class="form-group">
                    <label class="font-weight-bold">Refund Amount <small class="text-muted">(max: {{ config('app.currency') }} {{ number_format($sale->total, 2) }})</small></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">{{ config('app.currency') }}</span></div>
                        <input type="number" id="refundAmount" class="form-control font-weight-bold" step="0.01"
                               value="{{ number_format($sale->total, 2) }}" max="{{ $sale->total }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Refund Method</label>
                    <select id="refundMethod" class="form-control">
                        <option value="original">Original Method ({{ ucwords(str_replace('_',' ',$sale->payment_method)) }})</option>
                        <option value="cash">Cash</option>
                        <option value="credit">Store Credit</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Reason <span class="text-danger">*</span></label>
                    <textarea id="refundReason" class="form-control" rows="2" placeholder="Reason for refund..."></textarea>
                </div>
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="refundRestock" checked>
                    <label class="custom-control-label font-weight-bold" for="refundRestock">Restore stock to inventory</label>
                </div>
                <div id="refundError" class="alert alert-danger mt-3 d-none"></div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button class="btn btn-warning px-4 font-weight-bold" onclick="submitRefund()"><i class="fe fe-check mr-1"></i>Confirm Refund</button>
            </div>
        </div>
    </div>
</div>

{{-- Void Modal --}}
<div class="modal fade" id="voidModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fe fe-slash mr-2"></i>Void Sale</h5>
                <button class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-3">You are about to void <strong>{{ $sale->sale_number }}</strong>. Stock will be restored and this action cannot be undone.</p>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Reason <span class="text-danger">*</span></label>
                    <textarea id="voidReason" class="form-control" rows="2" placeholder="Reason..."></textarea>
                </div>
                <div id="voidError" class="alert alert-danger mt-3 d-none"></div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button class="btn btn-danger px-4 font-weight-bold" onclick="submitVoid()"><i class="fe fe-trash-2 mr-1"></i>Void Sale</button>
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

    function submitRefund() {
        const amount  = document.getElementById('refundAmount').value;
        const reason  = document.getElementById('refundReason').value.trim();
        const method  = document.getElementById('refundMethod').value;
        const restock = document.getElementById('refundRestock').checked;
        const errEl   = document.getElementById('refundError');

        if (!reason) {
            errEl.textContent = 'Please enter a reason.';
            errEl.classList.remove('d-none');
            return;
        }

        fetch(`/pos/sales/${SALE_ID}/refund`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ amount, reason, method, restock })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) { location.reload(); }
            else { errEl.textContent = d.error || 'Error occurred.'; errEl.classList.remove('d-none'); }
        })
        .catch(() => { errEl.textContent = 'Request failed.'; errEl.classList.remove('d-none'); });
    }

    function submitVoid() {
        const reason = document.getElementById('voidReason').value.trim();
        const errEl  = document.getElementById('voidError');

        if (!reason) {
            errEl.textContent = 'Please enter a reason.';
            errEl.classList.remove('d-none');
            return;
        }

        fetch(`/pos/sales/${SALE_ID}/void`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ reason })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) { location.reload(); }
            else { errEl.textContent = d.error || 'Error occurred.'; errEl.classList.remove('d-none'); }
        })
        .catch(() => { errEl.textContent = 'Request failed.'; errEl.classList.remove('d-none'); });
    }
</script>
@endsection
