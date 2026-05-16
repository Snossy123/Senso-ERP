@extends('marketing.layout')
@section('title', 'ERP Platform')
@section('content')
<section class="mkt-hero text-center">
    <div class="container">
        <h1 class="mb-3">Run your business on one ERP</h1>
        <p class="lead text-muted mb-4 mx-auto" style="max-width:640px">POS, inventory, accounting, CRM, and storefront — built for multi-tenant SaaS operators who need speed and clean architecture.</p>
        <a href="{{ route('login') }}" class="mkt-btn mr-2">Open dashboard</a>
        <a href="{{ route('marketing.pos') }}" class="btn btn-outline-secondary rounded-pill px-4">Explore POS</a>
    </div>
</section>
<section class="py-5">
    <div class="container">
        <div class="row">
            @foreach([
                ['POS', 'Fast register, shifts, returns, receipts', route('marketing.pos')],
                ['Inventory', 'Products, warehouses, stock movements', route('login')],
                ['CRM', 'Customers, tags, notes, sales history', route('login')],
                ['Accounting', 'Journal entries synced from sales', route('login')],
            ] as $mod)
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card mkt-card p-4">
                    <h5 class="font-weight-bold">{{ $mod[0] }}</h5>
                    <p class="text-muted small mb-3">{{ $mod[1] }}</p>
                    <a href="{{ $mod[2] }}" class="small font-weight-semibold">Learn more →</a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
