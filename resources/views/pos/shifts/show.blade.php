@extends('layouts.master')

@section('page-header')
<div class="pos-shift-show__header breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <h4 class="content-title mb-0 my-auto">POS</h4>
            <span class="text-muted tx-13">/ {{ __('messages.sidebar.shift_management') }}</span>
            <span class="pos-shift-show__terminal">{{ $shift->terminal_id }}</span>
        </div>
    </div>
    <div class="pos-shift-show__actions right-content">
        <a href="{{ route('pos.shifts.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="fe fe-arrow-left me-1"></i> Back
        </a>
        <button type="button" onclick="window.print()" class="btn btn-primary btn-sm rounded-pill px-3">
            <i class="fe fe-printer me-1"></i> Print
        </button>
    </div>
</div>
@endsection

@section('css')
<link href="{{ asset('css/pos/shift-show.css') }}?v=1" rel="stylesheet">
@endsection

@section('content')
@php
    $s = $shiftSummary ?? [];
    $variance = (float) ($shift->variance ?? 0);
    $expected = $shift->expected_cash !== null ? (float) $shift->expected_cash : null;
    $currency = config('app.currency');
    $varianceOk = $shift->status === 'closed' && abs($variance) < 0.000001;
    $varianceBad = $shift->status === 'closed' && ! $varianceOk;
@endphp

<div class="pos-shift-show">
    <div class="pos-shift-show__layout">
        <div class="pos-shift-show__main">
            <section class="pos-shift-show__panel mb-3">
                <div class="pos-shift-show__panel-head">
                    <h5>Operational timeline</h5>
                </div>
                <div class="pos-shift-show__timeline">
                    <div class="pos-shift-show__step">
                        <span class="pos-shift-show__step-num">01</span>
                        <div>
                            <div class="pos-shift-show__step-label">Shift opened</div>
                            <div class="pos-shift-show__step-value">
                                {{ $shift->opened_at->format('M d, Y · H:i') }} · {{ $shift->user?->name ?? '—' }}
                            </div>
                        </div>
                    </div>
                    @if($shift->closed_at)
                        <div class="pos-shift-show__step">
                            <span class="pos-shift-show__step-num pos-shift-show__step-num--muted">02</span>
                            <div>
                                <div class="pos-shift-show__step-label">Register closed</div>
                                <div class="pos-shift-show__step-value">{{ $shift->closed_at->format('M d, Y · H:i') }}</div>
                            </div>
                        </div>
                    @endif
                    @if($shift->notes)
                        <div class="pos-shift-show__notes"><strong>Notes:</strong> {{ $shift->notes }}</div>
                    @endif
                </div>
            </section>

            <section class="pos-shift-show__panel">
                <div class="pos-shift-show__panel-head">
                    <h5>Transactions</h5>
                    <small>Completed sales linked to this shift</small>
                </div>
                @if($shift->sales->isEmpty())
                    <div class="pos-shift-show__empty">No ticket activity for this shift.</div>
                @else
                    <div class="pos-shift-show__table-wrap d-none d-md-block">
                        <table class="pos-shift-show__table">
                            <thead>
                                <tr>
                                    <th class="col-num">#</th>
                                    <th>Sale</th>
                                    <th>Time</th>
                                    <th>Buyer</th>
                                    <th>Tender</th>
                                    <th class="col-total">Total</th>
                                    <th class="col-actions"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shift->sales as $i => $sale)
                                    <tr>
                                        <td class="col-num">{{ $i + 1 }}</td>
                                        <td class="font-weight-bold">{{ $sale->sale_number }}</td>
                                        <td>{{ $sale->created_at->format('H:i') }}</td>
                                        <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                                        <td>
                                            @if($sale->payment_method === 'cash')
                                                <span class="pos-shift-show__tender pos-shift-show__tender--cash">Cash</span>
                                            @elseif($sale->payment_method === 'card')
                                                <span class="pos-shift-show__tender pos-shift-show__tender--card">Card</span>
                                            @else
                                                <span class="pos-shift-show__tender pos-shift-show__tender--transfer">Transfer</span>
                                            @endif
                                        </td>
                                        <td class="col-total">{{ $currency }} {{ number_format((float) $sale->total, 2) }}</td>
                                        <td class="col-actions">
                                            <a href="{{ route('pos.sales.show', $sale) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-md-none p-3">
                        @foreach($shift->sales as $sale)
                            <article class="pos-shift-show__mobile-txn">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <span class="font-weight-bold">{{ $sale->sale_number }}</span>
                                    <span class="font-weight-bold">{{ $currency }} {{ number_format((float) $sale->total, 2) }}</span>
                                </div>
                                <small class="text-muted d-block mb-2">{{ $sale->created_at->format('H:i') }} · {{ $sale->customer?->name ?? 'Walk-in' }}</small>
                                <a href="{{ route('pos.sales.show', $sale) }}" class="btn btn-sm btn-outline-primary btn-block">View receipt</a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <aside class="pos-shift-show__aside">
            <div class="pos-shift-show__card">
                <div class="pos-shift-show__card-title">Sales summary</div>
                <div class="pos-shift-show__row">
                    <span class="pos-shift-show__row-label">Shift duration</span>
                    <span class="pos-shift-show__row-value">{{ $s['duration_human'] ?? '—' }}</span>
                </div>
                <div class="pos-shift-show__row">
                    <span class="pos-shift-show__row-label">Total orders</span>
                    <span class="pos-shift-show__row-value">{{ $s['total_orders'] ?? 0 }}</span>
                </div>
                <div class="pos-shift-show__row">
                    <span class="pos-shift-show__row-label">Total sales</span>
                    <span class="pos-shift-show__row-value">{{ $currency }} {{ number_format($s['total_sales'] ?? 0, 2) }}</span>
                </div>
                <div class="pos-shift-show__row pos-shift-show__row--danger">
                    <span class="pos-shift-show__row-label">Total refunds</span>
                    <span class="pos-shift-show__row-value">− {{ $currency }} {{ number_format($s['total_refunds'] ?? 0, 2) }}</span>
                </div>
                <div class="pos-shift-show__row pos-shift-show__row--emphasis">
                    <span class="pos-shift-show__row-label">Net sales</span>
                    <span class="pos-shift-show__row-value">{{ $currency }} {{ number_format($s['net_sales'] ?? 0, 2) }}</span>
                </div>
            </div>

            <div class="pos-shift-show__card">
                <div class="pos-shift-show__card-title">Payment summary</div>
                @forelse($s['payment_summary'] ?? [] as $pm)
                    <div class="pos-shift-show__pay-item">
                        <div class="pos-shift-show__pay-head">
                            <span class="pos-shift-show__pay-label">{{ $pm['label'] }} <small class="text-muted font-weight-normal">({{ $pm['txn_count'] }})</small></span>
                            <span class="pos-shift-show__pay-amt">{{ $currency }} {{ number_format($pm['amount'], 2) }}</span>
                        </div>
                        <div class="pos-shift-show__pay-bar"><span style="width: {{ min(100, $pm['percent']) }}%"></span></div>
                    </div>
                @empty
                    <p class="text-muted mb-0 small">No completed sales in this shift.</p>
                @endforelse
            </div>

            <div class="pos-shift-show__variance {{ $varianceOk ? 'pos-shift-show__variance--ok' : ($varianceBad ? 'pos-shift-show__variance--bad' : '') }}">
                <div>
                    <div class="pos-shift-show__card-title mb-1">Variance</div>
                    <small class="text-muted">{{ $shift->status === 'open' ? 'Drawer still open' : 'Reconciliation snapshot' }}</small>
                </div>
                <span class="pos-shift-show__variance-val">{{ number_format($variance, 2) }}</span>
            </div>

            <div class="pos-shift-show__card">
                <div class="pos-shift-show__card-title">Financial bridge</div>
                <div class="pos-shift-show__row">
                    <span class="pos-shift-show__row-label">Opening float</span>
                    <span class="pos-shift-show__row-value">{{ number_format((float) $shift->opening_float, 2) }}</span>
                </div>
                <div class="pos-shift-show__row pos-shift-show__row--success">
                    <span class="pos-shift-show__row-label">Cash receipts</span>
                    <span class="pos-shift-show__row-value">+ {{ number_format((float) $shift->totalCashSales(), 2) }}</span>
                </div>
                <div class="pos-shift-show__row">
                    <span class="pos-shift-show__row-label">Drawer expectation</span>
                    <span class="pos-shift-show__row-value">{{ $expected !== null ? number_format($expected, 2) : '—' }}</span>
                </div>
                <div class="pos-shift-show__row">
                    <span class="pos-shift-show__row-label">Counted cash</span>
                    <span class="pos-shift-show__row-value">{{ $shift->closing_float !== null ? number_format((float) $shift->closing_float, 2) : '—' }}</span>
                </div>
                <div class="pos-shift-show__row pos-shift-show__row--emphasis">
                    <span class="pos-shift-show__row-label">Gross sales</span>
                    <span class="pos-shift-show__row-value">{{ $currency }} {{ number_format((float) $shift->totalSales(), 2) }}</span>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
