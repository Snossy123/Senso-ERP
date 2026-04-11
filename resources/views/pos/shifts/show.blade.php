@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">POS</h4>
            <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ Shift Report</span>
            <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ {{ $shift->terminal_id }} ({{ $shift->user?->name }})</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <a href="{{ route('pos.shifts.index') }}" class="btn btn-secondary mr-2"><i class="fe fe-arrow-left"></i> Back</a>
        <button onclick="window.print()" class="btn btn-primary"><i class="fe fe-printer"></i> Print Report</button>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    {{-- Summary Cards --}}
    <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12">
        <div class="card overflow-hidden sales-card bg-primary-gradient">
            <div class="pl-3 pt-3 pr-3 pb-2 pt-0">
                <div class="">
                    <h6 class="mb-3 tx-12 text-white">EXPECTED CASH</h6>
                </div>
                <div class="pb-0 mt-0">
                    <div class="d-flex">
                        <div class="">
                            <h4 class="tx-20 font-weight-bold mb-1 text-white">{{ number_format($shift->expected_cash, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12">
        <div class="card overflow-hidden sales-card bg-danger-gradient">
            <div class="pl-3 pt-3 pr-3 pb-2 pt-0">
                <div class="">
                    <h6 class="mb-3 tx-12 text-white">CLOSING CASH</h6>
                </div>
                <div class="pb-0 mt-0">
                    <div class="d-flex">
                        <div class="">
                            <h4 class="tx-20 font-weight-bold mb-1 text-white">{{ number_format($shift->closing_float, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12">
        <div class="card overflow-hidden sales-card bg-success-gradient">
            <div class="pl-3 pt-3 pr-3 pb-2 pt-0">
                <div class="">
                    <h6 class="mb-3 tx-12 text-white">TOTAL SALES</h6>
                </div>
                <div class="pb-0 mt-0">
                    <div class="d-flex">
                        <div class="">
                            <h4 class="tx-20 font-weight-bold mb-1 text-white">{{ number_format($shift->totalSales(), 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12">
        <div class="card overflow-hidden sales-card {{ $shift->variance == 0 ? 'bg-info-gradient' : 'bg-warning-gradient' }}">
            <div class="pl-3 pt-3 pr-3 pb-2 pt-0">
                <div class="">
                    <h6 class="mb-3 tx-12 text-white">VARIANCE</h6>
                </div>
                <div class="pb-0 mt-0">
                    <div class="d-flex">
                        <div class="">
                            <h4 class="tx-20 font-weight-bold mb-1 text-white">{{ number_format($shift->variance, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header pb-0 border-bottom">
                <h5 class="mb-2">Shift Details</h5>
                <p class="text-muted small">Comprehensive breakdown of terminal activity during this shift.</p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 border-right">
                        <h6 class="font-weight-bold mb-3"><i class="fe fe-info mr-2"></i>General Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><th class="pl-0 text-muted">Status</th><td class="text-right font-weight-bold text-uppercase @if($shift->status == 'open') text-success @endif">{{ $shift->status }}</td></tr>
                            <tr><th class="pl-0 text-muted">Cashier</th><td class="text-right font-weight-bold">{{ $shift->user?->name }}</td></tr>
                            <tr><th class="pl-0 text-muted">Terminal</th><td class="text-right">{{ $shift->terminal_id }}</td></tr>
                            <tr><th class="pl-0 text-muted">Opened At</th><td class="text-right">{{ $shift->opened_at->format('M d, Y H:i:s') }}</td></tr>
                            <tr><th class="pl-0 text-muted">Closed At</th><td class="text-right">{{ $shift->closed_at ? $shift->closed_at->format('M d, Y H:i:s') : 'Still active' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold mb-3"><i class="fe fe-activity mr-2"></i>Financial Reconciliation</h6>
                        <table class="table table-sm table-borderless">
                            <tr><th class="pl-0 text-muted">Opening Float</th><td class="text-right">{{ number_format($shift->opening_float, 2) }}</td></tr>
                            <tr><th class="pl-0 text-muted">Cash Sales (+)</th><td class="text-right font-weight-bold text-success">{{ number_format($shift->totalCashSales(), 2) }}</td></tr>
                            <tr><th class="pl-0 text-muted">Expected in Drawer</th><td class="text-right font-weight-bold">{{ number_format($shift->expected_cash, 2) }}</td></tr>
                            <tr class="border-top"><th class="pl-0 text-muted pt-2">Actual Cash Counted</th><td class="text-right pt-2 font-weight-bold text-primary">{{ number_format($shift->closing_float, 2) }}</td></tr>
                            <tr><th class="pl-0 text-muted">Variance</th><td class="text-right font-weight-bold {{ $shift->variance == 0 ? 'text-success' : 'text-danger' }}">{{ number_format($shift->variance, 2) }}</td></tr>
                        </table>
                    </div>
                </div>

                @if($shift->notes)
                <div class="alert alert-light mt-4">
                    <h6 class="font-weight-bold mb-1">Shift Notes:</h6>
                    <p class="mb-0 text-muted">{{ $shift->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header pb-0 border-bottom">
                <h5 class="mb-0">Transactions in this Shift</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Sale Number</th>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Method</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = 1; @endphp
                            @forelse($shift->sales as $sale)
                            <tr>
                                <td>{{ $i++ }}</td>
                                <td class="font-weight-bold">{{ $sale->sale_number }}</td>
                                <td>{{ $sale->created_at->format('H:i:s') }}</td>
                                <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                                <td><span class="badge badge-light">{{ ucfirst($sale->payment_method) }}</span></td>
                                <td class="text-right font-weight-bold">{{ number_format($sale->total, 2) }}</td>
                                <td class="text-center">
                                    <a href="{{ route('pos.sales.show', $sale) }}" class="btn btn-sm btn-outline-info"><i class="fe fe-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">No transactions recorded yet in this shift.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('css')
<style>
    @media print {
        .breadcrumb-header, .btn, .main-footer, .breadcrumb { display: none !important; }
        .card { border: none !important; box-shadow: none !important; margin-bottom: 20px !important; }
        .content-body { padding: 0 !important; }
    }
</style>
@endsection
