@extends('layouts.master')
@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Inventory</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ Add Product</span>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('inventory.products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row row-sm">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label">SKU <span class="tx-danger">*</span></label>
                                <input class="form-control" name="sku" value="{{ old('sku') }}" placeholder="Unique Product SKU" type="text" required>
                                @error('sku') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="form-group">
                                <label class="form-label">Product Name <span class="tx-danger">*</span></label>
                                <input class="form-control" name="name" value="{{ old('name') }}" placeholder="Product Title" type="text" required>
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
                                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
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
                                        <option value="{{ $wh->id }}" {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
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
                                        <option value="{{ $sup->id }}" {{ old('supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row row-sm">
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Purchase Price <span class="tx-danger">*</span></label>
                                <input class="form-control" name="purchase_price" value="{{ old('purchase_price', 0) }}" type="number" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Selling Price <span class="tx-danger">*</span></label>
                                <input class="form-control" name="selling_price" value="{{ old('selling_price', 0) }}" type="number" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Min Stock Alert</label>
                                <input class="form-control" name="min_stock_alert" value="{{ old('min_stock_alert', 5) }}" type="number">
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Unit <span class="tx-danger">*</span></label>
                                <input class="form-control" name="unit" value="{{ old('unit', 'pcs') }}" placeholder="pcs, kg, etc" type="text" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                    </div>

                    <div class="row row-sm mb-4">
                        <div class="col-lg-6">
                            <div class="custom-controls-stacked">
                                <label class="custom-control custom-checkbox ml-0">
                                    <input type="checkbox" class="custom-control-input" name="is_ecommerce" value="1" {{ old('is_ecommerce') ? 'checked' : '' }}>
                                    <span class="custom-control-label">Visible on Ecommerce Storefront</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-lg-6 text-right">
                             <label class="form-label">Product Image</label>
                             <input type="file" name="image" class="form-control">
                        </div>
                    </div>

                    <div class="form-footer mt-2">
                        <button type="submit" class="btn btn-primary">Create Product</button>
                        <a href="{{ route('inventory.products.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
