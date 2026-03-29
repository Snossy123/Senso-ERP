@extends('layouts.master')

@section('page-header')
<div class="breadcrumb-header justify-content-between">
    <div class="my-auto">
        <div class="d-flex">
            <h4 class="content-title mb-0 my-auto">Procurement</h4><span class="text-muted mt-1 tx-13 ml-2 mb-0">/ New Purchase Order</span>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="row" x-data="purchaseOrderForm()">
    <div class="col-xl-12">
        <form action="{{ route('inventory.purchase-orders.store') }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="card-title mg-b-0">Order Details</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select name="supplier_id" class="form-control" required>
                                    <option value="">Select Supplier...</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label">Receiving Warehouse <span class="text-danger">*</span></label>
                                <select name="warehouse_id" class="form-control" required>
                                    <option value="">Select Warehouse...</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label">Order Date <span class="text-danger">*</span></label>
                                <input type="date" name="order_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header pb-0 d-flex justify-content-between">
                    <h4 class="card-title mg-b-0">Order Items</h4>
                    <button type="button" class="btn btn-sm btn-info" @click="addItem()"><i class="fa fa-plus"></i> Add Line</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 40%">Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Cost</th>
                                    <th>Subtotal</th>
                                    <th style="width: 50px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in items" :key="index">
                                    <tr>
                                        <td>
                                            <select :name="`items[${index}][product_id]`" class="form-control" x-model="item.product_id" @change="updatePrice(index)" required>
                                                <option value="">Select product...</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" data-price="{{ $product->purchase_price }}">{{ $product->sku }} - {{ $product->name }}</option>
                                                @endforeach
                                            </select>
                                            
                                            <div class="mt-2" x-show="getVariants(item.product_id).length > 0">
                                                <label class="tx-11 text-muted">Variant:</label>
                                                <select :name="`items[${index}][product_variant_id]`" class="form-control form-control-sm" x-model="item.product_variant_id">
                                                    <option value="">Default (No variant)</option>
                                                    <template x-for="v in getVariants(item.product_id)" :key="v.id">
                                                        <option :value="v.id" x-text="v.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" :name="`items[${index}][quantity]`" class="form-control" x-model.number="item.quantity" min="1" required>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">{{ config('app.currency') }}</span>
                                                </div>
                                                <input type="number" step="0.01" :name="`items[${index}][unit_cost]`" class="form-control" x-model.number="item.unit_cost" required>
                                            </div>
                                        </td>
                                        <td class="text-right align-middle">
                                            <strong>{{ config('app.currency') }} <span x-text="(item.quantity * item.unit_cost).toFixed(2)"></span></strong>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger" @click="removeItem(index)" x-show="items.length > 1"><i class="fa fa-times"></i></button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-right">Total Amount</th>
                                    <th class="text-right text-primary tx-18">
                                        {{ config('app.currency') }} <span x-text="total.toFixed(2)"></span>
                                    </th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="form-footer mt-4">
                        <a href="{{ route('inventory.purchase-orders.index') }}" class="btn btn-secondary mr-2">Cancel</a>
                        <button type="submit" class="btn btn-primary px-5">Submit Purchase Order</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function purchaseOrderForm() {
    return {
        items: [
            { product_id: '', product_variant_id: '', quantity: 1, unit_cost: 0 }
        ],
        products: @json($products),
        
        getVariants(productId) {
            if (!productId) return [];
            const p = this.products.find(p => p.id == productId);
            return p ? (p.variants || []) : [];
        },
        
        addItem() {
            this.items.push({ product_id: '', product_variant_id: '', quantity: 1, unit_cost: 0 });
        },
        
        removeItem(index) {
            this.items.splice(index, 1);
        },
        
        updatePrice(index) {
           const productId = this.items[index].product_id;
           if (productId) {
               const p = this.products.find(p => p.id == productId);
               if (p) this.items[index].unit_cost = p.purchase_price;
           }
        },
        
        get total() {
            return this.items.reduce((sum, item) => sum + (item.quantity * item.unit_cost), 0);
        }
    }
}
</script>
@endpush
