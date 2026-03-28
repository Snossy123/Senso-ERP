@extends('store.layouts.portal')
@section('hero')
<section class="hero-section text-center text-md-start">
    <div class="container py-lg-5">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-3 fw-bold mb-4">Discover Your <span class="text-white">Dream Life</span></h1>
                <p class="lead mb-5 op-8 text-white">Experience premium quality products delivered directly from our warehouse to your doorstep. Integrated with our world-class ERP.</p>
                <div class="d-flex gap-3 justify-content-center justify-content-md-start mt-4">
                    <a href="#products" class="btn btn-premium btn-lg">Shop Now</a>
                    <a href="#" class="btn btn-outline-light btn-lg px-4 border-2 rounded-pill">Learn More</a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <img src="/assets/img/media/login.png" class="img-fluid" alt="Hero">
            </div>
        </div>
    </div>
</section>
@endsection
@section('content')
<div class="row mb-5 py-4" id="products">
    <div class="col-md-3">
        <div class="sticky-top pt-4" style="top: 100px;">
            <h5 class="fw-bold mb-4"><i class="fa fa-filter me-2 text-primary"></i> Categories</h5>
            <div class="list-group list-group-flush border-0 shadow-sm rounded-4 overflow-hidden">
                <a href="{{ route('store.index') }}" class="list-group-item list-group-item-action {{ !request('category') ? 'active bg-primary' : '' }}">All Collection</a>
                @foreach($categories as $cat)
                    <a href="{{ route('store.index', ['category' => $cat->id]) }}" class="list-group-item list-group-item-action {{ request('category') == $cat->id ? 'active bg-primary' : '' }}">{{ $cat->name }}</a>
                @endforeach
            </div>

            <h5 class="fw-bold mt-5 mb-4">Search</h5>
            <div class="bg-white p-3 rounded-4 shadow-sm">
                <form action="{{ route('store.index') }}" method="GET">
                    <input type="text" name="search" class="form-control border-0 bg-light rounded-pill px-3" placeholder="Look for..." value="{{ request('search') }}">
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="row g-4 pt-4">
            @forelse($products as $product)
            <div class="col-md-4 col-sm-6">
                <div class="product-card">
                    <div class="position-relative overflow-hidden">
                        @if($product->image)
                            <img src="{{ asset('storage/'.$product->image) }}" class="img-fluid w-100" style="height: 250px; object-fit: cover;" alt="">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height: 250px;">
                                <i class="fa fa-image fa-3x"></i>
                            </div>
                        @endif
                        <span class="badge badge-sale rounded-pill px-3 py-2">NEW</span>
                    </div>
                    <div class="card-body p-4">
                        <small class="text-primary text-uppercase fw-600 ls-1">{{ $product->category?->name }}</small>
                        <h5 class="fw-bold my-2"><a href="{{ route('store.products.show', $product->slug) }}" class="text-decoration-none text-dark">{{ $product->name }}</a></h5>
                        <p class="text-muted tx-14 line-clamp-2">{{ Str::limit($product->description, 60) }}</p>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <span class="fs-4 fw-bold text-primary">{{ config('app.currency') }} {{ number_format($product->selling_price, 2) }}</span>
                            <form action="{{ route('store.cart.add', $product) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-premium btn-sm rounded-circle p-2 px-3"><i class="fa fa-plus"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <i class="fa fa-search fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No products found</h4>
            </div>
            @endforelse
        </div>
        <div class="mt-5 d-flex justify-content-center">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection
