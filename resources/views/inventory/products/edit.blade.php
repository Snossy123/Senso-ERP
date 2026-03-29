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
                <form action="{{ route('inventory.products.update', $product) }}" method="POST" enctype="multipart/form-data" x-data="{ hasVariants: {{ $product->has_variants ? 'true' : 'false' }} }">
                    @csrf
                    @method('PUT')
                    <div class="row row-sm">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label">SKU <span class="tx-danger">*</span></label>
                                <input class="form-control @error('sku') is-invalid @enderror" name="sku" value="{{ old('sku', $product->sku) }}" type="text" required>
                                @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="form-group">
                                <label class="form-label">Product Name <span class="tx-danger">*</span></label>
                                <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $product->name) }}" type="text" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row row-sm">
                        <div class="col-lg-3">
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
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Unit of Measure <span class="tx-danger">*</span></label>
                                <select name="unit_id" class="form-control" required>
                                    <option value="">Select Unit...</option>
                                    @foreach($units as $u)
                                        <option value="{{ $u->id }}" {{ old('unit_id', $product->unit_id) == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->short_name }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Valuation <span class="tx-danger">*</span></label>
                                <select name="valuation_method" class="form-control" required>
                                    <option value="fifo" {{ old('valuation_method', $product->valuation_method) == 'fifo' ? 'selected' : '' }}>FIFO</option>
                                    <option value="average" {{ old('valuation_method', $product->valuation_method) == 'average' ? 'selected' : '' }}>AVCO (Average Costing)</option>
                                </select>
                            </div>
                        </div>
                         <div class="col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Warehouse (Default)</label>
                                <select name="warehouse_id" class="form-control">
                                    <option value="">Select Warehouse</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ old('warehouse_id', $product->warehouse_id) == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row row-sm">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label">Purchase Price <span class="tx-danger">*</span></label>
                                <input class="form-control" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" type="number" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label">Selling Price <span class="tx-danger">*</span></label>
                                <input class="form-control" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" type="number" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="form-label">Min Stock Alert</label>
                                <input class="form-control" name="min_stock_alert" value="{{ old('min_stock_alert', $product->min_stock_alert) }}" type="number">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
                    </div>

                    <div class="row row-sm mb-4">
                        <div class="col-lg-4">
                            <div class="custom-controls-stacked mt-4">
                                <label class="custom-control custom-checkbox ml-0">
                                    <input type="checkbox" class="custom-control-input" name="has_variants" value="1" x-model="hasVariants">
                                    <span class="custom-control-label">This product has variants</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="custom-controls-stacked mt-4">
                                <label class="custom-control custom_checkbox">
                                    <input type="checkbox" class="custom-control-input" name="is_ecommerce" value="1" {{ old('is_ecommerce', $product->is_ecommerce) ? 'checked' : '' }}>
                                    <span class="custom-control-label">Visible on Ecommerce</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-lg-4">
                             <label class="form-label">Change Image</label>
                             <input type="file" name="image" class="form-control mt-1">
                        </div>
                    </div>

                    <!-- Variants Section -->
                    <div class="card bg-gray-100 border-0 mb-4" x-show="hasVariants" x-transition>
                        @php
                            $existingVariants = $product->variants->map(fn($v) => ['id' => $v->id, 'name' => $v->name, 'sku' => $v->sku])->toArray();
                        @endphp
                        <div class="card-body" x-data="{ variants: {{ json_encode($existingVariants ?: [['name' => '', 'sku' => '']]) }} }">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="tx-14 font-weight-bold mb-0">Product Variants</h5>
                                <button type="button" class="btn btn-sm btn-info" @click="variants.push({ name: '', sku: '' })">
                                    <i class="fa fa-plus mr-1"></i> Add Variant
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Variant Name</th>
                                            <th>Variant SKU</th>
                                            <th style="width: 50px"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(v, index) in variants" :key="index">
                                            <tr>
                                                <input type="hidden" :name="`variants[${index}][id]`" x-model="v.id">
                                                <td><input type="text" :name="`variants[${index}][name]`" class="form-control form-control-sm" x-model="v.name" placeholder="Name" :required="hasVariants"></td>
                                                <td><input type="text" :name="`variants[${index}][sku]`" class="form-control form-control-sm" x-model="v.sku" placeholder="SKU" :required="hasVariants"></td>
                                                <td><button type="button" class="btn btn-sm btn-link text-danger" @click="variants.splice(index, 1)" x-show="variants.length > 1"><i class="fa fa-times"></i></button></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
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
