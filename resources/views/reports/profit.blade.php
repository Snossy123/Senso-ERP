@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Reports</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Profit Analysis</span>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Total Revenue</h6>
                <h2 class="text-success">{{ config('app.currency') }} {{ number_format($totalRevenue, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Tax Collected</h6>
                <h2 class="text-info">{{ config('app.currency') }} {{ number_format($totalTax, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Net Sales</h6>
                <h2 class="text-primary">{{ config('app.currency') }} {{ number_format($totalSales, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Avg Order Value</h6>
                <h2 class="text-warning">{{ config('app.currency') }} {{ number_format($avgOrderValue, 2) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title mg-b-0">Profit Analysis</h4>
                <p class="tx-12 tx-gray-500 mb-2">Period: {{ $dateFrom->format('M d, Y') }} - {{ $dateTo->format('M d, Y') }}</p>
            </div>
            <div class="card-body">
                <form method="GET" class="row mb-4">
                    <div class="col-md-4">
                        <label>Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <label>Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $dateTo->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Update</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <tbody>
                            <tr>
                                <td>Total Orders</td>
                                <td class="font-weight-bold">{{ $orderCount }}</td>
                            </tr>
                            <tr>
                                <td>Gross Revenue</td>
                                <td class="font-weight-bold">{{ config('app.currency') }} {{ number_format($totalRevenue, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Tax</td>
                                <td class="text-danger">-{{ config('app.currency') }} {{ number_format($totalTax, 2) }}</td>
                            </tr>
                            <tr class="table-success">
                                <td><strong>Net Revenue</strong></td>
                                <td class="font-weight-bold text-success"><strong>{{ config('app.currency') }} {{ number_format($totalSales, 2) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title mg-b-0">Top Selling Products</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-md-nowrap">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts as $index => $product)
                            <tr>
                                <td><strong>{{ $index + 1 }}</strong></td>
                                <td>{{ $product->name }}</td>
                                <td><code>{{ $product->sku }}</code></td>
                                <td>{{ $product->category?->name }}</td>
                                <td class="font-weight-bold">{{ $product->total_sold ?? 0 }}</td>
                                <td class="text-success font-weight-bold">
                                    {{ config('app.currency') }} {{ number_format(($product->total_sold ?? 0) * $product->selling_price, 2) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">No sales data for this period</td>
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
