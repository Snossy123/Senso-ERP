@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Inventory</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ {{ $product->name }}</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <a href="{{ route('inventory.products.edit', $product) }}" class="btn btn-primary mr-2">Edit Product</a>
        <a href="{{ route('inventory.products.index') }}" class="btn btn-secondary">Back to List</a>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h4>Product Details</h4>
            </div>
            <div class="card-body">
                @if($product->image)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="img-fluid mb-4 rounded">
                @else
                <div class="text-center p-5 bg-light rounded mb-4">
                    <i class="fe fe-package tx-60 text-muted"></i>
                    <p class="text-muted mt-2">No image</p>
                </div>
                @endif

                <h5 class="mb-3">{{ $product->name }}</h5>
                
                <div class="mb-2">
                    <span class="text-muted">SKU:</span>
                    <strong class="ml-2">{{ $product->sku }}</strong>
                </div>
                @if($product->barcode)
                <div class="mb-2">
                    <span class="text-muted">Barcode:</span>
                    <strong class="ml-2">{{ $product->barcode }}</strong>
                </div>
                @endif
                <div class="mb-2">
                    <span class="text-muted">Status:</span>
                    @if($product->is_active)
                    <span class="badge badge-success ml-2">Active</span>
                    @else
                    <span class="badge badge-danger ml-2">Inactive</span>
                    @endif
                </div>
                <div class="mb-2">
                    <span class="text-muted">E-commerce:</span>
                    @if($product->is_ecommerce)
                    <span class="badge badge-info ml-2">Available</span>
                    @else
                    <span class="badge badge-secondary ml-2">Not Available</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4>Stock & Pricing</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded">
                            <h6 class="text-muted mb-1">Stock</h6>
                            <h3 class="mb-0 {{ $product->isLowStock() ? 'text-danger' : 'text-success' }}">
                                {{ $product->stock_quantity }}
                            </h3>
                            <small class="text-muted">{{ $product->unit }}</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded">
                            <h6 class="text-muted mb-1">Purchase Price</h6>
                            <h3 class="mb-0">{{ config('app.currency') }} {{ number_format($product->purchase_price, 2) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded">
                            <h6 class="text-muted mb-1">Selling Price</h6>
                            <h3 class="mb-0 text-primary">{{ config('app.currency') }} {{ number_format($product->selling_price, 2) }}</h3>
                        </div>
                    </div>
                </div>

                @if($product->isLowStock())
                <div class="alert alert-danger mt-3">
                    <i class="fe fe-alert-triangle"></i>
                    <strong>Low Stock Alert!</strong> Current stock ({{ $product->stock_quantity }}) is below minimum threshold ({{ $product->min_stock_alert }}).
                </div>
                @endif

                <hr>

                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">Category</p>
                        <strong>{{ $product->category?->name ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">Warehouse</p>
                        <strong>{{ $product->warehouse?->name ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">Supplier</p>
                        <strong>{{ $product->supplier?->name ?? 'N/A' }}</strong>
                    </div>
                </div>

                @if($product->description)
                <hr>
                <p class="mb-1 text-muted">Description</p>
                <p>{{ $product->description }}</p>
                @endif
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h4>Stock Movement History</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Reference</th>
                                <th>User</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $movement)
                            <tr>
                                <td>{{ $movement->created_at->format('M d, Y H:i') }}</td>
                                <td>
                                    @if($movement->type === 'in')
                                    <span class="badge badge-success">Stock In</span>
                                    @elseif($movement->type === 'out')
                                    <span class="badge badge-danger">Stock Out</span>
                                    @else
                                    <span class="badge badge-secondary">Adjustment</span>
                                    @endif
                                </td>
                                <td class="{{ $movement->type === 'in' ? 'text-success' : 'text-danger' }}">
                                    {{ $movement->type === 'in' ? '+' : '-' }}{{ $movement->quantity }}
                                </td>
                                <td>{{ $movement->reference ?? '-' }}</td>
                                <td>{{ $movement->user?->name ?? 'System' }}</td>
                                <td>{{ $movement->notes ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No stock movements recorded</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
