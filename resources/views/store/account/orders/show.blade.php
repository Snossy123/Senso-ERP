@extends('store.layouts.portal')
@section('content')
<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('store.account.dashboard') }}">Account</a></li>
            <li class="breadcrumb-item"><a href="{{ route('store.account.orders') }}">Orders</a></li>
            <li class="breadcrumb-item active">{{ $order->order_number }}</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between">
                    <h5 class="mb-0">Order {{ $order->order_number }}</h5>
                    <span class="badge bg-{{ $order->status_color }}">{{ ucfirst($order->status) }}</span>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->product?->name ?? 'Product Deleted' }}</strong>
                                </td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">{{ config('app.currency') }} {{ number_format($item->price, 2) }}</td>
                                <td class="text-end fw-bold">{{ config('app.currency') }} {{ number_format($item->quantity * $item->price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span>{{ config('app.currency') }} {{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    @if($order->discount > 0)
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Discount</span>
                        <span>-{{ config('app.currency') }} {{ number_format($order->discount, 2) }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping</span>
                        <span>{{ config('app.currency') }} {{ number_format($order->shipping_cost ?? 0, 2) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total</span>
                        <span class="text-primary">{{ config('app.currency') }} {{ number_format($order->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Order Details</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Order Date:</strong><br>{{ $order->created_at->format('M d, Y H:i') }}</p>
                    <p class="mb-2"><strong>Shipping Address:</strong><br>
                        {{ $order->shipping_name }}<br>
                        {{ $order->shipping_address }}<br>
                        {{ $order->shipping_city }}, {{ $order->shipping_postal_code }}
                    </p>
                    @if($order->notes)
                    <p class="mb-0"><strong>Order Notes:</strong><br>{{ $order->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('store.account.orders') }}" class="btn btn-outline-secondary w-100">
                    <i class="fa fa-arrow-left me-2"></i> Back to Orders
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
