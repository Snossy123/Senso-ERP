@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between align-items-center flex-wrap">
    <div class="my-auto">
        <div class="d-flex flex-wrap align-items-center">
            <h4 class="content-title mb-0 my-auto">POS</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ Sales history</span>
        </div>
        <small class="text-muted d-block mt-1">Operational retail ledger · refunds and voids require permissions</small>
    </div>
    <div class="d-flex my-xl-auto right-content mt-3 mt-xl-0">
        <a href="{{ route('pos.app') }}" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fa fa-plus mr-1"></i> New sale</a>
    </div>
</div>
@endsection

@section('css')
<style>
    .pos-page-bg { background: linear-gradient(180deg,#f8fafc 0%,#eef2fb 140px); padding-bottom: 48px; }
    .sales-kpi { border-radius: 18px; padding: 1.15rem 1.25rem; color: #fff; display:flex; gap:14px; align-items:center; margin-bottom:1rem;
        box-shadow: 0 12px 32px rgba(15,23,42,0.12); border: 1px solid rgba(255,255,255,0.06); position: relative; overflow: hidden;}
    .sales-kpi .kpi-icon { width:52px; height:52px; flex-shrink:0; border-radius:14px; background: rgba(255,255,255,0.16); display:flex; align-items:center; justify-content:center; font-size: 1.35rem;}
    .sales-kpi .kpi-val { font-size: 1.5rem; font-weight:800; line-height:1.1;}
    .sales-kpi .kpi-lbl { font-size:.7rem; text-transform:uppercase; opacity:.92; letter-spacing:.05em;}
    .pos-sticky-filter { position:sticky; top:0; z-index:6; backdrop-filter:saturate(170%) blur(10px); background: rgba(255,255,255,0.9); border:1px solid #e9edf7; border-radius:16px; padding:14px 18px; margin-bottom:16px;}
    .trend-pane { border-radius:16px; border:1px dashed #cfdaee; padding:18px 20px; background:#fff; color:#69759b; margin-bottom:16px;}
    .chip-pay { padding: .22rem .6rem .22rem .5rem; border-radius:999px; font-size:.75rem; font-weight:700; letter-spacing:.02em; display:inline-flex; align-items:center; gap:.3rem;}
    .chip-pay.cash { background:#ecfdf5; color:#065f46; }
    .chip-pay.card { background:#eff6ff; color:#1e40af; }
    .chip-pay.transfer { background:#fffbeb; color:#92400e; }
    .badge-status-refunded-mini { border-radius: 999px; padding: .12rem .5rem; font-size:.68rem;}
    .table-sales thead { position: sticky; top: 0; z-index:2; box-shadow: 0 1px 0 #edf0fa;}
    .table-sales thead th { font-size:.65rem; text-transform:uppercase; letter-spacing:.06em; color:#8492b8; border-top:none; font-weight:800; vertical-align:middle;}
    .table-sales tbody tr { vertical-align:middle;}
    .avatar-disk { width:34px;height:34px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-size:.73rem;font-weight:800;color:#394867;background:#e8eefb;}
    @media(max-width: 767px){
        .pos-sticky-filter { position:relative; backdrop-filter:none; }
    }
    .sale-card-mobile { border-radius:16px;border:1px solid #eaeef7;margin-bottom:.75rem;}
    .skeleton-soft { opacity:.35;pointer-events:none; animation: shimmerLine 1.2s infinite linear;background:linear-gradient(90deg,#f2f6ff,#eaeef9,#f2f6ff);background-size:200%;height:13px;border-radius:6px;}
    @keyframes shimmerLine { 0%{background-position:140% center;}100%{background-position:-40%;} }
</style>
@endsection

@section('content')
<div class="pos-page-bg">
@php
    $today        = \App\Models\Sale::where('tenant_id', auth()->user()->tenant_id)->whereDate('created_at', today());
    $totalToday   = (clone $today)->where('status', 'completed')->sum('total');
    $countToday   = (clone $today)->where('status', 'completed')->count();
    $refundsToday = (clone $today)->where('status', 'refunded')->sum('total');
    $voidsToday   = (clone $today)->where('status', 'voided')->count();
    $avgOrder     = $countToday > 0 ? $totalToday / $countToday : 0;

    $initialsFn = static function (?string $name): string {
        if (!$name || trim($name) === '') { return '—'; }
        $parts = preg_split('/\s+/', trim($name));
        return strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . mb_substr($parts[count($parts) > 1 ? count($parts) - 1 : 0] ?? '?', 0, 1));
    };
@endphp

<div class="row row-sm mx-1 mx-md-0">
    <div class="col-xl-3 col-lg-6">
        <div class="sales-kpi" style="background:linear-gradient(135deg,#111827,#312e81);">
            <span class="kpi-icon"><i class="fe fe-trending-up"></i></span>
            <div>
                <div class="kpi-val">{{ config('app.currency') }} {{ number_format($totalToday, 2) }}</div>
                <div class="kpi-lbl">Today gross</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="sales-kpi" style="background:linear-gradient(135deg,#0f766e,#0d9488);">
            <span class="kpi-icon"><i class="fe fe-shopping-bag"></i></span>
            <div>
                <div class="kpi-val">{{ $countToday }}</div>
                <div class="kpi-lbl">Transactions</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="sales-kpi" style="background:linear-gradient(135deg,#ca8a04,#b45309);">
            <span class="kpi-icon"><i class="fe fe-rotate-ccw"></i></span>
            <div>
                <div class="kpi-val">{{ config('app.currency') }} {{ number_format($refundsToday, 2) }}</div>
                <div class="kpi-lbl">Refunds booked</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="sales-kpi" style="background:linear-gradient(135deg,#581c87,#6b21a8);">
            <span class="kpi-icon"><i class="fe fe-bar-chart-2"></i></span>
            <div>
                <div class="kpi-val">{{ config('app.currency') }} {{ number_format($avgOrder, 2) }}</div>
                <div class="kpi-lbl">Avg basket · {{ $voidsToday }} voids today</div>
            </div>
        </div>
    </div>
</div>

<div class="trend-pane d-none d-xl-flex align-items-center justify-content-between mx-2 mx-xl-1">
    <div>
        <div class="tx-13 font-weight-bold text-dark mb-1">Trend intelligence</div>
        <div class="tx-11 text-muted mb-0">Weekly comparison heatmap plugs in Phase 3 (realtime KPI stream).</div>
    </div>
    <span class="badge badge-light-secondary border rounded-pill tx-11">Placeholder</span>
</div>

<div class="pos-sticky-filter shadow-sm mx-2 mx-xl-1">
    <form method="GET" action="{{ route('pos.sales.index') }}" class="row align-items-end g-2 gx-3">
        <div class="col-6 col-md-2"><label class="tx-11 font-weight-bold text-muted text-uppercase mb-1">From</label><input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm rounded-lg"></div>
        <div class="col-6 col-md-2"><label class="tx-11 font-weight-bold text-muted text-uppercase mb-1">To</label><input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm rounded-lg"></div>
        <div class="col-md-2 col-12"><label class="tx-11 font-weight-bold text-muted text-uppercase mb-1">Cashier</label>
            <select name="cashier_id" class="form-control form-control-sm rounded-lg"><option value="">All cashiers</option>@foreach($cashiers as $id => $name)<option value="{{ $id }}" {{ request('cashier_id') == $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select>
        </div>
        <div class="col-md-2 col-6"><label class="tx-11 font-weight-bold text-muted text-uppercase mb-1">Payment</label>
            <select name="payment_method" class="form-control form-control-sm rounded-lg"><option value="">Any</option><option value="cash" {{ request('payment_method')=='cash'?'selected':'' }}>Cash</option><option value="card" {{ request('payment_method')=='card'?'selected':'' }}>Card</option><option value="bank_transfer" {{ request('payment_method')=='bank_transfer'?'selected':'' }}>Bank transfer</option></select>
        </div>
        <div class="col-md-2 col-6"><label class="tx-11 font-weight-bold text-muted text-uppercase mb-1">Status</label>
            <select name="status" class="form-control form-control-sm rounded-lg"><option value="">Any</option><option value="completed" {{ request('status')=='completed'?'selected':'' }}>Completed</option><option value="refunded" {{ request('status')=='refunded'?'selected':'' }}>Refunded</option><option value="voided" {{ request('status')=='voided'?'selected':'' }}>Voided</option></select>
        </div>
        <div class="col-md-2 col-12"><button type="submit" class="btn btn-primary btn-block rounded-lg"><i class="fe fe-filter mr-1"></i>Apply</button></div>
        <div class="col-md-auto col-12 text-md-right mt-2 mt-md-0"><a href="{{ route('pos.sales.index') }}" class="btn btn-outline-light border rounded-lg px-4">Reset</a></div>
    </form>
</div>

<div class="card border-0 shadow-lg rounded-xl overflow-hidden mx-2 mx-xl-1">
    <div class="card-body px-3 py-4">
        @foreach($sales as $sale)
            <div class="sale-card-mobile d-md-none p-3 mb-3">
                <div class="d-flex justify-content-between mb-2">
                    <strong class="text-dark">{{ $sale->sale_number }}</strong>
                    <span class="text-primary font-weight-bold">{{ config('app.currency') }} {{ number_format($sale->total, 2) }}</span>
                </div>
                @php $pm = $sale->payment_method; @endphp
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @if($sale->status === 'completed')<span class="badge badge-success rounded-pill">Completed</span>
                    @elseif($sale->status === 'refunded')<span class="badge badge-warning rounded-pill">Refunded</span>
                    @elseif($sale->status === 'voided')<span class="badge badge-danger rounded-pill">Voided</span>@else <span class="badge badge-secondary rounded-pill">{{ ucfirst($sale->status) }}</span>@endif
                    @if($pm === 'cash')<span class="chip-pay cash"><i class="fe fe-dollar-sign"></i> Cash</span>
                    @elseif($pm === 'card')<span class="chip-pay card"><i class="fe fe-credit-card"></i> Card</span>
                    @else<span class="chip-pay transfer"><i class="fe fe-smartphone"></i> Transfer</span>@endif
                    @if($sale->status === 'refunded') <span class="badge badge-soft-warning badge-status-refunded-mini">Refund activity</span> @endif
                </div>
                <small class="text-muted d-block mb-3">{{ $sale->created_at->format('M d · H:i') }} · {{ $sale->customer?->name ?? 'Walk-in' }}</small>
                <div class="d-flex justify-content-end"><a href="{{ route('pos.sales.show', $sale) }}" class="btn btn-sm btn-info-light mr-2">View</a>
                    @if(!$sale->isVoided() && !$sale->isRefunded())
                        <button type="button" class="btn btn-sm btn-warning-light mr-2" onclick="openRefund({{ $sale->id }}, '{{ $sale->sale_number }}', {{ $sale->total }})">Refund</button>
                        <button type="button" class="btn btn-sm btn-danger-light" onclick="openVoid({{ $sale->id }}, '{{ $sale->sale_number }}')">Void</button>
                    @endif
                </div>
            </div>
        @endforeach
        @if($sales->isEmpty())
            <div class="py-5 text-center">
                <i class="fe fe-shopping-cart tx-56 text-muted d-block mb-3"></i>
                <p class="text-muted mb-3">Nothing matches your lens yet.</p>
                <a href="{{ route('pos.app') }}" class="btn btn-primary rounded-xl px-5">Spin up a lane</a>
            </div>
        @endif
    </div>
    <div class="d-none d-md-block px-3 pb-0">
        <div class="table-responsive rounded-lg border mb-4">
            <table class="table table-hover mb-0 table-sales">
                <thead class="bg-light">
                    <tr>
                        <th class="pl-4">Sale</th><th style="white-space:nowrap;">When</th><th>Customer</th><th>Cashier</th><th>Items</th><th>Tender</th><th>KPI</th><th class="text-right">Due</th><th class="text-center pr-4">⋯</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sales as $sale)
                    <tr class="border-light">
                        <td class="pl-4 tx-13 font-weight-bold">{{ $sale->sale_number }}</td>
                        <td style="white-space:nowrap;"><div class="font-weight-bold">{{ $sale->created_at->format('M d · H:i') }}</div></td>
                        <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="avatar-disk mr-2">{{ $initialsFn($sale->user?->name) }}</span>
                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($sale->user?->name ?? '—', 16) }}</small>
                            </div>
                        </td>
                        <td><span class="badge badge-soft-light border">{{ $sale->items->count() }}</span></td>
                        @php $pm = $sale->payment_method; @endphp
                        <td>
                            @if($pm === 'cash') <span class="chip-pay cash"><i class="fe fe-dollar-sign"></i> Cash</span>
                            @elseif($pm === 'card') <span class="chip-pay card"><i class="fe fe-credit-card"></i> Card</span>
                            @else <span class="chip-pay transfer"><i class="fe fe-smartphone"></i> transfer</span> @endif
                        </td>
                        <td>
                            @php $s = $sale->status; @endphp
                            @if($s === 'completed') <span class="badge badge-soft-success badge-status-refunded-mini">Paid</span>
                            @elseif($s === 'refunded') <span class="badge badge-soft-warning badge-status-refunded-mini">Refund</span>
                            @elseif($s === 'voided') <span class="badge badge-soft-danger badge-status-refunded-mini">Void</span>
                            @else <span class="badge badge-soft-secondary">{{ $s }}</span> @endif
                        </td>
                        <td class="text-right font-weight-bold tx-14">{{ config('app.currency') }} {{ number_format($sale->total, 2) }}</td>
                        <td class="text-center pr-3">
                            <a href="{{ route('pos.sales.show', $sale) }}" class="btn btn-xs btn-rounded btn-info-gradient action-btn mr-2" title="View"><i class="fe fe-eye"></i></a>
                            @if(!$sale->isVoided() && !$sale->isRefunded())
                                <button type="button" class="btn btn-xs btn-rounded btn-outline-warning mr-2" onclick="openRefund({{ $sale->id }}, '{{ $sale->sale_number }}', {{ $sale->total }})"><i class="fe fe-rotate-ccw"></i></button>
                                <button type="button" class="btn btn-xs btn-rounded btn-outline-danger" onclick="openVoid({{ $sale->id }}, '{{ $sale->sale_number }}')"><i class="fe fe-slash"></i></button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($sales->hasPages())
    <div class="card-footer bg-white border-0">{{ $sales->links() }}</div>
    @endif
</div>
</div>

{{-- refund & void modals unchanged --}}
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-xl rounded-xl overflow-hidden"><div class="modal-header bg-warning text-white rounded-0 py-3">
        <h5 class="modal-title mb-0"><i class="fe fe-rotate-ccw mr-2"></i>Refund — <span id="refundSaleNum"></span></h5>
        <button class="close text-white" type="button" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body px-4 py-4 bg-light rounded-0">
        <label class="font-weight-semibold mb-2">Refund amount</label>
        <small class="d-block text-muted mb-2">Maximum <span id="refundMax"></span></small>
        <div class="input-group mb-4 shadow-sm border rounded-xl overflow-hidden"><div class="input-group-prepend"><span class="input-group-text">{{ config('app.currency') }}</span></div><input type="number" id="refundAmount" class="form-control font-weight-semibold bg-white" step="0.01"></div>
        <label class="font-weight-semibold">Method</label>
        <select id="refundMethod" class="form-control mb-4 rounded-xl border"><option value="original">Original</option><option value="cash">Cash</option><option value="credit">Credit</option></select>
        <label class="font-weight-semibold">Reason <span class="text-danger">*</span></label>
        <textarea id="refundReason" rows="2" class="form-control rounded-xl border mb-3" placeholder="Audit note…"></textarea>
        <div class="custom-control custom-switch mb-3"><input type="checkbox" class="custom-control-input" id="refundRestock" checked><label class="custom-control-label" for="refundRestock">Restock</label></div>
        <div id="refundError" class="alert alert-danger d-none rounded-lg"></div></div><div class="modal-footer rounded-0 border-0 pb-4"><button type="button" class="btn btn-outline-secondary px-4" data-dismiss="modal">Dismiss</button><button type="button" class="btn btn-warning px-5 font-weight-bold rounded-xl" onclick="submitRefund()">Post refund</button></div></div></div></div>

<div class="modal fade" id="voidModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content border-0 rounded-xl shadow-lg"><div class="modal-header bg-danger text-white rounded-top"><h5 class="modal-title font-weight-semibold mb-0">Void sale</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
    <div class="modal-body p-4"><p class="text-muted tx-13 mb-3">Permanent reversal for <strong id="voidSaleNum"></strong></p><textarea id="voidReason" rows="3" class="form-control rounded-lg border mb-3" placeholder="Reason required"></textarea><div id="voidError" class="alert alert-danger rounded-lg d-none"></div></div>
    <div class="modal-footer border-0"><button type="button" data-dismiss="modal" class="btn btn-light px-4">Cancel</button><button type="button" class="btn btn-danger px-4 font-weight-bold rounded-lg" onclick="submitVoid()">Void</button></div></div></div></div>
@endsection

@section('js')
<script>
    let currentSaleId = null;
    const CSRF = '{{ csrf_token() }}';

    function openRefund(id, num, max) {
        currentSaleId = id;
        document.getElementById('refundSaleNum').textContent = num;
        document.getElementById('refundMax').textContent = '{{ config("app.currency") }} ' + parseFloat(max).toFixed(2);
        document.getElementById('refundAmount').value = parseFloat(max).toFixed(2);
        document.getElementById('refundAmount').max = max;
        document.getElementById('refundReason').value = '';
        document.getElementById('refundError').classList.add('d-none');
        $('#refundModal').modal('show');
    }

    function submitRefund() {
        const amount  = document.getElementById('refundAmount').value;
        const reason  = document.getElementById('refundReason').value.trim();
        const method  = document.getElementById('refundMethod').value;
        const restock = document.getElementById('refundRestock').checked;
        const errEl   = document.getElementById('refundError');

        if (!reason) { errEl.textContent = 'Reason required'; errEl.classList.remove('d-none'); return; }

        fetch(`/pos/sales/${currentSaleId}/refund`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ amount, reason, method, restock })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) { $('#refundModal').modal('hide'); location.reload(); }
            else { errEl.textContent = d.error || 'Error'; errEl.classList.remove('d-none'); }
        })
        .catch(() => { errEl.textContent = 'Request failed.'; errEl.classList.remove('d-none'); });
    }

    function openVoid(id, num) {
        currentSaleId = id;
        document.getElementById('voidSaleNum').textContent = num;
        document.getElementById('voidReason').value = '';
        document.getElementById('voidError').classList.add('d-none');
        $('#voidModal').modal('show');
    }

    function submitVoid() {
        const reason = document.getElementById('voidReason').value.trim();
        const errEl  = document.getElementById('voidError');
        if (!reason) { errEl.textContent = 'Reason required'; errEl.classList.remove('d-none'); return; }

        fetch(`/pos/sales/${currentSaleId}/void`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ reason })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) { $('#voidModal').modal('hide'); location.reload(); }
            else { errEl.textContent = d.error || 'Error'; errEl.classList.remove('d-none'); }
        })
        .catch(() => { errEl.textContent = 'Request failed.'; errEl.classList.remove('d-none'); });
    }
</script>
@endsection
