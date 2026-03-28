@extends('store.layouts.portal')
@section('content')
<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('store.index') }}">Store</a></li>
            <li class="breadcrumb-item"><a href="{{ route('store.index', ['category' => $product->category_id]) }}">{{ $product->category?->name }}</a></li>
            <li class="breadcrumb-item active">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="bg-white p-3 rounded-4 shadow-sm">
                @if($product->image)
                    <img src="{{ asset('storage/'.$product->image) }}" class="img-fluid w-100 rounded-3" alt="{{ $product->name }}">
                @else
                    <div class="bg-light d-flex align-items-center justify-content-center rounded-3" style="height: 400px;">
                        <i class="fa fa-image fa-5x text-muted"></i>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-lg-6">
            <div class="ps-lg-4">
                <span class="badge bg-primary mb-2">{{ $product->category?->name }}</span>
                <h2 class="fw-bold mb-3">{{ $product->name }}</h2>
                
                <div class="d-flex align-items-center mb-4">
                    <h3 class="text-primary fw-bold mb-0 me-3">{{ config('app.currency') }} {{ number_format($product->selling_price, 2) }}</h3>
                    @if($product->stock_quantity > 0)
                        <span class="badge bg-success">In Stock ({{ $product->stock_quantity }} {{ $product->unit }})</span>
                    @else
                        <span class="badge bg-danger">Out of Stock</span>
                    @endif
                </div>

                @if($product->description)
                    <p class="text-muted mb-4">{{ $product->description }}</p>
                @endif

                <div class="mb-4">
                    <table class="table table-sm">
                        <tr>
                            <td class="text-muted border-0">SKU</td>
                            <td class="fw-bold border-0">{{ $product->sku }}</td>
                        </tr>
                        @if($product->weight)
                        <tr>
                            <td class="text-muted border-0">Weight</td>
                            <td class="border-0">{{ $product->weight }} {{ $product->unit }}</td>
                        </tr>
                        @endif
                        @if($product->barcode)
                        <tr>
                            <td class="text-muted border-0">Barcode</td>
                            <td class="border-0">{{ $product->barcode }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                @if($product->stock_quantity > 0)
                    <form action="{{ route('store.cart.add', $product) }}" method="POST" class="d-flex gap-3 mb-4">
                        @csrf
                        <div class="input-group" style="width: 140px;">
                            <span class="input-group-text">Qty</span>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" max="{{ $product->stock_quantity }}">
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                            <i class="fa fa-cart-plus me-2"></i> Add to Cart
                        </button>
                    </form>
                @else
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle me-2"></i> This product is currently out of stock.
                    </div>
                @endif

                <div class="d-flex gap-3 mt-4">
                    <a href="{{ route('store.cart.index') }}" class="btn btn-outline-primary">
                        <i class="fa fa-shopping-cart me-2"></i> View Cart
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($related->count() > 0)
    <div class="mt-5 pt-5">
        <h4 class="fw-bold mb-4">Related Products</h4>
        <div class="row g-4">
            @foreach($related as $item)
            <div class="col-md-3">
                <div class="product-card">
                    <div class="position-relative overflow-hidden">
                        @if($item->image)
                            <img src="{{ asset('storage/'.$item->image) }}" class="img-fluid w-100" style="height: 180px; object-fit: cover;" alt="">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height: 180px;">
                                <i class="fa fa-image fa-2x"></i>
                            </div>
                        @endif
                    </div>
                    <div class="card-body p-3">
                        <h6 class="mb-1"><a href="{{ route('store.products.show', $item->slug) }}" class="text-decoration-none text-dark">{{ $item->name }}</a></h6>
                        <span class="fw-bold text-primary">{{ config('app.currency') }} {{ number_format($item->selling_price, 2) }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
