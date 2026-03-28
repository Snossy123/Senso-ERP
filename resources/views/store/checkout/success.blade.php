@extends('store.layouts.portal')
@section('content')
<div class="row min-vh-50 align-items-center justify-content-center text-center py-5">
    <div class="col-md-6">
        <div class="card border-0 shadow-lg rounded-5 p-5 bg-white">
            <div class="mb-5 position-relative">
                <i class="fa fa-shopping-bag text-primary tx-120 opacity-10"></i>
                <i class="fa fa-check-circle text-success tx-80 position-absolute top-50 start-50 translate-middle"></i>
            </div>
            <h2 class="fw-bold mb-4">Order Placed Successfully!</h2>
            <p class="text-muted mb-5 tx-18">Thank you for your purchase. Your order number is <strong class="text-primary tx-22">#{{ $orderNumber }}</strong>.</p>
            <p class="mb-5 text-muted">We have sent the confirmation details to your email. Our team will process your order shortly.</p>
            <div class="d-grid gap-3 mt-5">
                <a href="{{ route('store.index') }}" class="btn btn-premium btn-lg rounded-pill py-3 px-5">Continue Shopping <i class="fa fa-arrow-right ms-2"></i></a>
                @auth('customer')
                    <a href="{{ route('store.account.orders') }}" class="btn btn-outline-primary btn-lg rounded-pill py-3 px-5 border-2">Track Order</a>
                @else
                    <p class="mt-4 tx-14 text-muted">Want to track your orders? <a href="{{ route('store.register') }}" class="text-primary fw-bold text-decoration-none">Create an account</a></p>
                @endauth
            </div>
        </div>
    </div>
</div>
@endsection
