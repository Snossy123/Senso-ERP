@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">POS</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ Sales History</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <a href="{{ route('pos.terminal') }}" class="btn btn-primary"><i class="fa fa-plus mr-1"></i> New Sale</a>
    </div>
</div>
@endsection

@section('css')
<style>
    .filter-card { background: #fff; border-radius: 12px; padding: 20px 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); margin-bottom: 20px; border: 1px solid #f0f0f0; }
    .stat-card { border-radius: 12px; padding: 20px 24px; color: #fff; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .stat-card .icon-wrap { width: 56px; height: 56px; border-radius: 12px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
    .stat-card .stat-val { font-size: 1.7em; font-weight: 800; line-height: 1; }
    .stat-card .stat-lbl { font-size: 0.78em; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
    .badge-status-completed { background: #d4edda; color: #155724; }
    .badge-status-refunded  { background: #fff3cd; color: #856404; }
    .badge-status-voided    { background: #f8d7da; color: #721c24; }
    .table th { font-size: 0.78em; text-transform: uppercase; color: #888; letter-spacing: 0.5px; font-weight: 700; border-top: none; }
    .action-btn { width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; }
</style>
@endsection

@section('content')

{{-- Summary Stats --}}
@php
    $today        = \App\Models\Sale::where('tenant_id', auth()->user()->tenant_id)->whereDate('created_at', today());
    $totalToday   = (clone $today)->where('status', 'completed')->sum('total');
    $countToday   = (clone $today)->where('status', 'completed')->count();
    $refundsToday = (clone $today)->where('status', 'refunded')->sum('total');
    $avgOrder     = $countToday > 0 ? $totalToday / $countToday : 0;
@endphp
<div class="row mb-2">
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#1a237e,#283593)">
            <div class="icon-wrap"><i class="fe fe-trending-up"></i></div>
            <div>
                <div class="stat-val">{{ config('app.currency') }} {{ number_format($totalToday, 2) }}</div>
                <div class="stat-lbl">Today's Sales</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#00897b,#00695c)">
            <div class="icon-wrap"><i class="fe fe-shopping-bag"></i></div>
            <div>
                <div class="stat-val">{{ $countToday }}</div>
                <div class="stat-lbl">Transactions</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#e65100,#bf360c)">
            <div class="icon-wrap"><i class="fe fe-rotate-ccw"></i></div>
            <div>
                <div class="stat-val">{{ config('app.currency') }} {{ number_format($refundsToday, 2) }}</div>
                <div class="stat-lbl">Refunds Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#6a1b9a,#4a148c)">
            <div class="icon-wrap"><i class="fe fe-bar-chart-2"></i></div>
            <div>
                <div class="stat-val">{{ config('app.currency') }} {{ number_format($avgOrder, 2) }}</div>
                <div class="stat-lbl">Avg. Order</div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="filter-card">
    <form method="GET" action="{{ route('pos.sales.index') }}" class="row align-items-end g-2">
        <div class="col-md-2 col-6">
            <label class="tx-11 font-weight-bold text-muted text-uppercase">From</label>
            <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2 col-6">
            <label class="tx-11 font-weight-bold text-muted text-uppercase">To</label>
            <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2 col-6">
            <label class="tx-11 font-weight-bold text-muted text-uppercase">Cashier</label>
            <select name="cashier_id" class="form-control form-control-sm">
                <option value="">All Cashiers</option>
                @foreach($cashiers as $id => $name)
                    <option value="{{ $id }}" {{ request('cashier_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="tx-11 font-weight-bold text-muted text-uppercase">Payment</label>
            <select name="payment_method" class="form-control form-control-sm">
                <option value="">All Methods</option>
                <option value="cash" {{ request('payment_method')=='cash'?'selected':'' }}>Cash</option>
                <option value="card" {{ request('payment_method')=='card'?'selected':'' }}>Card</option>
                <option value="bank_transfer" {{ request('payment_method')=='bank_transfer'?'selected':'' }}>Bank Transfer</option>
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="tx-11 font-weight-bold text-muted text-uppercase">Status</label>
            <select name="status" class="form-control form-control-sm">
                <option value="">All Statuses</option>
                <option value="completed" {{ request('status')=='completed'?'selected':'' }}>Completed</option>
                <option value="refunded"  {{ request('status')=='refunded'?'selected':'' }}>Refunded</option>
                <option value="voided"    {{ request('status')=='voided'?'selected':'' }}>Voided</option>
            </select>
        </div>
        <div class="col-md-2 col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fe fe-filter mr-1"></i>Filter</button>
            <a href="{{ route('pos.sales.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fe fe-x"></i></a>
        </div>
    </form>
</div>

{{-- Sales Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="pl-4">Sale #</th>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Cashier</th>
                        <th>Items</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th class="text-right">Total</th>
                        <th class="text-center pr-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td class="pl-4 font-weight-bold text-muted">{{ $sale->sale_number }}</td>
                        <td>
                            <div class="font-weight-bold tx-13">{{ $sale->created_at->format('M d, Y') }}</div>
                            <small class="text-muted">{{ $sale->created_at->format('H:i') }}</small>
                        </td>
                        <td>
                            @if($sale->customer)
                                <span class="font-weight-bold">{{ $sale->customer->name }}</span>
                            @else
                                <span class="text-muted">Walk-in</span>
                            @endif
                        </td>
                        <td><small>{{ $sale->user?->name ?? '—' }}</small></td>
                        <td><span class="badge badge-light border">{{ $sale->items->count() }} items</span></td>
                        <td>
                            @php $pm = $sale->payment_method; @endphp
                            @if($pm === 'cash')
                                <span class="badge badge-success-transparent"><i class="fe fe-dollar-sign mr-1"></i>Cash</span>
                            @elseif($pm === 'card')
                                <span class="badge badge-info-transparent"><i class="fe fe-credit-card mr-1"></i>Card</span>
                            @else
                                <span class="badge badge-warning-transparent"><i class="fe fe-smartphone mr-1"></i>Transfer</span>
                            @endif
                        </td>
                        <td>
                            @php $s = $sale->status; @endphp
                            @if($s === 'completed')  <span class="badge badge-status-completed rounded-pill px-3 py-1">Completed</span>
                            @elseif($s === 'refunded') <span class="badge badge-status-refunded rounded-pill px-3 py-1">Refunded</span>
                            @elseif($s === 'voided')   <span class="badge badge-status-voided rounded-pill px-3 py-1">Voided</span>
                            @else <span class="badge badge-secondary">{{ ucfirst($s) }}</span> @endif
                        </td>
                        <td class="text-right font-weight-bold text-primary tx-15">
                            {{ config('app.currency') }} {{ number_format($sale->total, 2) }}
                        </td>
                        <td class="text-center pr-4">
                            <a href="{{ route('pos.sales.show', $sale) }}" class="btn btn-sm btn-info-light action-btn mr-1" title="View"><i class="fe fe-eye"></i></a>
                            @if(!$sale->isVoided() && !$sale->isRefunded())
                                <button class="btn btn-sm btn-warning-light action-btn mr-1" title="Refund"
                                    onclick="openRefund({{ $sale->id }}, '{{ $sale->sale_number }}', {{ $sale->total }})">
                                    <i class="fe fe-rotate-ccw"></i>
                                </button>
                                <button class="btn btn-sm btn-danger-light action-btn" title="Void"
                                    onclick="openVoid({{ $sale->id }}, '{{ $sale->sale_number }}')">
                                    <i class="fe fe-slash"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="fe fe-shopping-cart tx-40 text-muted d-block mb-2"></i>
                            <p class="text-muted mb-3">No sales recorded yet</p>
                            <a href="{{ route('pos.terminal') }}" class="btn btn-primary btn-sm">Start First Sale</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($sales->hasPages())
    <div class="card-footer bg-white border-top pt-3">
        {{ $sales->links() }}
    </div>
    @endif
</div>

{{-- Refund Modal --}}
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title"><i class="fe fe-rotate-ccw mr-2"></i>Issue Refund — <span id="refundSaleNum"></span></h5>
                <button class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4">
                <div class="form-group">
                    <label class="font-weight-bold">Refund Amount <small class="text-muted">(max: <span id="refundMax"></span>)</small></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">{{ config('app.currency') }}</span></div>
                        <input type="number" id="refundAmount" class="form-control font-weight-bold" step="0.01">
                    </div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Refund Method</label>
                    <select id="refundMethod" class="form-control">
                        <option value="original">Original Method</option>
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
                    <label class="custom-control-label" for="refundRestock">Restore stock to inventory</label>
                </div>
                <div id="refundError" class="alert alert-danger mt-3 d-none"></div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button class="btn btn-warning px-4" onclick="submitRefund()"><i class="fe fe-check mr-1"></i>Confirm Refund</button>
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
                <p class="text-muted mb-3">You are about to void <strong id="voidSaleNum"></strong>. This action cannot be undone.</p>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Reason <span class="text-danger">*</span></label>
                    <textarea id="voidReason" class="form-control" rows="2" placeholder="Reason for voiding..."></textarea>
                </div>
                <div id="voidError" class="alert alert-danger mt-3 d-none"></div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button class="btn btn-danger px-4" onclick="submitVoid()"><i class="fe fe-trash-2 mr-1"></i>Void Sale</button>
            </div>
        </div>
    </div>
</div>

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

        if (!reason) { errEl.textContent = 'Please enter a reason.'; errEl.classList.remove('d-none'); return; }

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
        if (!reason) { errEl.textContent = 'Please enter a reason.'; errEl.classList.remove('d-none'); return; }

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
