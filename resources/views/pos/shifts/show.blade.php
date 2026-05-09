@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between flex-wrap gap-3">
    <div class="my-auto">
        <div class="d-flex flex-wrap">
            <h4 class="content-title mb-0 my-auto">POS</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ Reconciliation</span>
            <span class="badge badge-dark rounded-pill ml-2 py-2 px-3 mt-2 mt-xl-0">{{ $shift->terminal_id }}</span>
        </div>
    </div>
    <div class="d-flex gap-2 right-content flex-wrap">
        <a href="{{ route('pos.shifts.index') }}" class="btn btn-secondary rounded-pill"><i class="fe fe-arrow-left"></i> Back</a>
        <button type="button" onclick="window.print()" class="btn btn-outline-primary rounded-pill"><i class="fe fe-printer"></i> Print</button>
    </div>
</div>
@endsection

@section('css')
<style>
    .recon-fin-card {border-radius:20px;color:#fff;padding:18px;border:1px solid rgba(255,255,255,0.1);margin-bottom:.75rem;box-shadow:0 22px 50px rgba(15,23,42,0.18);}
    .variance-strong {font-weight:900;letter-spacing:-.02em;}
    .timeline-rail-shift {border-left:3px dashed #cdd6f9;padding-left:20px;margin-left:10px;}
    .txn-chip{font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;font-weight:800;padding:.35rem .7rem;border-radius:999px;}
    @media print{
        .breadcrumb-header,.sidebar,.sidebar-mini,.btn,.main-footer{display:none!important;}
        .recon-fin-card{color:#222!important;background:#fff!important;box-shadow:none!important;}
    }
</style>
@endsection

@section('content')
@php
    $variance = (float)($shift->variance ?? 0);
    $expected = $shift->expected_cash !== null ? (float)$shift->expected_cash : null;
@endphp

<div class="row row-sm px-2">
    <div class="col-lg-8">
        <div class="timeline-rail-shift mb-4 pb-4">
            <h5 class="font-weight-bold mb-3">Operational timeline</h5>
            <div class="d-flex align-items-start mb-3"><span class="badge badge-primary-transparent mr-3 mt-1">01</span>
                <div><div class="text-muted tx-11 mb-2 text-uppercase font-weight-semibold">Shift opened</div><div>{{ $shift->opened_at->format('M d, Y H:i:s') }} · cashier {{ $shift->user?->name }}</div></div>
            </div>
            @if($shift->closed_at)
                <div class="d-flex align-items-start mb-3"><span class="badge badge-secondary-transparent mr-3 mt-1">02</span>
                    <div><div class="text-muted tx-11 mb-2 text-uppercase font-weight-semibold">Register closed</div><div>{{ $shift->closed_at->format('M d, Y H:i:s') }}</div></div>
                </div>
            @endif
            @if($shift->notes)
                <div class="rounded-xl border px-4 py-3 bg-light"><strong>Notes:</strong><span class="text-muted">{{ $shift->notes }}</span></div>
            @endif
        </div>

        <div class="card border-0 shadow-lg rounded-xl">
            <div class="card-header bg-white py-4 border-0 px-4">
                <h5 class="mb-2 font-weight-bold">Transactions anchored to lane</h5>
                <small class="text-muted">Deep links reconcile with accounting payloads automatically.</small>
            </div>
            <div class="table-responsive d-none d-md-block">
                <table class="table mb-0 table-hover tx-13">
                    <thead class="bg-light text-muted text-uppercase tx-11"><tr><th>#</th><th>Sale</th><th>Time</th><th>Buyer</th><th>Tender</th><th class="text-right">Total</th><th></th></tr></thead>
                    <tbody>
                        @php $i = 1; @endphp
                        @forelse($shift->sales as $sale)
                        <tr>
                            <td>{{ $i++ }}</td>
                            <td class="font-weight-semibold">{{ $sale->sale_number }}</td>
                            <td>{{ $sale->created_at->format('H:i:s') }}</td>
                            <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                            <td>
                                @if($sale->payment_method === 'cash')
                                    <span class="txn-chip badge badge-success-transparent px-3 py-1">Cash</span>
                                @elseif($sale->payment_method === 'card')
                                    <span class="txn-chip badge badge-info-transparent px-3 py-1">Card</span>
                                @else
                                    <span class="txn-chip badge badge-warning-transparent px-3 py-1">Transfer</span>
                                @endif
                            </td>
                            <td class="text-right font-weight-semibold">{{ number_format((float)$sale->total, 2) }}</td>
                            <td class="text-right"><a href="{{ route('pos.sales.show', $sale) }}" class="btn btn-sm btn-outline-secondary rounded-xl">Ledger</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-5 text-muted">No ticket activity yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-md-none px-4 pb-4">
                @forelse($shift->sales as $sale)
                    <div class="border rounded-xl p-3 mb-3">
                        <div class="font-weight-semibold">{{ $sale->sale_number }}</div>
                        <div class="d-flex justify-content-between tx-13 text-muted mb-3"><span>{{ $sale->created_at->format('H:i:s') }}</span><span>{{ number_format((float)$sale->total, 2) }}</span></div>
                        <a href="{{ route('pos.sales.show', $sale) }}" class="btn btn-sm btn-outline-primary btn-block rounded-xl">View receipt</a>
                    </div>
                @empty
                    <div class="text-center text-muted py-4 mb-0">No ticket activity captured.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="recon-fin-card mb-4" style="background:linear-gradient(135deg,#1d4ed8,#1e293b);">
            <small class="text-uppercase tx-11 opacity-80 d-block mb-4">Variance intelligence</small>
            <div class="d-flex justify-content-between align-items-baseline mb-2">
                <span class="opacity-80">Variance</span>
                <span class="variance-strong tx-36">{{ number_format((float)$variance, 2) }}</span>
            </div>
            <small class="opacity-80">{{ $shift->status === 'open' ? 'Drawer still accumulating sales.' : 'Locked recon snapshot.' }}</small>
        </div>

        <div class="bg-white rounded-xl border shadow-lg p-4" style="position:sticky;top:90px;">
            <h6 class="text-uppercase text-muted mb-4 font-weight-bold">Financial bridge</h6>
            <div class="border-bottom pb-3 mb-3 d-flex justify-content-between"><small class="text-muted">Opening float</small><strong>{{ number_format((float)$shift->opening_float, 2) }}</strong></div>
            <div class="border-bottom pb-3 mb-3 d-flex justify-content-between text-success"><small class="font-weight-semibold text-uppercase">Cash receipts</small><strong>+ {{ number_format((float)$shift->totalCashSales(), 2) }}</strong></div>
            <div class="border-bottom pb-3 mb-3 d-flex justify-content-between"><small class="text-muted">Drawer expectation</small><strong>{{ $expected !== null ? number_format($expected, 2) : '—' }}</strong></div>
            <div class="d-flex justify-content-between mb-4"><small class="text-muted">Counted cash</small><strong class="tx-18">{{ $shift->closing_float !== null ? number_format((float)$shift->closing_float, 2) : 'Awaiting audit' }}</strong></div>
            <div class="rounded-xl p-4 border {{ ($shift->status === 'closed' && abs($variance) < .000001) ? 'border-success bg-success-transparent text-success' : 'border-danger bg-danger-transparent'}}">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="small text-muted text-uppercase font-weight-bold">Total sales</div>
                    <div class="h4 mb-0 font-weight-bold">{{ number_format((float)$shift->totalSales(), 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
