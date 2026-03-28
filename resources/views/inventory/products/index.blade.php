@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Inventory</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Products</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <div class="pr-1 mb-3 mb-xl-0">
            <a href="{{ route('inventory.products.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Add Product</a>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between">
                    <h4 class="card-title mg-b-0">Product Catalog</h4>
                </div>
                <p class="tx-12 tx-gray-500 mb-2">Manage your inventory products for both POS and Ecommerce.</p>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-md-nowrap">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th>Price</th>
                                <th>Market</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr>
                                <td><code>{{ $product->sku }}</code></td>
                                <td>
                                    <div class="d-flex">
                                        @if($product->image)
                                            <img src="{{ asset('storage/'.$product->image) }}" class="avatar-sm rounded-circle mr-2" alt="">
                                        @endif
                                        <span class="my-auto">{{ $product->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $product->category?->name ?? 'Uncategorized' }}</td>
                                <td>
                                    <span class="badge badge-{{ $product->stock_quantity <= $product->min_stock_alert ? 'danger' : 'light' }}">
                                        {{ $product->stock_quantity }} {{ $product->unit }}
                                    </span>
                                </td>
                                <td>{{ config('app.currency') }} {{ number_format($product->selling_price, 2) }}</td>
                                <td>
                                    @if($product->is_ecommerce)
                                        <span class="badge badge-primary">Store</span>
                                    @endif
                                    <span class="badge badge-info">POS</span>
                                </td>
                                <td>
                                    <span class="dot-label bg-{{ $product->is_active ? 'success' : 'danger' }} mr-1"></span>
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </td>
                                <td>
                                    <a href="{{ route('inventory.products.show', $product) }}" class="btn btn-sm btn-info-light"><i class="fa fa-eye"></i></a>
                                    <a href="{{ route('inventory.products.edit', $product) }}" class="btn btn-sm btn-primary-light"><i class="fa fa-edit"></i></a>
                                    <form action="{{ route('inventory.products.destroy', $product) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger-light" onclick="return confirm('Delete item?')"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
