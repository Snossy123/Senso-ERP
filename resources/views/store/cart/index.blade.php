@extends('store.layouts.portal')
@section('content')
<div class="row g-5">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h3 class="fw-bold mb-4"><i class="fa fa-shopping-basket me-2 text-primary"></i> Shopping Cart</h3>
            @if(empty($products))
                <div class="text-center py-5">
                    <i class="fa fa-cart-arrow-down tx-60 text-muted mb-4"></i>
                    <h4>Your cart is empty</h4>
                    <p class="text-muted">Looks like you haven't added anything yet.</p>
                    <a href="{{ route('store.index') }}" class="btn btn-premium mt-3">Start Shopping</a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="text-muted text-uppercase small ls-1">
                                <th class="border-0">Product</th>
                                <th class="border-0">Price</th>
                                <th class="border-0">Quantity</th>
                                <th class="border-0 text-end">Total</th>
                                <th class="border-0"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $item)
                            <tr>
                                <td class="py-3 border-0">
                                    <div class="d-flex align-items-center">
                                        @if($item['product']->image)
                                            <img src="{{ asset('storage/'.$item['product']->image) }}" class="rounded-3 shadow-sm me-3" width="70" alt="">
                                        @else
                                            <div class="bg-light rounded-3 me-3 d-flex align-items-center justify-content-center text-muted" width="70" height="70"><i class="fa fa-image"></i></div>
                                        @endif
                                        <div>
                                            <h6 class="mb-0 fw-bold">{{ $item['product']->name }}</h6>
                                            <small class="text-muted">{{ $item['product']->category?->name }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 border-0 fw-500">{{ config('app.currency') }} {{ number_format($item['product']->selling_price, 2) }}</td>
                                <td class="py-3 border-0">
                                    <form action="{{ route('store.cart.update', $item['product']) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <div class="input-group input-group-sm wd-100 shadow-sm rounded overflow-hidden">
                                            <button class="btn btn-light bg-white border-0" name="qty" value="{{ $item['qty'] - 1 }}">-</button>
                                            <input type="text" class="form-control text-center border-0 bg-white" value="{{ $item['qty'] }}" readonly>
                                            <button class="btn btn-light bg-white border-0" name="qty" value="{{ $item['qty'] + 1 }}">+</button>
                                        </div>
                                    </form>
                                </td>
                                <td class="py-3 border-0 text-end fw-bold text-primary">{{ config('app.currency') }} {{ number_format($item['lineTotal'], 2) }}</td>
                                <td class="py-3 border-0 text-end">
                                    <a href="{{ route('store.cart.remove', $item['product']) }}" class="text-danger opacity-50 hover-100"><i class="fa fa-trash-alt"></i></a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow rounded-4 p-4 sticky-top" style="top: 100px;">
            <h5 class="fw-bold mb-4">Summary</h5>
            <div class="d-flex justify-content-between mb-3 text-muted">
                <span>Subtotal</span>
                <span>{{ config('app.currency') }} {{ number_format($subtotal, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between mb-3 text-muted">
                <span>Shipping</span>
                <span>Calculated at next step</span>
            </div>
            <hr class="border-light-subtle my-3">
            <div class="d-flex justify-content-between mb-5">
                <strong class="fs-4">Grand Total</strong>
                <strong class="fs-4 text-primary">{{ config('app.currency') }} {{ number_format($subtotal, 2) }}</strong>
            </div>
            <a href="{{ route('store.checkout') }}" class="btn btn-premium btn-lg w-100 py-3 rounded-pill {{ empty($products) ? 'disabled' : '' }}">Proceed to Checkout <i class="fa fa-arrow-right ms-2"></i></a>
            <div class="text-center mt-3">
                <a href="{{ route('store.index') }}" class="text-decoration-none text-muted tx-13"><i class="fa fa-chevron-left me-1"></i> Continue Shopping</a>
            </div>
        </div>
    </div>
</div>
@endsection
