@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Reports</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Sales Report</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <div class="btn-group mb-2 mr-2">
            <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-download"></i> Export
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item" href="{{ route('exports.sales.pdf', request()->query()) }}"><i class="fa fa-file-pdf-o"></i> Export PDF</a>
                <a class="dropdown-item" href="{{ route('exports.sales.excel', request()->query()) }}"><i class="fa fa-file-excel-o"></i> Export Excel</a>
            </div>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="row mb-4">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Total Revenue</h6>
                <h2 class="text-primary">{{ config('app.currency') }} {{ number_format($totalRevenue, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Total Tax</h6>
                <h2 class="text-info">{{ config('app.currency') }} {{ number_format($totalTax, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Total Discount</h6>
                <h2 class="text-danger">{{ config('app.currency') }} {{ number_format($totalDiscount, 2) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between">
                    <h4 class="card-title mg-b-0">Sales Transactions</h4>
                </div>
                <p class="tx-12 tx-gray-500 mb-2">Filter and view sales data.</p>
            </div>
            <div class="card-body">
                <form method="GET" class="row mb-4">
                    <div class="col-md-3">
                        <label>Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label>Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-3">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="">All</option>
                            <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>Card</option>
                            <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-md-nowrap">
                        <thead>
                            <tr>
                                <th>Sale #</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Payment</th>
                                <th>Subtotal</th>
                                <th>Tax</th>
                                <th>Total</th>
                                <th>Cashier</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sales as $sale)
                            <tr>
                                <td><strong>{{ $sale->sale_number }}</strong></td>
                                <td>{{ $sale->created_at->format('M d, Y H:i') }}</td>
                                <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                                <td>
                                    <span class="badge badge-{{ $sale->payment_method == 'cash' ? 'success' : ($sale->payment_method == 'card' ? 'info' : 'warning') }}">
                                        {{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}
                                    </span>
                                </td>
                                <td>{{ config('app.currency') }} {{ number_format($sale->subtotal, 2) }}</td>
                                <td>{{ config('app.currency') }} {{ number_format($sale->tax_amount, 2) }}</td>
                                <td class="font-weight-bold text-primary">{{ config('app.currency') }} {{ number_format($sale->total, 2) }}</td>
                                <td>{{ $sale->user?->name }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <p class="text-muted">No sales found</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $sales->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{URL::asset('assets/plugins/chart.js/Chart.bundle.min.js')}}"></script>
@endsection
