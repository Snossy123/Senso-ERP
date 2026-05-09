@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between flex-wrap gap-3">
    <div class="my-auto">
        <div class="d-flex flex-wrap align-items-center">
            <h4 class="content-title mb-0 my-auto">POS</h4>
            <span class="text-muted mt-1 tx-13 mx-2 mb-0">/ Receipt</span>
            <span class="badge badge-primary-transparent border rounded-pill px-3 py-1 tx-13 font-weight-bold">{{ $sale->sale_number }}</span>
        </div>
    </div>
    <div class="d-flex flex-wrap my-xl-auto right-content gap-2">
        <a href="{{ route('pos.sales.index') }}" class="btn btn-secondary rounded-pill"><i class="fe fe-arrow-left mr-1"></i> Back</a>
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary rounded-pill"><i class="fe fe-printer mr-1"></i> Print</button>
        @if(!$sale->isVoided() && !$sale->isRefunded())
            <button type="button" class="btn btn-warning rounded-pill" onclick="$('#refundModal').modal('show')"><i class="fe fe-rotate-ccw mr-1"></i> Refund</button>
            <button type="button" class="btn btn-danger rounded-pill" onclick="$('#voidModal').modal('show')"><i class="fe fe-slash mr-1"></i> Void</button>
        @endif
    </div>
</div>
@endsection

@section('css')
<style>
    .receipt-shell { background: linear-gradient(145deg,#eef2ff,#f8fafc); padding-bottom: 32px; border-radius:20px; padding:20px;}
    .receipt-stack { border-radius: 20px; overflow: hidden; border: 1px solid #eceff7; box-shadow: 0 12px 42px rgba(15,23,42,0.08); background: #fff; }
    .receipt-line { display:flex; justify-content:space-between; padding:.55rem 0; font-size:.93rem; border-bottom:1px solid #f2f4fb; }
    .receipt-line:last-child { border-bottom:0; }
    .receipt-muted { color:#8b95b8; font-weight:600; text-transform:uppercase; font-size:.62rem; letter-spacing:.08em; }
    .status-pill-prem { padding:.28rem .95rem; border-radius:999px; font-weight:800; letter-spacing:.06em; font-size:.65rem; text-transform:uppercase; display:inline-block; }
    .status-pill-prem.completed { background:#dcfce7;color:#166534;}
    .status-pill-prem.refunded { background:#fef3c7;color:#92400e;}
    .status-pill-prem.voided { background:#fee2e2;color:#991b1b;}
    .sticky-totals { position:sticky; top:92px; border-radius:20px; overflow:hidden; border:1px solid #e2e8f0; box-shadow:0 22px 56px rgba(15,23,42,0.12); background:linear-gradient(140deg,#0f172a,#312e81); color:#fff; }
    .refund-chip { border-left: 4px solid #fbbf24; background:#fffaf0; padding:14px 16px 16px; border-radius:16px; margin-bottom: .75rem; box-shadow:0 6px 18px rgba(251,191,36,0.15); }
    .timeline-marker { width:13px;height:13px;border-radius:50%;background:#4f46e5;border:3px solid #e0e7ff;margin-top:.35rem;margin-right:.75rem;}
    .timeline-rail { width:3px;background:#eef2ff;position:absolute;top:36px;bottom:24px;left:6px;border-radius:3px;}
    @media print {
        .breadcrumb-header, .btn, .breadcrumb, footer, #refundModal, #voidModal, .sidebar, .sidebar-mini { display:none !important; }
        .sticky-totals { position: relative !important; top: 0 !important; box-shadow: none !important; }
        .receipt-stack { box-shadow: none !important; border:none !important;}
    }
</style>
@endsection

@section('content')
<div class="receipt-shell">
<div class="row">
    <div class="col-lg-8">

        <div class="card receipt-stack mb-4 rounded-xl">
            <div class="card-header bg-white py-4 border-0 px-4 d-flex justify-content-between align-items-start flex-wrap">
                <div>
                    <small class="receipt-muted mb-2 d-inline-block">Line items</small>
                    <h5 class="mb-2 font-weight-bold text-dark">{{ $sale->sale_number }}</h5>
                    <div class="d-flex gap-3 flex-wrap text-muted tx-13">
                        <span><i class="fe fe-calendar mr-1 text-primary"></i>{{ $sale->created_at->format('M d, Y · H:i') }}</span>
                        <span><i class="fe fe-user mr-1 text-success"></i>{{ $sale->user?->name ?? 'Cashier unknown' }}</span>
                    </div>
                </div>
                <span class="status-pill-prem {{ $sale->status }}">{{ $sale->status }}</span>
            </div>
            <div class="table-responsive px-2 px-md-4 pb-2">
                <table class="table mb-2">
                    <thead class="text-muted tx-11"><tr><th>Product</th><th class="text-center">Qty</th><th class="text-right">Unit</th><th class="text-right">Disc</th><th class="text-right pr-md-4">Total</th></tr></thead>
                    <tbody>
                        @foreach($sale->items as $item)
                        <tr>
                            <td class="border-0 pl-3">
                                <div class="font-weight-bold">{{ $item->product?->name ?: 'Unavailable SKU' }}</div>
                                @if($item->product_variant_id)<small class="text-muted tx-11">Variant #{{ $item->product_variant_id }}</small>@endif
                            </td>
                            <td class="border-0 text-center">{{ $item->quantity }}</td>
                            <td class="border-0 text-right">{{ config('app.currency') }} {{ number_format($item->unit_price, 2) }}</td>
                            <td class="border-0 text-right text-muted">
                                @if($item->discount_pct > 0)
                                    −{{ config('app.currency') }} {{ number_format($item->discount_amount, 2) }}
                                @else —
                                @endif
                            </td>
                            <td class="border-0 text-right pr-md-4 font-weight-semibold">{{ config('app.currency') }} {{ number_format($item->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($sale->refunds->count())
        <div class="mb-4">
            <div class="d-flex align-items-center mb-3">
                <span class="rounded-circle mr-3" style="width:12px;height:12px;background:#fbbf24;"></span>
                <h5 class="mb-0 font-weight-bold"><i class="fe fe-repeat mr-2 text-warning"></i>Refund timeline</h5>
            </div>
            @foreach($sale->refunds as $refund)
                <div class="refund-chip shadow-sm border">
                    <div class="d-flex justify-content-between flex-wrap mb-2">
                        <div class="font-weight-bold">{{ $refund->refund_number }}</div>
                        <div class="text-warning tx-17 font-weight-bolder">{{ config('app.currency') }} {{ number_format($refund->amount, 2) }}</div>
                    </div>
                    <div class="text-muted tx-12">
                        {{ $refund->created_at->format('M d · H:i') }} · {{ $refund->user?->name }} · Method {{ ucfirst($refund->method) }}
                    </div>
                    <p class="mb-0 tx-13 mt-2 text-dark">{{ $refund->reason }}</p>
                </div>
            @endforeach
        </div>
        @endif

        @if($sale->isVoided())
            <div class="alert shadow-sm border-danger text-danger rounded-xl bg-white mb-4">
                <strong>Void locked</strong> — {{ $sale->void_reason }} · {{ $sale->voidedBy?->name ?? 'Cashier N/A' }} · {{ optional($sale->voided_at)->format('M d · H:i') }}
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="sticky-totals p-4 rounded-xl mb-4">
            <div class="d-flex justify-content-between tx-13 opacity-80"><span class="text-uppercase">Subtotal</span><span>{{ config('app.currency') }} {{ number_format($sale->subtotal, 2) }}</span></div>
            @if($sale->discount_amount > 0)
            <div class="d-flex justify-content-between tx-13 mt-3" style="color:#fde68a;"><span class="opacity-85">Discount</span><span class="font-weight-semibold">- {{ config('app.currency') }} {{ number_format($sale->discount_amount, 2) }}</span></div>
            @endif
            @if($sale->tax_amount > 0)
            <div class="d-flex justify-content-between tx-13 mt-3 opacity-80"><span>Tax</span><span>{{ config('app.currency') }} {{ number_format($sale->tax_amount, 2) }}</span></div>
            @endif
            <div class="border-top mt-4 pt-4 mb-4" style="border-color:rgba(255,255,255,0.2)!important;">
                <div class="d-flex justify-content-between align-items-baseline">
                    <span class="text-uppercase tx-13 opacity-80">Collected</span>
                    <span style="font-size:1.85rem;letter-spacing:-0.03em;line-height:1;" class="font-weight-bolder">{{ config('app.currency') }} {{ number_format($sale->total, 2) }}</span>
                </div>
                @if($sale->payment_method === 'cash')
                    <div class="d-flex justify-content-between tx-12 mt-3 opacity-80"><span>Tendered</span><span>{{ config('app.currency') }} {{ number_format($sale->amount_tendered, 2) }}</span></div>
                    <div class="d-flex justify-content-between tx-12 mt-2 opacity-80"><span>Change due</span><span>{{ config('app.currency') }} {{ number_format($sale->change_due, 2) }}</span></div>
                @endif
            </div>
            <div class="receipt-muted mb-3">Operational metadata</div>
            @php $rows = [['label'=>'Customer','value'=>$sale->customer?->name ?? 'Walk-in'],
                    ['label'=>'Status','value'=>ucfirst($sale->status)],
                    ['label'=>'Tender','value'=>ucwords(str_replace('_',' ',$sale->payment_method))],
                    ['label'=>'Shift','value'=>$sale->shift ? $sale->shift->terminal_id.' · '.$sale->shift->opened_at->format('H:i') : '—']]; @endphp
            <div class="position-relative pl-4">
                <span class="timeline-rail"></span>
                @foreach($rows as $row)
                <div class="d-flex pb-4 position-relative">
                    <span class="timeline-marker"></span>
                    <div>
                        <div class="receipt-muted mb-1">{{ $row['label'] }}</div>
                        <div class="tx-13 font-weight-semibold">{{ $row['value'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@if(!$sale->isVoided() && !$sale->isRefunded())
<div class="modal fade" id="refundModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 rounded-xl shadow-xl">
    <div class="modal-header bg-warning text-white border-0"><h5 class="modal-title mb-0">Refund — {{ $sale->sale_number }}</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
    <div class="modal-body px-4 py-4 bg-light">
        <label class="font-weight-semibold">Amount <small>(max {{ config('app.currency') }} {{ number_format($sale->total, 2) }})</small></label>
        <div class="input-group mb-3"><div class="input-group-prepend"><span class="input-group-text">{{ config('app.currency') }}</span></div>
            <input type="number" id="refundAmount" class="form-control" step="0.01" max="{{ $sale->total }}" value="{{ number_format($sale->total, 2, '.', '') }}"></div>
        <label class="font-weight-semibold">Method</label>
        <select id="refundMethod" class="form-control mb-3"><option value="original">Original ({{ ucwords(str_replace('_',' ',$sale->payment_method)) }})</option><option value="cash">Cash</option><option value="credit">Store credit</option></select>
        <label class="font-weight-semibold">Reason *</label>
        <textarea id="refundReason" rows="3" class="form-control rounded-lg"></textarea>
        <div class="custom-control custom-switch mt-3"><input type="checkbox" class="custom-control-input" id="refundRestock" checked><label class="custom-control-label" for="refundRestock">Restock</label></div>
        <div id="refundError" class="alert alert-danger rounded-lg mt-3 d-none"></div>
    </div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-light px-4" data-dismiss="modal">Cancel</button><button type="button" onclick="submitRefund()" class="btn btn-warning px-4 font-weight-bold">Confirm refund</button></div>
</div></div></div>
<div class="modal fade" id="voidModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content border-0 rounded-xl shadow-xl">
    <div class="modal-header bg-danger text-white border-0 rounded-top"><h5 class="modal-title mb-0">Void sale</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
    <div class="modal-body p-4 bg-white"><p class="text-muted">Stock returns and accounting reversing entries run immediately.</p>
        <label class="font-weight-semibold text-danger">Audit reason</label><textarea id="voidReason" class="form-control rounded-lg mb-3" rows="3"></textarea>
        <div id="voidError" class="alert alert-danger rounded-lg d-none"></div></div>
    <div class="modal-footer border-0 pb-4"><button class="btn btn-light px-4" type="button" data-dismiss="modal">Cancel</button><button type="button" onclick="submitVoid()" class="btn btn-danger px-5 font-weight-semibold">Void</button></div>
</div></div></div>
@endif
</div>

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
            errEl.textContent = 'Reason required.';
            errEl.classList.remove('d-none');
            return;
        }
        fetch(`/pos/sales/${SALE_ID}/refund`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ amount, reason, method, restock })
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
