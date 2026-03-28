@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Inventory</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Edit Product</span>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('inventory.products.update', $product) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row row-sm">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label">SKU <span class="tx-danger">*</span></label>
                                <input class="form-control" name="sku" value="{{ old('sku', $product->sku) }}" type="text" required>
                                @error('sku') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="form-group">
                                <label class="form-label">Product Name <span class="tx-danger">*</span></label>
                                <input class="form-control" name="name" value="{{ old('name', $product->name) }}" type="text" required>
                                @error('name') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row row-sm">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-control">
                                    <option value="">Select Category</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label">Warehouse</label>
                                <select name="warehouse_id" class="form-control">
                                    <option value="">Select Warehouse</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ old('warehouse_id', $product->warehouse_id) == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-control">
                                    <option value="">Select Supplier</option>
                                    @foreach($suppliers as $sup)
                                        <option value="{{ $sup->id }}" {{ old('supplier_id', $product->supplier_id) == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row row-sm">
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Purchase Price <span class="tx-danger">*</span></label>
                                <input class="form-control" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" type="number" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Selling Price <span class="tx-danger">*</span></label>
                                <input class="form-control" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" type="number" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Stock Quantity</label>
                                <input class="form-control" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" type="number">
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Min Stock Alert</label>
                                <input class="form-control" name="min_stock_alert" value="{{ old('min_stock_alert', $product->min_stock_alert) }}" type="number">
                            </div>
                        </div>
                    </div>

                    <div class="row row-sm">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label">Unit <span class="tx-danger">*</span></label>
                                <input class="form-control" name="unit" value="{{ old('unit', $product->unit) }}" type="text" required>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label">Barcode</label>
                                <input class="form-control" name="barcode" value="{{ old('barcode', $product->barcode) }}" type="text">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label">Weight</label>
                                <input class="form-control" name="weight" value="{{ old('weight', $product->weight) }}" type="number" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
                    </div>

                    <div class="row row-sm mb-4">
                        <div class="col-lg-4">
                            <div class="custom-controls-stacked">
                                <label class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                                    <span class="custom-control-label">Active Product</span>
                                </label>
                            </div>
                            <div class="custom-controls-stacked mt-2">
                                <label class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="is_ecommerce" value="1" {{ old('is_ecommerce', $product->is_ecommerce) ? 'checked' : '' }}>
                                    <span class="custom-control-label">Visible on Ecommerce Storefront</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">Change Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            @if($product->image)
                            <small class="text-muted">Current: {{ $product->image }}</small>
                            @endif
                        </div>
                        <div class="col-lg-4">
                            @if($product->image)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="img-thumbnail" style="max-height: 100px;">
                            @endif
                        </div>
                    </div>

                    <div class="form-footer mt-2">
                        <button type="submit" class="btn btn-primary">Update Product</button>
                        <a href="{{ route('inventory.products.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
