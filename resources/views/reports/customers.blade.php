@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Reports</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Customer Report</span>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="row mb-4">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">New Customers (Period)</h6>
                <h2 class="text-primary">{{ $newCustomers }}</h2>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Total Customers</h6>
                <h2 class="text-info">{{ $topCustomers->count() }}+</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title mg-b-0">Top Customers by Spending</h4>
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
                    <table class="table table-hover mb-0 text-md-nowrap">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topCustomers as $index => $customer)
                            <tr>
                                <td><strong>{{ $index + 1 }}</strong></td>
                                <td>
                                    <strong>{{ $customer->name }}</strong>
                                </td>
                                <td>{{ $customer->email }}</td>
                                <td>{{ $customer->phone ?? '-' }}</td>
                                <td><span class="badge badge-light">{{ $customer->order_count ?? 0 }}</span></td>
                                <td class="font-weight-bold text-success">
                                    {{ config('app.currency') }} {{ number_format($customer->total_spent ?? 0, 2) }}
                                </td>
                                <td>{{ $customer->created_at->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">No customer data found</td>
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
