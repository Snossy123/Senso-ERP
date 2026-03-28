@extends('layouts.master')
@section('css')
<style>
    .pos-product-card { cursor: pointer; transition: all 0.2s; height: 100%; }
    .pos-product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .cart-item-row { border-bottom: 1px solid #eee; padding: 10px 0; }
    .pos-sidebar { height: calc(100vh - 120px); position: sticky; top: 80px; display: flex; flex-direction: column; }
    .pos-cart-list { flex-grow: 1; overflow-y: auto; }
</style>
@endsection
@section('content')
<div class="row" x-data="posSystem()">
    <!-- Left Column: Products -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between">
                    <h4 class="card-title mg-b-0">Products List</h4>
                    <div class="wd-200">
                        <select class="form-control" x-model="selectedCategory">
                            <option value="all">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row row-sm">
                    <template x-for="product in filteredProducts()" :key="product.id">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card pos-product-card border h-100" @click="addToCart(product)">
                                <div class="card-body p-2 text-center">
                                    <template x-if="product.image">
                                        <img :src="'/storage/' + product.image" class="avatar-lg mb-2" alt="">
                                    </template>
                                    <template x-if="!product.image">
                                        <div class="avatar-lg bg-light mb-2 mx-auto d-flex align-items-center justify-content-center">
                                            <i class="fe fe-package tx-24"></i>
                                        </div>
                                    </template>
                                    <h6 class="mb-1 tx-13" x-text="product.name"></h6>
                                    <p class="mb-0 font-weight-bold text-primary" x-text="'{{ config('app.currency') }}' + product.price.toFixed(2)"></p>
                                    <small class="text-muted" x-text="'Stock: ' + product.stock"></small>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Cart -->
    <div class="col-lg-4">
        <div class="card pos-sidebar">
            <div class="card-header bg-primary text-white pb-3">
                <h5 class="mb-0">Current Order</h5>
            </div>
            <div class="card-body p-3 d-flex flex-column h-100">
                <div class="pos-cart-list pr-2">
                    <template x-if="cart.length === 0">
                        <div class="text-center py-5">
                            <i class="fe fe-shopping-cart tx-40 text-muted"></i>
                            <p class="text-muted mt-2">Cart is empty</p>
                        </div>
                    </template>
                    <template x-for="(item, index) in cart" :key="index">
                        <div class="cart-item-row">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="font-weight-bold tx-13" x-text="item.name"></span>
                                <span class="text-primary font-weight-bold" x-text="'{{ config('app.currency') }}' + (item.price * item.qty).toFixed(2)"></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted" x-text="'@' + item.price.toFixed(2)"></small>
                                <div class="input-group input-group-sm wd-100">
                                    <div class="input-group-prepend"><button class="btn btn-light border-0" @click="updateQty(item.id, -1)">-</button></div>
                                    <input type="text" class="form-control text-center bg-white" :value="item.qty" readonly>
                                    <div class="input-group-append"><button class="btn btn-light border-0" @click="updateQty(item.id, 1)">+</button></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-auto border-top pt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span x-text="'{{ config('app.currency') }}' + subtotal.toFixed(2)"></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax ({{ config('app.tax_rate', 0) }}%)</span>
                        <span x-text="'{{ config('app.currency') }}' + tax.toFixed(2)"></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 border-top pt-2">
                        <strong class="tx-18">TOTAL</strong>
                        <strong class="tx-18 text-primary" x-text="'{{ config('app.currency') }}' + total.toFixed(2)"></strong>
                    </div>

                    <div class="mb-3">
                        <label class="tx-11 text-uppercase text-muted font-weight-bold">Payment Method</label>
                        <select class="form-control" x-model="paymentMethod">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <button class="btn btn-primary btn-block btn-lg" 
                            @click="checkout()" 
                            :disabled="cart.length === 0 || processing">
                        <span x-show="!processing">COMPLETE SALE</span>
                        <span x-show="processing"><i class="fa fa-spinner fa-spin"></i> Processing...</span>
                    </button>
                    <button class="btn btn-outline-danger btn-block btn-sm mt-2" @click="clearCart()">Cancel Order</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body text-center py-5">
                    <i class="fe fe-check-circle tx-80 text-success"></i>
                    <h3 class="mt-3">Sale Completed!</h3>
                    <p class="text-muted">Transaction recorded successfully.</p>
                    <button type="button" class="btn btn-success mt-3" data-dismiss="modal" @click="window.location.reload()">Done</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    function posSystem() {
        return {
            products: @json($products),
            cart: [],
            selectedCategory: 'all',
            paymentMethod: 'cash',
            processing: false,
            
            get subtotal() {
                return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
            },
            get tax() {
                return this.subtotal * ({{ config('app.tax_rate', 0) }} / 100);
            },
            get total() {
                return this.subtotal + this.tax;
            },

            filteredProducts() {
                if (this.selectedCategory === 'all') return this.products;
                return this.products.filter(p => p.category_id == this.selectedCategory);
            },

            addToCart(product) {
                const existing = this.cart.find(item => item.id === product.id);
                if (existing) {
                    if (existing.qty < product.stock) {
                        existing.qty++;
                    } else {
                        alert('Insufficient stock!');
                    }
                } else {
                    this.cart.push({ ...product, qty: 1 });
                }
            },

            updateQty(id, delta) {
                const item = this.cart.find(i => i.id === id);
                if (item) {
                    const newQty = item.qty + delta;
                    if (newQty > 0) {
                        const product = this.products.find(p => p.id === id);
                        if (newQty <= product.stock) {
                            item.qty = newQty;
                        } else {
                            alert('Insufficient stock!');
                        }
                    } else {
                        this.cart = this.cart.filter(i => i.id !== id);
                    }
                }
            },

            clearCart() {
                if(confirm('Clear entire order?')) {
                    this.cart = [];
                }
            },

            checkout() {
                this.processing = true;
                const payload = {
                    items: this.cart.map(item => ({ id: item.id, qty: item.qty, price: item.price })),
                    payment_method: this.paymentMethod,
                    tax_rate: {{ config('app.tax_rate', 0) }},
                    _token: '{{ csrf_token() }}'
                };

                fetch('{{ route('pos.sale.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    this.processing = false;
                    if (data.success) {
                        $('#successModal').modal('show');
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => {
                    this.processing = false;
                    console.error(err);
                    alert('An error occurred during checkout.');
                });
            }
        };
    }
</script>
@endsection
