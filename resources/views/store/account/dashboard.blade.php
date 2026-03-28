@extends('store.layouts.portal')
@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-lg-3">
            <div class="list-group shadow-sm">
                <a href="{{ route('store.account.dashboard') }}" class="list-group-item list-group-item-action active bg-primary border-0">
                    <i class="fa fa-home me-2"></i> Dashboard
                </a>
                <a href="{{ route('store.account.orders') }}" class="list-group-item list-group-item-action">
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
                    <h4 class="mb-0">Welcome, {{ $customer->name }}!</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="bg-primary bg-opacity-10 p-4 rounded-4 text-center">
                                <i class="fa fa-shopping-bag fa-2x text-primary mb-2"></i>
                                <h3 class="mb-0">{{ $totalOrders }}</h3>
                                <small class="text-muted">Total Orders</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="bg-success bg-opacity-10 p-4 rounded-4 text-center">
                                <i class="fa fa-check-circle fa-2x text-success mb-2"></i>
                                <h3 class="mb-0">{{ $customer->orders()->where('status', 'completed')->count() }}</h3>
                                <small class="text-muted">Completed</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="bg-warning bg-opacity-10 p-4 rounded-4 text-center">
                                <i class="fa fa-clock fa-2x text-warning mb-2"></i>
                                <h3 class="mb-0">{{ $customer->orders()->whereIn('status', ['pending', 'processing'])->count() }}</h3>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Orders</h5>
                    <a href="{{ route('store.account.orders') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    @forelse($recentOrders as $order)
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $order->order_number }}</strong>
                                <span class="badge bg-{{ $order->status_color }} ms-2">{{ ucfirst($order->status) }}</span>
                                <br>
                                <small class="text-muted">{{ $order->created_at->format('M d, Y') }}</small>
                            </div>
                            <div class="text-end">
                                <strong class="text-primary">{{ config('app.currency') }} {{ number_format($order->total, 2) }}</strong>
                                <br>
                                <a href="{{ route('store.account.orders.show', $order) }}" class="btn btn-sm btn-link">View Details</a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="p-5 text-center">
                        <i class="fa fa-shopping-bag fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No orders yet</p>
                        <a href="{{ route('store.index') }}" class="btn btn-primary">Start Shopping</a>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
