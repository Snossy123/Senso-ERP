@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Inventory</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Purchase Orders</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <div class="pr-1 mb-3 mb-xl-0">
            <a href="{{ route('inventory.purchase-orders.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> New Purchase Order</a>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title mg-b-0">Purchase Orders</h4>
                <p class="tx-12 tx-gray-500 mb-2">Track your procurement and incoming stock.</p>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-md-nowrap">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Supplier</th>
                                <th>Ship To</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                            <tr>
                                <td><strong>{{ $order->reference_no }}</strong></td>
                                <td>{{ $order->supplier->name }}</td>
                                <td>{{ $order->warehouse->name }}</td>
                                <td>{{ $order->order_date->format('Y-m-d') }}</td>
                                <td>{{ config('app.currency') }} {{ number_format($order->total_amount, 2) }}</td>
                                <td>
                                    <span class="badge @if($order->status === 'received') badge-success @elseif($order->status === 'draft') badge-light @elseif($order->status === 'ordered') badge-primary @elseif($order->status === 'cancelled') badge-danger @else badge-info @endif">
                                        {{ strtoupper($order->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('inventory.purchase-orders.show', $order) }}" class="btn btn-sm btn-info-light"><i class="fa fa-eye"></i></a>
                                    @if($order->status === 'draft')
                                        <a href="#" class="btn btn-sm btn-primary-light"><i class="fa fa-edit"></i></a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <p class="text-muted">No purchase orders found</p>
                                    <a href="{{ route('inventory.purchase-orders.create') }}" class="btn btn-primary">Create First Order</a>
                                </td>
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
