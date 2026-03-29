@extends('layouts.master')

@section('title', 'New Stock Movement')

@section('content')
<div class="row">
    <div class="col-8 offset-2">
        <div class="card">
            <div class="card-header border-bottom-0">
                <h3 class="card-title">Record New Stock Movement</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('inventory.movements.store') }}" method="POST">
                    @csrf

                    <div class="form-group mb-3">
                        <label class="form-label">Product</label>
                        <select name="product_id" class="form-control @error('product_id') is-invalid @enderror" required>
                            <option value="">Select Product...</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} (In Stock: {{ $product->stock_quantity }})</option>
                            @endforeach
                        </select>
                        @error('product_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Warehouse (Optional)</label>
                        <select name="warehouse_id" class="form-control @error('warehouse_id') is-invalid @enderror">
                            <option value="">Select Warehouse...</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                        @error('warehouse_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-control @error('type') is-invalid @enderror" required>
                            <option value="in">STOCK IN (+ Qty)</option>
                            <option value="out">STOCK OUT (- Qty)</option>
                            <option value="adjustment">ADJUSTMENT (= Qty)</option>
                        </select>
                        @error('type') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror" required min="1">
                        @error('quantity') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Reference (Optional)</label>
                        <input type="text" name="reference" class="form-control @error('reference') is-invalid @enderror">
                        @error('reference') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3"></textarea>
                        @error('notes') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-footer mt-4 text-right">
                        <a href="{{ route('inventory.movements.index') }}" class="btn btn-secondary mr-2">Cancel</a>
                        <button type="submit" class="btn btn-primary px-5">Record Movement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
