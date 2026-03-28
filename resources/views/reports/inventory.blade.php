@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Reports</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Inventory Report</span>
        </div>
    </div>
    <div class="d-flex my-xl-auto right-content">
        <div class="btn-group mb-2 mr-2">
            <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-download"></i> Export
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item" href="{{ route('exports.inventory.pdf') }}"><i class="fa fa-file-pdf-o"></i> Export PDF</a>
                <a class="dropdown-item" href="{{ route('exports.inventory.excel') }}"><i class="fa fa-file-excel-o"></i> Export Excel</a>
            </div>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="row mb-4">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Out of Stock</h6>
                <h2 class="text-danger">{{ $outOfStock->count() }}</h2>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Low Stock Items</h6>
                <h2 class="text-warning">{{ $lowStock->count() }}</h2>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted">Categories</h6>
                <h2 class="text-info">{{ $categoryStats->count() }}</h2>
            </div>
        </div>
    </div>
</div>

@if($outOfStock->count() > 0)
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title mg-b-0 text-danger"><i class="fa fa-exclamation-triangle"></i> Out of Stock Products</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-md-nowrap">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Warehouse</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($outOfStock as $product)
                            <tr class="table-danger">
                                <td><code>{{ $product->sku }}</code></td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->category?->name }}</td>
                                <td>{{ $product->warehouse?->name }}</td>
                                <td class="text-danger font-weight-bold">0</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row mt-4">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title mg-b-0 text-warning"><i class="fa fa-exclamation-circle"></i> Low Stock Alert</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-md-nowrap">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Warehouse</th>
                                <th>Current Stock</th>
                                <th>Min Alert</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lowStock as $product)
                            <tr>
                                <td><code>{{ $product->sku }}</code></td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->category?->name }}</td>
                                <td>{{ $product->warehouse?->name }}</td>
                                <td class="{{ $product->stock_quantity == 0 ? 'text-danger' : 'text-warning' }} font-weight-bold">
                                    {{ $product->stock_quantity }}
                                </td>
                                <td>{{ $product->min_stock_alert }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">No low stock items</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $lowStock->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="card-title mg-b-0">Stock by Category</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-md-nowrap">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Products</th>
                                <th>Total Stock</th>
                                <th>Stock Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categoryStats as $stat)
                            <tr>
                                <td><strong>{{ $stat->category?->name ?? 'Uncategorized' }}</strong></td>
                                <td>{{ $stat->count }}</td>
                                <td>{{ number_format($stat->total_stock ?? 0) }}</td>
                                <td>{{ config('app.currency') }} {{ number_format($stat->count * ($stat->category?->products->first()?->purchase_price ?? 0), 2) }}</td>
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
