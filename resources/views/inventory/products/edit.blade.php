@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between product-form__header">
    <div class="my-auto">
        <div class="d-flex flex-wrap align-items-center">
            <h4 class="content-title mb-0 my-auto">{{ __('messages.sidebar.products') }}</h4>
            <span class="text-muted mt-1 tx-13 mx-2 mb-0">/ Edit Product</span>
        </div>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2 my-xl-auto right-content">
        <a href="{{ route('inventory.products.index') }}" class="btn btn-light btn-sm px-3">Cancel</a>
        <button type="submit" form="product-edit-form" class="btn btn-primary btn-sm px-4">
            <i class="fe fe-save me-1"></i> Save product
        </button>
    </div>
</div>
@endsection

@section('css')
<link href="{{ asset('css/inventory/product-form.css') }}?v=2" rel="stylesheet">
@endsection

@section('content')
<div class="product-form">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card product-form__card">
        <div class="card-body">
            <form id="product-edit-form" action="{{ route('inventory.products.update', $product) }}" method="POST" enctype="multipart/form-data" x-data="{ hasVariants: {{ $product->has_variants ? 'true' : 'false' }} }">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-lg-4 product-form__aside">
                        @include('inventory.products.partials.image-field', ['product' => $product])
                        <div class="product-form__options">
                            <label class="custom-control custom-checkbox d-block">
                                <input type="checkbox" class="custom-control-input" name="is_ecommerce" value="1" {{ old('is_ecommerce', $product->is_ecommerce) ? 'checked' : '' }}>
                                <span class="custom-control-label">Visible on Ecommerce</span>
                            </label>
                            <label class="custom-control custom-checkbox d-block mb-0">
                                <input type="checkbox" class="custom-control-input" name="has_variants" value="1" x-model="hasVariants">
                                <span class="custom-control-label">This product has variants</span>
                            </label>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="row row-sm">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">SKU <span class="text-danger">*</span></label>
                                    <input class="form-control @error('sku') is-invalid @enderror" name="sku" value="{{ old('sku', $product->sku) }}" required>
                                    @error('sku')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $product->name) }}" required>
                                    @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row row-sm">
                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" class="form-control">
                                        <option value="">Select Category</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label class="form-label">Unit <span class="text-danger">*</span></label>
                                    <select name="unit_id" class="form-control" required>
                                        <option value="">Select unit</option>
                                        @foreach($units as $u)
                                            <option value="{{ $u->id }}" @selected(old('unit_id', $product->unit_id) == $u->id)>{{ $u->name }} ({{ $u->short_name }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label class="form-label">Valuation <span class="text-danger">*</span></label>
                                    <select name="valuation_method" class="form-control" required>
                                        <option value="fifo" @selected(old('valuation_method', $product->valuation_method) == 'fifo')>FIFO</option>
                                        <option value="average" @selected(old('valuation_method', $product->valuation_method) == 'average')>AVCO</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label class="form-label">Warehouse</label>
                                    <select name="warehouse_id" class="form-control">
                                        <option value="">Select warehouse</option>
                                        @foreach($warehouses as $wh)
                                            <option value="{{ $wh->id }}" @selected(old('warehouse_id', $product->warehouse_id) == $wh->id)>{{ $wh->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row row-sm">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                                    <input class="form-control" name="purchase_price" type="number" step="0.01" value="{{ old('purchase_price', $product->purchase_price) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                                    <input class="form-control" name="selling_price" type="number" step="0.01" value="{{ old('selling_price', $product->selling_price) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-lg-0">
                                    <label class="form-label">Min Stock Alert</label>
                                    <input class="form-control" name="min_stock_alert" type="number" value="{{ old('min_stock_alert', $product->min_stock_alert) }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="4">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card bg-light border-0 mt-4" x-show="hasVariants" x-cloak>
                    @php
                        $existingVariants = $product->variants->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'sku' => $v->sku])->toArray();
                    @endphp
                    <div class="card-body" x-data="{ variants: {{ json_encode($existingVariants ?: [['name' => '', 'sku' => '']]) }} }">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 font-weight-bold">Product variants</h6>
                            <button type="button" class="btn btn-sm btn-info" @click="variants.push({ name: '', sku: '' })">
                                <i class="fe fe-plus"></i> Add
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="text-muted tx-11">
                                    <tr><th>Name</th><th>SKU</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <template x-for="(v, index) in variants" :key="index">
                                        <tr>
                                            <input type="hidden" :name="`variants[${index}][id]`" x-model="v.id">
                                            <td><input type="text" :name="`variants[${index}][name]`" class="form-control form-control-sm" x-model="v.name" :required="hasVariants"></td>
                                            <td><input type="text" :name="`variants[${index}][sku]`" class="form-control form-control-sm" x-model="v.sku" :required="hasVariants"></td>
                                            <td><button type="button" class="btn btn-sm btn-link text-danger p-0" @click="variants.splice(index, 1)" x-show="variants.length > 1"><i class="fe fe-x"></i></button></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="product-form__footer card-footer bg-white border-top">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fe fe-save me-1"></i> Update Product
                    </button>
                    <a href="{{ route('inventory.products.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
