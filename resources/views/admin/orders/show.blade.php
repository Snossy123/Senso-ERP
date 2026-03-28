@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Store Portal</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Order Details</span>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="row row-sm">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header pb-0 d-flex justify-content-between">
                <h4 class="card-title mg-b-0">Order Summary: <code>#{{ $order->order_number }}</code></h4>
                <span class="badge badge-{{ $order->status_badge }} tx-14">{{ strtoupper($order->status) }}</span>
            </div>
            <div class="card-body">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="border-0">Product</th>
                            <th class="border-0">Price</th>
                            <th class="border-0 text-center">Qty</th>
                            <th class="border-0 text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ config('app.currency') }} {{ number_format($item->price, 2) }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right font-weight-bold">{{ config('app.currency') }} {{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-light p-4">
                <div class="row">
                    <div class="col-md-6">
                         <h6 class="tx-12 text-uppercase text-muted font-weight-bold">Shipping Info</h6>
                         <p class="mb-1"><strong>{{ $order->customer_name }}</strong></p>
                         <p class="mb-1">{{ $order->customer_phone }} | {{ $order->customer_email }}</p>
                         <p class="mb-0">{{ $order->shipping_address }}, {{ $order->city }}</p>
                    </div>
                    <div class="col-md-6 text-right">
                         <div class="d-flex justify-content-end mb-2">
                             <span class="text-muted mr-3">Subtotal</span>
                             <strong>{{ config('app.currency') }} {{ number_format($order->subtotal, 2) }}</strong>
                         </div>
                         <div class="d-flex justify-content-end mb-2">
                             <span class="text-muted mr-3">Tax ({{ $order->tax_rate }}%)</span>
                             <strong>{{ config('app.currency') }} {{ number_format($order->tax_amount, 2) }}</strong>
                         </div>
                         <div class="d-flex justify-content-end">
                             <h4 class="text-primary tx-22 mb-0 border-top pt-2">{{ config('app.currency') }} {{ number_format($order->total, 2) }}</h4>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header pb-0 border-bottom-0">
                 <h4 class="card-title mg-b-0">Update Order Status</h4>
            </div>
            <div class="card-body p-4 bg-primary text-white text-center">
                 <form action="{{ route('admin.orders.status', $order) }}" method="POST">
                     @csrf @method('PATCH')
                     <p class="mb-4 tx-14 opacity-75">Change the order status as it moves through fulfillment. Stock was already deducted at checkout.</p>
                     
                     <div class="form-group">
                         <select name="status" class="form-control text-dark font-weight-bold">
                             <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>PENDING</option>
                             <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>PROCESSING</option>
                             <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>SHIPPED</option>
                             <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>DELIVERED</option>
                             <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>CANCELLED</option>
                         </select>
                     </div>
                     <button type="submit" class="btn btn-warning btn-block font-weight-bold py-2 mt-4">UPDATE STATUS</button>
                 </form>
            </div>
        </div>
        <div class="card mt-3">
             <div class="card-body text-center py-4">
                 <p class="text-muted mb-3 tx-12">Ordered on: {{ $order->created_at->format('F j, Y g:i a') }}</p>
                 <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-light btn-block btn-sm">BACK TO LIST</a>
             </div>
        </div>
    </div>
</div>
@endsection
