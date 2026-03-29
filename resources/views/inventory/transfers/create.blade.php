@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Logistics</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ New Stock Transfer</span>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="row" x-data="transferForm()">
    <div class="col-xl-12">
        <form action="{{ route('inventory.transfers.store') }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-header pb-0 border-bottom-0">
                    <h4 class="card-title">Movement Details</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label">From Warehouse (Source) <span class="text-danger">*</span></label>
                                <select name="from_warehouse_id" class="form-control" x-model="fromWarehouse" required>
                                    <option value="">Select origin...</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label">To Warehouse (Destination) <span class="text-danger">*</span></label>
                                <select name="to_warehouse_id" class="form-control" required>
                                    <option value="">Select destination...</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}" x-show="fromWarehouse != '{{ $warehouse->id }}'">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label">Transfer Date <span class="text-danger">*</span></label>
                                <input type="date" name="transfer_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h4 class="card-title mg-b-0">Transfer Items</h4>
                    <button type="button" class="btn btn-sm btn-info" @click="addItem()"><i class="fa fa-plus"></i> Add Item</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 50%">Product / Variant</th>
                                    <th>Available (Source)</th>
                                    <th>Qty to Transfer</th>
                                    <th style="width: 50px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in items" :key="index">
                                    <tr>
                                        <td>
                                            <select :name="`items[${index}][product_id]`" class="form-control" x-model="item.product_id" required>
                                                <option value="">Select product...</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}">{{ $product->sku }} - {{ $product->name }}</option>
                                                @endforeach
                                            </select>
                                            
                                            <div class="mt-2" x-show="getVariants(item.product_id).length > 0">
                                                <select :name="`items[${index}][product_variant_id]`" class="form-control form-control-sm" x-model="item.product_variant_id">
                                                    <option value="">Default Variant</option>
                                                    <template x-for="v in getVariants(item.product_id)" :key="v.id">
                                                        <option :value="v.id" x-text="v.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-muted">Requires verification</span>
                                        </td>
                                        <td>
                                            <input type="number" :name="`items[${index}][quantity]`" class="form-control" x-model.number="item.quantity" min="1" required>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger" @click="removeItem(index)" x-show="items.length > 1"><i class="fa fa-times"></i></button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="form-footer mt-4">
                        <a href="{{ route('inventory.transfers.index') }}" class="btn btn-secondary mr-2">Cancel</a>
                        <button type="submit" class="btn btn-primary px-5">Complete Transfer</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function transferForm() {
    return {
        fromWarehouse: '',
        items: [
            { product_id: '', product_variant_id: '', quantity: 1 }
        ],
        products: @json($products),
        
        getVariants(productId) {
            if (!productId) return [];
            const p = this.products.find(p => p.id == productId);
            return p ? (p.variants || []) : [];
        },
        
        addItem() {
            this.items.push({ product_id: '', product_variant_id: '', quantity: 1 });
        },
        
        removeItem(index) {
            this.items.splice(index, 1);
        }
    }
}
</script>
@endpush
