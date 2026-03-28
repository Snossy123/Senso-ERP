@extends('store.layouts.portal')
@section('content')
<div class="row g-5">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-5 mb-4">
            <h3 class="fw-bold mb-5 text-primary"><i class="fa fa-map-marker-alt me-2"></i> Shipping & Billing</h3>
            <form action="{{ route('store.checkout.place') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" class="form-control rounded-pill px-3 bg-light border-0 py-2 mt-1" value="{{ old('customer_name', $customer?->name) }}" placeholder="Your legal name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="customer_email" class="form-control rounded-pill px-3 bg-light border-0 py-2 mt-1" value="{{ old('customer_email', $customer?->email) }}" placeholder="name@example.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="customer_phone" class="form-control rounded-pill px-3 bg-light border-0 py-2 mt-1" value="{{ old('customer_phone', $customer?->phone) }}" placeholder="+1 (555) 000-0000" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Shipping Address <span class="text-danger">*</span></label>
                        <textarea name="shipping_address" class="form-control rounded-4 bg-light border-0 py-3 px-3 mt-1" rows="4" placeholder="Street, Building, Apartment..." required>{{ old('shipping_address', $customer?->address) }}</textarea>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fw-bold">City <span class="text-danger">*</span></label>
                        <input type="text" name="city" class="form-control rounded-pill px-3 bg-light border-0 py-2 mt-1" value="{{ old('city', $customer?->city) }}" placeholder="e.g. New York" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold mb-3">Payment Method <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <label class="flex-grow-1 border rounded-4 p-3 d-flex align-items-center cursor-pointer hover-bg-light transition-all">
                                <input type="radio" name="payment_method" value="cash_on_delivery" class="form-check-input me-3" checked>
                                <div>
                                    <div class="fw-bold">Cash on Delivery</div>
                                    <small class="text-muted">Pay when you receive</small>
                                </div>
                            </label>
                            <label class="flex-grow-1 border rounded-4 p-3 d-flex align-items-center cursor-pointer hover-bg-light transition-all opacity-50 grayscale">
                                <input type="radio" name="payment_method" value="online" class="form-check-input me-3" disabled>
                                <div>
                                    <div class="fw-bold">Online Payment</div>
                                    <small class="text-muted text-primary">Coming Soon</small>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-premium btn-lg w-100 py-3 rounded-pill mt-5 shadow-lg">Confirm Order <i class="fa fa-shopping-bag ms-2"></i></button>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 bg-white shadow-lg rounded-5 p-5 position-sticky" style="top: 120px;">
            <h5 class="fw-bold mb-5"><i class="fa fa-clipboard-list me-2 text-primary"></i> Order Summary</h5>
            @foreach($items as $item)
                <div class="d-flex justify-content-between mb-4 align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-light rounded-3 p-1 me-3 position-relative">
                            @if($item['product']->image)
                                <img src="{{ asset('storage/'.$item['product']->image) }}" width="50" height="50" class="rounded-2" style="object-fit: cover;" alt="">
                            @else
                                <div class="bg-primary-subtle text-primary rounded-2 d-flex align-items-center justify-content-center" width="50" height="50"><i class="fa fa-image"></i></div>
                            @endif
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary border border-2 border-white small">{{ $item['qty'] }}</span>
                        </div>
                        <span class="fw-bold text-dark tx-14">{{ $item['product']->name }}</span>
                    </div>
                    <span class="fw-bold">{{ config('app.currency') }} {{ number_format($item['lineTotal'], 2) }}</span>
                </div>
            @endforeach
            <hr class="border-light-subtle my-4">
            <div class="d-flex justify-content-between mb-3 text-muted">
                <span>Items Subtotal</span>
                <span>{{ config('app.currency') }} {{ number_format($subtotal, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between mb-3 text-muted">
                <span>Shipping Fee</span>
                <span class="text-success fw-bold">FREE</span>
            </div>
            <div class="d-flex justify-content-between mt-5">
                <strong class="fs-4">Order Total</strong>
                <strong class="fs-4 text-primary">{{ config('app.currency') }} {{ number_format($subtotal, 2) }}</strong>
            </div>
        </div>
    </div>
</div>
@endsection
