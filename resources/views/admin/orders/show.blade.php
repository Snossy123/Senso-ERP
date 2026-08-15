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
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
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
                            <td>{{ config('app.currency') }} {{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right font-weight-bold">{{ config('app.currency') }} {{ number_format($item->total, 2) }}</td>
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
                             <span class="text-muted mr-3">{{ __('shipping.fee') }}</span>
                             <strong>{{ config('app.currency') }} {{ number_format($order->shipping_cost ?? 0, 2) }}</strong>
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
        @include('partials.shipment-card', [
            'shipment' => $order->shipment,
            'shippingIntegration' => $shippingIntegration ?? null,
            'canManage' => auth()->user()->isAdmin() || auth()->user()->hasPermission('orders.process'),
            'refreshUrl' => $order->shipment ? route('admin.orders.shipments.refresh', $order) : null,
            'updateUrl' => $order->shipment ? route('admin.orders.shipments.update', $order) : null,
            'createUrl' => route('admin.orders.shipments.store', $order),
            'createMode' => 'order',
            'shippingRates' => $shippingRates ?? collect(),
            'notes' => $order->notes,
        ])
        @if($order->isCollectible() && (auth()->user()->isAdmin() || auth()->user()->hasPermission('accounting.collect')))
        <div class="card mt-3 border-success">
            <div class="card-body">
                <h5 class="tx-15 font-weight-bold">Record customer payment</h5>
                <p class="text-muted tx-12 mb-3">Status: {{ strtoupper($order->payment_status) }} — posts cash receipt to GL.</p>
                <button type="button" class="btn btn-success btn-block" data-toggle="modal" data-target="#collectOrderModal">Collect payment</button>
            </div>
        </div>
        @endif
        <div class="card mt-3">
             <div class="card-body text-center py-4">
                 <p class="text-muted mb-3 tx-12">Ordered on: {{ $order->created_at->format('F j, Y g:i a') }}</p>
                 <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-light btn-block btn-sm">BACK TO LIST</a>
             </div>
        </div>
    </div>
</div>

@if($order->isCollectible() && (auth()->user()->isAdmin() || auth()->user()->hasPermission('accounting.collect')))
<div class="modal fade" id="collectOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.orders.mark-paid', $order) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Collect — {{ $order->order_number }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Amount: <strong>{{ number_format($order->total, 2) }}</strong></p>
                <div class="form-group">
                    <label>Receipt date</label>
                    <input type="date" name="payment_date" class="form-control" required value="{{ date('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label>Collection method</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="cash" {{ $order->payment_method === 'cash_on_delivery' ? 'selected' : '' }}>Cash</option>
                        <option value="card" {{ $order->payment_method === 'online' ? 'selected' : '' }}>Card</option>
                        <option value="bank_transfer">Bank transfer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Record receipt</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
