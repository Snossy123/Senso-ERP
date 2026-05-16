@extends('layouts.master')

@section('page-header')
<div class="pos-shifts-header breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex flex-wrap align-items-center">
            <h4 class="content-title mb-0 my-auto">POS</h4>
            <span class="text-muted mt-1 tx-13 mx-2 mb-0">/ {{ __('messages.sidebar.shift_management') }}</span>
        </div>
        <small class="text-muted d-block mt-1">Liquidity stewardship · reconcile before bank deposits</small>
    </div>
</div>
@endsection

@section('css')
<link href="{{ asset('css/pos/shift-reports.css') }}?v=1" rel="stylesheet">
@endsection

@section('content')
@php
    $tenantId = auth()->user()->tenant_id;
    $collection = $shifts->getCollection();
    $openOnPage = $collection->where('status', 'open')->count();
    $openTotal = \App\Models\PosShift::where('tenant_id', $tenantId)->where('status', 'open')->count();
    $varianceRows = $collection->where('status', 'closed')->filter(fn ($s) => abs((float) $s->variance) >= 0.01);
    $netVariance = (float) $collection->where('status', 'closed')->sum('variance');
    $currency = config('app.currency');
@endphp

<div class="pos-shifts-page">
    <div class="pos-shifts-kpis">
        <article class="pos-shifts-kpi">
            <span class="pos-shifts-kpi__icon pos-shifts-kpi__icon--open" aria-hidden="true"><i class="fe fe-monitor"></i></span>
            <div>
                <div class="pos-shifts-kpi__val">{{ $openTotal }}</div>
                <div class="pos-shifts-kpi__lbl">Open lanes</div>
                <div class="pos-shifts-kpi__sub">{{ $openOnPage }} on this page</div>
            </div>
        </article>
        <article class="pos-shifts-kpi">
            <span class="pos-shifts-kpi__icon pos-shifts-kpi__icon--total" aria-hidden="true"><i class="fe fe-layers"></i></span>
            <div>
                <div class="pos-shifts-kpi__val">{{ number_format($shifts->total()) }}</div>
                <div class="pos-shifts-kpi__lbl">Shift records</div>
                <div class="pos-shifts-kpi__sub">Matching filters</div>
            </div>
        </article>
        <article class="pos-shifts-kpi">
            <span class="pos-shifts-kpi__icon pos-shifts-kpi__icon--warn" aria-hidden="true"><i class="fe fe-alert-triangle"></i></span>
            <div>
                <div class="pos-shifts-kpi__val">{{ $varianceRows->count() }}</div>
                <div class="pos-shifts-kpi__lbl">With variance</div>
                <div class="pos-shifts-kpi__sub">On this page</div>
            </div>
        </article>
        <article class="pos-shifts-kpi">
            <span class="pos-shifts-kpi__icon pos-shifts-kpi__icon--var" aria-hidden="true"><i class="fe fe-trending-down"></i></span>
            <div>
                <div class="pos-shifts-kpi__val">{{ $currency }} {{ number_format($netVariance, 2) }}</div>
                <div class="pos-shifts-kpi__lbl">Net variance</div>
                <div class="pos-shifts-kpi__sub">Closed shifts · page</div>
            </div>
        </article>
    </div>

    <div class="pos-shifts-filters">
        <form method="GET" action="{{ route('pos.shifts.index') }}" class="pos-shifts-filters__grid">
            <div>
                <label class="small font-weight-bold text-muted text-uppercase d-block" for="shift-cashier">Cashier</label>
                <select id="shift-cashier" name="user_id" class="form-control form-control-sm">
                    <option value="">All cashiers</option>
                    @foreach($cashiers as $id => $name)
                        <option value="{{ $id }}" @selected(request('user_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="small font-weight-bold text-muted text-uppercase d-block" for="shift-status">Status</label>
                <select id="shift-status" name="status" class="form-control form-control-sm">
                    <option value="">All</option>
                    <option value="open" @selected(request('status') === 'open')>Open</option>
                    <option value="closed" @selected(request('status') === 'closed')>Closed</option>
                </select>
            </div>
            <div>
                <label class="small font-weight-bold text-muted text-uppercase d-block" for="shift-from">From</label>
                <input type="date" id="shift-from" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
            </div>
            <div>
                <label class="small font-weight-bold text-muted text-uppercase d-block" for="shift-to">To</label>
                <input type="date" id="shift-to" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
            </div>
            <div class="pos-shifts-filters__actions">
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fe fe-filter me-1"></i> Apply
                </button>
                <a href="{{ route('pos.shifts.index') }}" class="btn btn-outline-secondary btn-sm px-3">Reset</a>
            </div>
        </form>
    </div>

    <div class="pos-shifts-card">
        @if($shifts->isEmpty())
            <div class="pos-shifts-empty">
                <i class="fe fe-clock d-block"></i>
                <p class="text-muted mb-0">No shifts match your filters.</p>
            </div>
        @else
            <div class="d-md-none p-3">
                @foreach($shifts as $shift)
                    <article class="pos-shifts-mobile-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="font-weight-bold">{{ $shift->terminal_id }}</span>
                            @if($shift->status === 'closed')
                                @php $v = (float) $shift->variance; @endphp
                                <span class="pos-shifts-var {{ abs($v) < 0.01 ? 'pos-shifts-var--ok' : 'pos-shifts-var--bad' }}">
                                    {{ number_format($v, 2) }}
                                </span>
                            @else
                                <span class="pos-shifts-status pos-shifts-status--open">Open</span>
                            @endif
                        </div>
                        <small class="text-muted d-block mb-2">{{ $shift->user?->name }}</small>
                        <div class="d-flex justify-content-between tx-13 text-muted mb-3">
                            <span>{{ $shift->opened_at->format('M d · H:i') }}</span>
                            <span>{{ $shift->closed_at ? $shift->closed_at->format('M d · H:i') : 'Active' }}</span>
                        </div>
                        <a href="{{ route('pos.shifts.show', $shift) }}" class="btn btn-sm btn-outline-primary btn-block">Analyze</a>
                    </article>
                @endforeach
            </div>

            <div class="pos-shifts-table-wrap d-none d-md-block">
                <table class="pos-shifts-table">
                    <thead>
                        <tr>
                            <th class="col-terminal">Terminal</th>
                            <th>Cashier</th>
                            <th>Opened</th>
                            <th>Closed</th>
                            <th class="col-money">Open float</th>
                            <th class="col-money">Close</th>
                            <th class="col-money">Expected</th>
                            <th class="col-variance">Variance</th>
                            <th>Status</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shifts as $shift)
                            <tr>
                                <td class="col-terminal">{{ $shift->terminal_id }}</td>
                                <td>{{ $shift->user?->name ?? '—' }}</td>
                                <td class="col-money">{{ $shift->opened_at->format('M d, H:i') }}</td>
                                <td class="col-money">{{ $shift->closed_at ? $shift->closed_at->format('M d, H:i') : '—' }}</td>
                                <td class="col-money">{{ number_format((float) $shift->opening_float, 2) }}</td>
                                <td class="col-money">{{ $shift->closing_float !== null ? number_format((float) $shift->closing_float, 2) : '—' }}</td>
                                <td class="col-money">{{ $shift->expected_cash !== null ? number_format((float) $shift->expected_cash, 2) : '—' }}</td>
                                <td class="col-variance">
                                    @if($shift->status === 'closed')
                                        @php $v = (float) $shift->variance; @endphp
                                        <span class="pos-shifts-var {{ abs($v) < 0.01 ? 'pos-shifts-var--ok' : 'pos-shifts-var--bad' }}">
                                            {{ number_format($v, 2) }}
                                        </span>
                                    @else
                                        <span class="text-muted tx-13">Running</span>
                                    @endif
                                </td>
                                <td>
                                    @if($shift->status === 'open')
                                        <span class="pos-shifts-status pos-shifts-status--open">Open</span>
                                    @else
                                        <span class="pos-shifts-status pos-shifts-status--closed">Closed</span>
                                    @endif
                                </td>
                                <td class="col-actions">
                                    <a href="{{ route('pos.shifts.show', $shift) }}" class="btn btn-sm btn-outline-primary">Analyze</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($shifts->hasPages())
            <div class="pos-shifts-pagination">{{ $shifts->links() }}</div>
        @endif
    </div>
</div>
@endsection
