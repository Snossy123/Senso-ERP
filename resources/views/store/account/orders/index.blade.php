@extends('store.layouts.portal')
@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-lg-3">
            <div class="list-group shadow-sm">
                <a href="{{ route('store.account.dashboard') }}" class="list-group-item list-group-item-action">
                    <i class="fa fa-home me-2"></i> Dashboard
                </a>
                <a href="{{ route('store.account.orders') }}" class="list-group-item list-group-item-action active bg-primary border-0">
                    <i class="fa fa-box me-2"></i> My Orders
                </a>
                <a href="{{ route('store.account.profile') }}" class="list-group-item list-group-item-action">
                    <i class="fa fa-user me-2"></i> Profile
                </a>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="mb-0">My Orders</h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                <tr>
                                    <td><strong>{{ $order->order_number }}</strong></td>
                                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                                    <td>{{ $order->items->count() }} items</td>
                                    <td class="fw-bold text-primary">{{ config('app.currency') }} {{ number_format($order->total, 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $order->status_color }}">{{ ucfirst($order->status) }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('store.account.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="fa fa-shopping-bag fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No orders yet</p>
                                        <a href="{{ route('store.index') }}" class="btn btn-primary">Start Shopping</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">
                        {{ $orders->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
