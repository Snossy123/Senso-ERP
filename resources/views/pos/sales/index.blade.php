@extends('layouts.master')

@section('page-header')
<div class="pos-sales-header breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex flex-wrap align-items-center">
            <h4 class="content-title mb-0 my-auto">POS</h4>
            <span class="text-muted mt-1 tx-13 mx-2 mb-0">/ {{ __('messages.sidebar.sales_history') }}</span>
        </div>
        <small class="text-muted d-block mt-1">Operational retail ledger · refunds and voids require permissions</small>
    </div>
    <div class="pos-sales-header__actions my-xl-auto right-content">
        <a href="{{ route('pos.app') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="fe fe-plus me-1"></i> New sale
        </a>
    </div>
</div>
@endsection

@section('css')
<link href="{{ asset('css/pos/sales-history.css') }}?v=2" rel="stylesheet">
@endsection

@section('content')
@php
    $tenantId = auth()->user()->tenant_id;
    $today = \App\Models\Sale::where('tenant_id', $tenantId)->whereDate('created_at', today());
    $totalToday = (clone $today)->where('status', 'completed')->sum('total');
    $countToday = (clone $today)->where('status', 'completed')->count();
    $refundsToday = (clone $today)->where('status', 'refunded')->sum('total');
    $voidsToday = (clone $today)->where('status', 'voided')->count();
    $avgOrder = $countToday > 0 ? $totalToday / $countToday : 0;
    $currency = config('app.currency');

    $initialsFn = static function (?string $name): string {
        if (! $name || trim($name) === '') {
            return '—';
        }
        $parts = preg_split('/\s+/', trim($name));

        return strtoupper(mb_substr($parts[0] ?? '?', 0, 1).mb_substr($parts[count($parts) > 1 ? count($parts) - 1 : 0] ?? '?', 0, 1));
    };
@endphp

<div class="pos-sales-page">
    <div class="pos-sales-kpis">
        <article class="pos-sales-kpi">
            <span class="pos-sales-kpi__icon pos-sales-kpi__icon--gross" aria-hidden="true"><i class="fe fe-trending-up"></i></span>
            <div>
                <div class="pos-sales-kpi__val">{{ $currency }} {{ number_format($totalToday, 2) }}</div>
                <div class="pos-sales-kpi__lbl">Today gross</div>
            </div>
        </article>
        <article class="pos-sales-kpi">
            <span class="pos-sales-kpi__icon pos-sales-kpi__icon--tx" aria-hidden="true"><i class="fe fe-shopping-bag"></i></span>
            <div>
                <div class="pos-sales-kpi__val">{{ number_format($countToday) }}</div>
                <div class="pos-sales-kpi__lbl">Transactions</div>
            </div>
        </article>
        <article class="pos-sales-kpi">
            <span class="pos-sales-kpi__icon pos-sales-kpi__icon--refund" aria-hidden="true"><i class="fe fe-rotate-ccw"></i></span>
            <div>
                <div class="pos-sales-kpi__val">{{ $currency }} {{ number_format($refundsToday, 2) }}</div>
                <div class="pos-sales-kpi__lbl">Refunds booked</div>
            </div>
        </article>
        <article class="pos-sales-kpi">
            <span class="pos-sales-kpi__icon pos-sales-kpi__icon--avg" aria-hidden="true"><i class="fe fe-bar-chart-2"></i></span>
            <div>
                <div class="pos-sales-kpi__val">{{ $currency }} {{ number_format($avgOrder, 2) }}</div>
                <div class="pos-sales-kpi__lbl">Avg basket</div>
                <div class="pos-sales-kpi__sub">{{ $voidsToday }} void{{ $voidsToday === 1 ? '' : 's' }} today</div>
            </div>
        </article>
    </div>

    <div class="pos-sales-filters">
        <form method="GET" action="{{ route('pos.sales.index') }}" class="pos-sales-filters__grid">
            <div>
                <label class="small font-weight-bold text-muted text-uppercase d-block" for="filter-from">From</label>
                <input type="date" id="filter-from" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
            </div>
            <div>
                <label class="small font-weight-bold text-muted text-uppercase d-block" for="filter-to">To</label>
                <input type="date" id="filter-to" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
            </div>
            <div>
                <label class="small font-weight-bold text-muted text-uppercase d-block" for="filter-cashier">Cashier</label>
                <select id="filter-cashier" name="cashier_id" class="form-control form-control-sm">
                    <option value="">All cashiers</option>
                    @foreach($cashiers as $id => $name)
                        <option value="{{ $id }}" @selected(request('cashier_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="small font-weight-bold text-muted text-uppercase d-block" for="filter-payment">Payment</label>
                <select id="filter-payment" name="payment_method" class="form-control form-control-sm">
                    <option value="">Any</option>
                    <option value="cash" @selected(request('payment_method') === 'cash')>Cash</option>
                    <option value="card" @selected(request('payment_method') === 'card')>Card</option>
                    <option value="bank_transfer" @selected(request('payment_method') === 'bank_transfer')>Bank transfer</option>
                </select>
            </div>
            <div>
                <label class="small font-weight-bold text-muted text-uppercase d-block" for="filter-status">Status</label>
                <select id="filter-status" name="status" class="form-control form-control-sm">
                    <option value="">Any</option>
                    <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                    <option value="refunded" @selected(request('status') === 'refunded')>Refunded</option>
                    <option value="voided" @selected(request('status') === 'voided')>Voided</option>
                </select>
            </div>
            <div class="pos-sales-filters__actions">
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fe fe-filter me-1"></i> Apply
                </button>
                <a href="{{ route('pos.sales.index') }}" class="btn btn-outline-secondary btn-sm px-3">Reset</a>
            </div>
        </form>
    </div>

    <div class="pos-sales-card">
        @if($sales->isEmpty())
            <div class="pos-sales-empty">
                <i class="fe fe-shopping-cart d-block"></i>
                <p class="text-muted mb-3">Nothing matches your filters yet.</p>
                <a href="{{ route('pos.app') }}" class="btn btn-primary rounded-pill px-4">Start a new sale</a>
            </div>
        @else
            <div class="d-md-none p-3">
                @foreach($sales as $sale)
                    @php $pm = $sale->payment_method; @endphp
                    <article class="pos-sales-mobile-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="sale-id font-weight-bold">{{ $sale->sale_number }}</span>
                            <span class="font-weight-bold text-primary">{{ $currency }} {{ number_format($sale->total, 2) }}</span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center mb-2" style="gap: 0.35rem;">
                            @if($sale->status === 'completed')
                                <span class="pos-sales-status pos-sales-status--paid">Paid</span>
                            @elseif($sale->status === 'refunded')
                                <span class="pos-sales-status pos-sales-status--refund">Refund</span>
                            @elseif($sale->status === 'voided')
                                <span class="pos-sales-status pos-sales-status--void">Void</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($sale->status) }}</span>
                            @endif
                            @if($pm === 'cash')
                                <span class="pos-sales-chip pos-sales-chip--cash"><i class="fe fe-dollar-sign"></i> Cash</span>
                            @elseif($pm === 'card')
                                <span class="pos-sales-chip pos-sales-chip--card"><i class="fe fe-credit-card"></i> Card</span>
                            @else
                                <span class="pos-sales-chip pos-sales-chip--transfer"><i class="fe fe-smartphone"></i> Transfer</span>
                            @endif
                        </div>
                        <small class="text-muted d-block mb-3">
                            {{ $sale->created_at->format('M d · H:i') }} · {{ $sale->customer?->name ?? 'Walk-in' }}
                        </small>
                        <div class="d-flex justify-content-end flex-wrap" style="gap: 0.35rem;">
                            <a href="{{ route('pos.sales.show', $sale) }}" class="btn btn-sm btn-outline-primary">View</a>
                            @if(! $sale->isVoided() && ! $sale->isRefunded())
                                <button type="button" class="btn btn-sm btn-outline-warning"
                                    onclick="openRefund({{ $sale->id }}, '{{ $sale->sale_number }}', {{ $sale->total }})">Refund</button>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="openVoid({{ $sale->id }}, '{{ $sale->sale_number }}')">Void</button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pos-sales-table-wrap d-none d-md-block">
                <table class="pos-sales-table">
                    <thead>
                        <tr>
                            <th class="col-sale">Sale</th>
                            <th>When</th>
                            <th>Customer</th>
                            <th>Cashier</th>
                            <th class="text-center">Items</th>
                            <th>Tender</th>
                            <th>Status</th>
                            <th class="col-total">Total</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sales as $sale)
                            @php
                                $pm = $sale->payment_method;
                                $status = $sale->status;
                            @endphp
                            <tr>
                                <td class="col-sale">
                                    <span class="sale-id">{{ $sale->sale_number }}</span>
                                </td>
                                <td>
                                    <span class="sale-when">
                                        {{ $sale->created_at->format('M d') }}
                                        <small>{{ $sale->created_at->format('H:i') }}</small>
                                    </span>
                                </td>
                                <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                                <td>
                                    <span class="pos-sales-cashier">
                                        <span class="pos-sales-avatar">{{ $initialsFn($sale->user?->name) }}</span>
                                        <span>{{ \Illuminate\Support\Str::limit($sale->user?->name ?? '—', 18) }}</span>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="pos-sales-items-badge">{{ $sale->items_count }}</span>
                                </td>
                                <td>
                                    @if($pm === 'cash')
                                        <span class="pos-sales-chip pos-sales-chip--cash"><i class="fe fe-dollar-sign"></i> Cash</span>
                                    @elseif($pm === 'card')
                                        <span class="pos-sales-chip pos-sales-chip--card"><i class="fe fe-credit-card"></i> Card</span>
                                    @else
                                        <span class="pos-sales-chip pos-sales-chip--transfer"><i class="fe fe-smartphone"></i> Transfer</span>
                                    @endif
                                </td>
                                <td>
                                    @if($status === 'completed')
                                        <span class="pos-sales-status pos-sales-status--paid">Paid</span>
                                    @elseif($status === 'refunded')
                                        <span class="pos-sales-status pos-sales-status--refund">Refund</span>
                                    @elseif($status === 'voided')
                                        <span class="pos-sales-status pos-sales-status--void">Void</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $status }}</span>
                                    @endif
                                </td>
                                <td class="col-total">{{ $currency }} {{ number_format($sale->total, 2) }}</td>
                                <td class="col-actions">
                                    <div class="pos-sales-actions">
                                        <a href="{{ route('pos.sales.show', $sale) }}" class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="fe fe-eye"></i>
                                        </a>
                                        @if(! $sale->isVoided() && ! $sale->isRefunded())
                                            <button type="button" class="btn btn-sm btn-outline-warning" title="Refund"
                                                onclick="openRefund({{ $sale->id }}, '{{ $sale->sale_number }}', {{ $sale->total }})">
                                                <i class="fe fe-rotate-ccw"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" title="Void"
                                                onclick="openVoid({{ $sale->id }}, '{{ $sale->sale_number }}')">
                                                <i class="fe fe-slash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($sales->hasPages())
            <div class="pos-sales-pagination">{{ $sales->links() }}</div>
        @endif
    </div>
</div>

<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-xl rounded-xl overflow-hidden">
            <div class="modal-header bg-warning text-white rounded-0 py-3">
                <h5 class="modal-title mb-0"><i class="fe fe-rotate-ccw me-2"></i>Refund — <span id="refundSaleNum"></span></h5>
                <button class="close text-white" type="button" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body px-4 py-4 bg-light rounded-0">
                <label class="font-weight-semibold mb-2">Refund amount</label>
                <small class="d-block text-muted mb-2">Maximum <span id="refundMax"></span></small>
                <div class="input-group mb-4 shadow-sm border rounded-xl overflow-hidden">
                    <div class="input-group-prepend"><span class="input-group-text">{{ $currency }}</span></div>
                    <input type="number" id="refundAmount" class="form-control font-weight-semibold bg-white" step="0.01">
                </div>
                <label class="font-weight-semibold">Method</label>
                <select id="refundMethod" class="form-control mb-4 rounded-xl border">
                    <option value="original">Original</option>
                    <option value="cash">Cash</option>
                    <option value="credit">Credit</option>
                </select>
                <label class="font-weight-semibold">Reason <span class="text-danger">*</span></label>
                <textarea id="refundReason" rows="2" class="form-control rounded-xl border mb-3" placeholder="Audit note…"></textarea>
                <div class="custom-control custom-switch mb-3">
                    <input type="checkbox" class="custom-control-input" id="refundRestock" checked>
                    <label class="custom-control-label" for="refundRestock">Restock</label>
                </div>
                <div id="refundError" class="alert alert-danger d-none rounded-lg"></div>
            </div>
            <div class="modal-footer rounded-0 border-0 pb-4">
                <button type="button" class="btn btn-outline-secondary px-4" data-dismiss="modal">Dismiss</button>
                <button type="button" class="btn btn-warning px-5 font-weight-bold rounded-xl" onclick="submitRefund()">Post refund</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="voidModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-xl shadow-lg">
            <div class="modal-header bg-danger text-white rounded-top">
                <h5 class="modal-title font-weight-semibold mb-0">Void sale</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted tx-13 mb-3">Permanent reversal for <strong id="voidSaleNum"></strong></p>
                <textarea id="voidReason" rows="3" class="form-control rounded-lg border mb-3" placeholder="Reason required"></textarea>
                <div id="voidError" class="alert alert-danger rounded-lg d-none"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" data-dismiss="modal" class="btn btn-light px-4">Cancel</button>
                <button type="button" class="btn btn-danger px-4 font-weight-bold rounded-lg" onclick="submitVoid()">Void</button>
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
