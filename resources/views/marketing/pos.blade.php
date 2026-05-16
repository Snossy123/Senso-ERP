@extends('marketing.layout')
@section('title', 'Point of Sale')
@section('content')
<section class="mkt-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="mb-3">Point of Sale built for retail speed</h1>
                <p class="text-muted lead mb-4">Touch-first register, dynamic product grid, line & order discounts, flexible returns, shift reconciliation, and thermal receipts — with inventory and accounting synced on every sale.</p>
                <ul class="list-unstyled mb-4">
                    <li class="mb-2">✓ Dynamic catalog grid that fills the screen</li>
                    <li class="mb-2">✓ Line discounts in % or fixed amount</li>
                    <li class="mb-2">✓ Partial returns with per-line stock restore</li>
                    <li class="mb-2">✓ Shift sales & payment summary</li>
                    <li class="mb-2">✓ Light / dark register theme</li>
                    <li class="mb-2">✓ CRM customer search at checkout</li>
                </ul>
                <a href="{{ route('pos.app') }}" class="mkt-btn">Launch POS</a>
            </div>
            <div class="col-lg-6 mt-4 mt-lg-0">
                <div class="card mkt-card p-4 bg-white">
                    <p class="text-muted small mb-2">Register preview</p>
                    <div class="rounded-lg border p-3" style="min-height:220px;background:linear-gradient(180deg,#eef2f7,#fff)">
                        <div class="d-flex gap-2 mb-3"><span class="badge badge-primary">Catalog</span><span class="badge badge-light">Cart</span><span class="badge badge-light">Checkout</span></div>
                        <p class="mb-0 text-muted small">Optimized for tablets and desktop — category rail, search, held orders, and customer display.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
