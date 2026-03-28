@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Reports</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Overview</span>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card overflow-hidden sales-card bg-primary-gradient">
            <div class="pl-3 pt-3 pr-3 pb-2">
                <div class="">
                    <h6 class="mb-3 tx-12 text-white">TOTAL REVENUE</h6>
                </div>
                <div class="pb-0 mt-0">
                    <div class="d-flex">
                        <div class="">
                            <h4 class="tx-20 font-weight-bold mb-1 text-white">{{ config('app.currency') }} {{ number_format($stats['totalSales'], 2) }}</h4>
                            <p class="mb-0 tx-12 text-white op-7">All time sales</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card overflow-hidden sales-card bg-danger-gradient">
            <div class="pl-3 pt-3 pr-3 pb-2">
                <div class="">
                    <h6 class="mb-3 tx-12 text-white">LOW STOCK ITEMS</h6>
                </div>
                <div class="pb-0 mt-0">
                    <div class="d-flex">
                        <div class="">
                            <h4 class="tx-20 font-weight-bold mb-1 text-white">{{ $stats['lowStockCount'] }}</h4>
                            <p class="mb-0 tx-12 text-white op-7">Need restocking</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card overflow-hidden sales-card bg-success-gradient">
            <div class="pl-3 pt-3 pr-3 pb-2">
                <div class="">
                    <h6 class="mb-3 tx-12 text-white">TODAY'S SALES</h6>
                </div>
                <div class="pb-0 mt-0">
                    <div class="d-flex">
                        <div class="">
                            <h4 class="tx-20 font-weight-bold mb-1 text-white">{{ config('app.currency') }} {{ number_format($stats['todaySales'], 2) }}</h4>
                            <p class="mb-0 tx-12 text-white op-7">{{ now()->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card overflow-hidden sales-card bg-warning-gradient">
            <div class="pl-3 pt-3 pr-3 pb-2">
                <div class="">
                    <h6 class="mb-3 tx-12 text-white">PENDING ORDERS</h6>
                </div>
                <div class="pb-0 mt-0">
                    <div class="d-flex">
                        <div class="">
                            <h4 class="tx-20 font-weight-bold mb-1 text-white">{{ $stats['pendingOrders'] }}</h4>
                            <p class="mb-0 tx-12 text-white op-7">Awaiting processing</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title mg-b-0">Report Modules</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <a href="{{ route('reports.sales') }}" class="btn btn-outline-primary btn-block btn-lg py-4">
                            <i class="fa fa-chart-line fa-2x mb-2"></i><br>
                            <strong>Sales Report</strong><br>
                            <small>Revenue, orders, trends</small>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('reports.inventory') }}" class="btn btn-outline-danger btn-block btn-lg py-4">
                            <i class="fa fa-boxes fa-2x mb-2"></i><br>
                            <strong>Inventory Report</strong><br>
                            <small>Stock levels, low stock</small>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('reports.profit') }}" class="btn btn-outline-success btn-block btn-lg py-4">
                            <i class="fa fa-dollar-sign fa-2x mb-2"></i><br>
                            <strong>Profit Analysis</strong><br>
                            <small>Margins, top products</small>
                        </a>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <a href="{{ route('reports.customers') }}" class="btn btn-outline-info btn-block btn-lg py-4">
                            <i class="fa fa-users fa-2x mb-2"></i><br>
                            <strong>Customer Report</strong><br>
                            <small>Top customers, new signups</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Quick Stats</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <td>Total Products</td>
                            <td class="font-weight-bold">{{ $stats['totalProducts'] }}</td>
                        </tr>
                        <tr>
                            <td>Total Customers</td>
                            <td class="font-weight-bold">{{ $stats['totalCustomers'] }}</td>
                        </tr>
                        <tr>
                            <td>Total Orders</td>
                            <td class="font-weight-bold">{{ $stats['totalOrders'] }}</td>
                        </tr>
                        <tr>
                            <td>Monthly Sales</td>
                            <td class="font-weight-bold text-success">{{ config('app.currency') }} {{ number_format($stats['monthlySales'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
