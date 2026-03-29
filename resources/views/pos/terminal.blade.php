@extends('layouts.master')
@section('title', 'POS Terminal')
@section('css')
<style>
    body { overflow: hidden; }
    .pos-wrap { display: flex; height: calc(100vh - 60px); overflow: hidden; }
    /* Left Panel */
    .pos-left { flex: 1; display: flex; flex-direction: column; overflow: hidden; background: #f4f5f8; }
    .pos-toolbar { background: #fff; padding: 10px 14px; border-bottom: 1px solid #e0e0e0; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .pos-products { flex: 1; overflow-y: auto; padding: 12px; }
    .pos-product-card { cursor: pointer; transition: all 0.18s; border: 2px solid transparent; border-radius: 8px; }
    .pos-product-card:hover { border-color: #4a90d9; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(74,144,217,.2); }
    .pos-product-card.out-stock { opacity: .45; cursor: not-allowed; }
    .stock-warn { position: absolute; top: 6px; right: 6px; font-size: 10px; }
    /* Right Panel */
    .pos-right { width: 380px; display: flex; flex-direction: column; background: #fff; border-left: 1px solid #e0e0e0; }
    .pos-cart-header { background: #1a237e; color: #fff; padding: 12px 16px; }
    .pos-cart-body { flex: 1; overflow-y: auto; padding: 10px; }
    .pos-cart-footer { border-top: 1px solid #eee; padding: 12px; background: #fafafa; }
    .cart-row { display: flex; align-items: center; gap: 8px; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
    .cart-row:last-child { border-bottom: none; }
    .qty-btn { width: 26px; height: 26px; border: 1px solid #ccc; background: #f5f5f5; border-radius: 4px; cursor: pointer; font-weight: bold; line-height: 24px; text-align: center; }
    .qty-btn:hover { background: #1a237e; color:#fff; border-color:#1a237e; }
    .pos-total-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: .9em; }
    .pos-grand-total { font-size: 1.4em; font-weight: 700; color: #1a237e; }
    /* Numpad */
    .numpad-btn { width: 100%; padding: 12px; font-size: 1.1em; font-weight: 600; border-radius: 6px; border: 1px solid #ddd; background: #fff; cursor: pointer; transition: background .15s; }
    .numpad-btn:hover { background: #e8eaf6; }
    .numpad-btn.clear { background: #fff3e0; color: #e65100; }
    .numpad-btn.charge { background: #1a237e; color: #fff; font-size: 1.2em; }
    .numpad-btn.charge:hover { background: #283593; }
    /* Tabs */
    .pos-category-tabs { display: flex; gap: 6px; overflow-x: auto; padding: 4px 0; }
    .pos-category-tabs::-webkit-scrollbar { height: 3px; }
    .cat-tab { white-space: nowrap; padding: 5px 14px; border-radius: 20px; border: 1px solid #ccc; cursor: pointer; font-size:.85em; background:#fff; transition: all .15s; }
    .cat-tab.active { background: #1a237e; color: #fff; border-color: #1a237e; }
</style>
@endsection

@section('content')
<div class="pos-wrap" x-data="posSystem()" x-init="init()">

    <!-- ═══════════════════════ LEFT: Products ═══════════════════════ -->
    <div class="pos-left">
        <!-- Toolbar -->
        <div class="pos-toolbar">
            <!-- Search/Barcode -->
            <div class="flex-grow-1" style="max-width:340px; position:relative;">
                <input type="text" x-model="searchQuery" @input.debounce.300ms="onSearch()"
                       @keydown.enter.prevent="barcodeSearch()"
                       class="form-control" placeholder="🔍 Search / Scan barcode (Enter)..." id="pos-search">
            </div>
            <!-- Customer Select + Quick Add -->
            <div class="d-flex align-items-center" style="gap:4px;">
                <select class="form-control" style="width:170px" x-model="customerId">
                    <option value="">— Walk-in Customer —</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                    <template x-for="c in newCustomers" :key="c.id">
                        <option :value="c.id" x-text="c.name" :selected="customerId == c.id"></option>
                    </template>
                </select>
                <button class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#quickCustomerModal" title="Add New Customer">
                    <i class="fe fe-user-plus"></i>
                </button>
            </div>
            <!-- Hold / Held Orders -->
            <button class="btn btn-outline-warning btn-sm" @click="holdCurrentOrder()" title="Hold Order">
                <i class="fe fe-pause-circle"></i> Hold
            </button>
            <button class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#heldOrdersModal" title="Held Orders">
                <i class="fe fe-list"></i>
                <span x-text="heldOrders.length > 0 ? heldOrders.length : ''" class="badge badge-danger ml-1"></span>
            </button>
            <!-- Shift -->
            @if($activeShift)
                <button class="btn btn-outline-danger btn-sm" data-toggle="modal" data-target="#closeShiftModal" title="Close Shift">
                    <i class="fe fe-log-out"></i> Shift
                </button>
            @else
                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#openShiftModal">
                    <i class="fe fe-log-in"></i> Open Shift
                </button>
            @endif

            <a href="{{ route('pos.sales.index') }}" class="btn btn-outline-info btn-sm" title="Sales History">
                <i class="fe fe-archive"></i>
            </a>
        </div>

        <!-- Category Tabs -->
        <div class="px-3 pt-2 pb-1 bg-white border-bottom">
            <div class="pos-category-tabs">
                <span class="cat-tab" :class="{'active': selectedCategory === 'all'}" @click="selectedCategory='all'">All</span>
                @foreach($categories as $cat)
                    <span class="cat-tab" :class="{'active': selectedCategory == {{ $cat->id }}}" @click="selectedCategory={{ $cat->id }}">{{ $cat->name }}</span>
                @endforeach
            </div>
        </div>

        <!-- Product Grid -->
        <div class="pos-products">
            <div class="row row-sm">
                <template x-for="product in filteredProducts" :key="product.id">
                    <div class="col-6 col-md-4 col-xl-3 mb-3">
                        <div class="card pos-product-card h-100 position-relative"
                             :class="{'out-stock': product.out_of_stock}"
                             @click="!product.out_of_stock && addToCart(product)">
                            <div class="card-body p-2 text-center">
                                <template x-if="product.low_stock && !product.out_of_stock">
                                    <span class="badge badge-warning stock-warn">Low</span>
                                </template>
                                <template x-if="product.out_of_stock">
                                    <span class="badge badge-danger stock-warn">Out</span>
                                </template>
                                <img :src="product.image" class="avatar-md mb-1" style="object-fit:cover;border-radius:6px;">
                                <div class="tx-12 font-weight-semibold mt-1 text-truncate" x-text="product.name"></div>
                                <div class="text-primary font-weight-bold tx-13" x-text="'{{ config('app.currency_symbol','$') }}' + product.price.toFixed(2)"></div>
                                <small class="text-muted" :class="{'text-danger': product.out_of_stock}" x-text="'Stock: ' + product.stock"></small>
                            </div>
                        </div>
                    </div>
                </template>
                <template x-if="filteredProducts.length === 0">
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="fe fe-package tx-40"></i>
                        <p class="mt-2">No products found.</p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════ RIGHT: Cart ═══════════════════════ -->
    <div class="pos-right">
        <div class="pos-cart-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold">🛒 Current Order</h6>
            <small x-text="cart.length + ' item(s)'"></small>
        </div>

        <div class="pos-cart-body">
            <template x-if="cart.length === 0">
                <div class="text-center py-5 text-muted">
                    <i class="fe fe-shopping-cart tx-40"></i>
                    <p class="mt-3">Cart is empty.<br>Click a product to add.</p>
                </div>
            </template>

            <template x-for="(item, idx) in cart" :key="idx">
                <div class="cart-row">
                    <div class="flex-grow-1">
                        <div class="font-weight-semibold tx-13" x-text="item.name"></div>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="qty-btn" @click="updateQty(idx, -1)">−</span>
                            <span class="px-2 font-weight-bold" x-text="item.qty"></span>
                            <span class="qty-btn" @click="updateQty(idx, 1)">+</span>
                            <span class="text-muted tx-12 ml-2">@ <span x-text="item.price.toFixed(2)"></span></span>
                        </div>
                        <!-- Item discount -->
                        <div class="mt-1 d-flex align-items-center gap-1">
                            <input type="number" x-model.number="item.discount_pct" min="0" max="100"
                                   class="form-control form-control-sm" style="width:60px" placeholder="Disc%">
                            <span class="text-muted tx-11">% off</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-weight-bold text-primary" x-text="'{{ config('app.currency_symbol','$') }}' + itemTotal(item).toFixed(2)"></div>
                        <button class="btn btn-link text-danger p-0 mt-1 tx-11" @click="removeItem(idx)">✕ Remove</button>
                    </div>
                </div>
            </template>
        </div>

        <div class="pos-cart-footer">
            <!-- Order Discount -->
            <div class="d-flex align-items-center gap-2 mb-2">
                <label class="mb-0 tx-12 text-muted">Order Discount:</label>
                <div class="input-group input-group-sm" style="max-width:140px">
                    <input type="number" x-model.number="orderDiscount" min="0" class="form-control" placeholder="0.00">
                    <div class="input-group-append"><span class="input-group-text">{{ config('app.currency_symbol','$') }}</span></div>
                </div>
            </div>

            <!-- Totals -->
            <div class="pos-total-row"><span>Subtotal</span><span x-text="'{{ config('app.currency_symbol','$') }}' + subtotal.toFixed(2)"></span></div>
            <div class="pos-total-row text-danger"><span>Discount</span><span x-text="'- {{ config('app.currency_symbol','$') }}' + totalDiscount.toFixed(2)"></span></div>
            <div class="pos-total-row"><span>Tax ({{ config('app.tax_rate', 0) }}%)</span><span x-text="'{{ config('app.currency_symbol','$') }}' + tax.toFixed(2)"></span></div>
            <div class="pos-total-row border-top pt-2 mt-1">
                <span class="pos-grand-total">TOTAL</span>
                <span class="pos-grand-total" x-text="'{{ config('app.currency_symbol','$') }}' + total.toFixed(2)"></span>
            </div>

            <!-- Payment Method -->
            <div class="row row-sm mt-2">
                <div class="col-12 mb-2">
                    <label class="tx-11 text-muted font-weight-bold">PAYMENT METHOD</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm flex-fill" :class="paymentMethod==='cash' ? 'btn-dark' : 'btn-outline-secondary'" @click="paymentMethod='cash'">💵 Cash</button>
                        <button class="btn btn-sm flex-fill" :class="paymentMethod==='card' ? 'btn-dark' : 'btn-outline-secondary'" @click="paymentMethod='card'">💳 Card</button>
                        <button class="btn btn-sm flex-fill" :class="paymentMethod==='bank_transfer' ? 'btn-dark' : 'btn-outline-secondary'" @click="paymentMethod='bank_transfer'">🏦 Bank</button>
                    </div>
                </div>
                <!-- Cash tendered (show only for cash) -->
                <div class="col-12 mb-2" x-show="paymentMethod === 'cash'">
                    <label class="tx-11 text-muted">CASH TENDERED</label>
                    <input type="number" x-model.number="amountTendered" class="form-control" :min="total" placeholder="0.00" step="0.01">
                    <div class="mt-1 font-weight-bold text-success tx-14" x-show="amountTendered >= total">
                        Change: <span x-text="'{{ config('app.currency_symbol','$') }}' + changeDue.toFixed(2)"></span>
                    </div>
                </div>
                <!-- Notes -->
                <div class="col-12 mb-2">
                    <input type="text" x-model="notes" class="form-control form-control-sm" placeholder="Notes (optional)">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-grid gap-1">
                <button class="btn btn-primary btn-block tx-14 font-weight-bold py-2"
                        @click="checkout()"
                        :disabled="cart.length === 0 || processing">
                    <template x-if="!processing"><span>⚡ CHARGE {{ config('app.currency_symbol','$') }}<span x-text="total.toFixed(2)"></span></span></template>
                    <template x-if="processing"><span><i class="fas fa-spinner fa-spin"></i> Processing…</span></template>
                </button>
                <button class="btn btn-outline-danger btn-block btn-sm" @click="clearCart()" :disabled="cart.length === 0">🗑 Clear Order</button>
            </div>
        </div>
    </div>

    <!-- ═══════════════ Held Orders Modal ═══════════════ -->
    <div class="modal fade" id="heldOrdersModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Held Orders</h5>
                    <button class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <template x-if="heldOrders.length === 0">
                        <p class="text-muted text-center py-3">No held orders.</p>
                    </template>
                    <template x-for="held in heldOrders" :key="held.id">
                        <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                            <div>
                                <strong x-text="held.label"></strong>
                                <div class="text-muted tx-12" x-text="held.cart_data.length + ' item(s) — $' + parseFloat(held.subtotal).toFixed(2)"></div>
                            </div>
                            <button class="btn btn-sm btn-success" @click="resumeOrder(held.id)">Resume</button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ Open Shift Modal ═══════════════ -->
    <div class="modal fade" id="openShiftModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Open Shift</h5><button class="close" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body">
                    <label>Opening Float (Cash in Drawer)</label>
                    <input type="number" x-model.number="shiftOpenFloat" class="form-control" min="0" step="0.01" placeholder="0.00">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success" @click="openShift()">Open Shift</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ Close Shift Modal ═══════════════ -->
    <div class="modal fade" id="closeShiftModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Close Shift</h5><button class="close" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body">
                    <label>Closing Cash Count</label>
                    <input type="number" x-model.number="shiftCloseFloat" class="form-control" min="0" step="0.01" placeholder="0.00">
                    <textarea x-model="shiftNotes" class="form-control mt-2" rows="2" placeholder="Notes (optional)"></textarea>
                    <div x-show="shiftSummary" class="alert alert-info mt-3">
                        <div>Total Sales: <strong x-text="'$' + (shiftSummary?.total_sales || 0).toFixed(2)"></strong></div>
                        <div>Expected Cash: <strong x-text="'$' + (shiftSummary?.expected_cash || 0).toFixed(2)"></strong></div>
                        <div>Variance: <strong :class="(shiftSummary?.variance || 0) < 0 ? 'text-danger' : 'text-success'" x-text="'$' + (shiftSummary?.variance || 0).toFixed(2)"></strong></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger" @click="closeShift()">Close Shift</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ Quick Add Customer Modal ═══════════════ -->
    <div class="modal fade" id="quickCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fe fe-user-plus mr-2"></i>Quick Add Customer</h5>
                    <button class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-2">
                        <label class="tx-12 font-weight-bold">Name <span class="text-danger">*</span></label>
                        <input type="text" x-model="newCustomer.name" class="form-control" placeholder="Full name">
                    </div>
                    <div class="form-group mb-2">
                        <label class="tx-12 font-weight-bold">Phone</label>
                        <input type="text" x-model="newCustomer.phone" class="form-control" placeholder="+1234...">
                    </div>
                    <div class="form-group mb-2">
                        <label class="tx-12 font-weight-bold">Email</label>
                        <input type="email" x-model="newCustomer.email" class="form-control" placeholder="customer@email.com">
                    </div>
                    <div x-show="newCustomer.error" class="alert alert-danger py-2 tx-12" x-text="newCustomer.error"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary btn-sm" @click="saveQuickCustomer()" :disabled="!newCustomer.name.trim()">
                        <i class="fe fe-check mr-1"></i> Save & Select
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ Variant Selection Modal ═══════════════ -->
    <div class="modal fade" id="variantModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Variant</h5>
                    <button class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        <template x-for="v in activeProductVariants" :key="v.id">
                            <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                    @click="addVariantToCart(v)">
                                <span x-text="v.name"></span>
                                <span class="font-weight-bold" x-text="'$' + v.price.toFixed(2)"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ Success Modal ═══════════════ -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-5">
                    <i class="fe fe-check-circle tx-80 text-success"></i>
                    <h4 class="mt-3">Sale Complete!</h4>
                    <div class="alert alert-light mt-3" x-show="changeDue > 0">
                        Change Due: <strong class="text-success tx-18" x-text="'{{ config('app.currency_symbol','$') }}' + changeDue.toFixed(2)"></strong>
                    </div>
                    <button class="btn btn-success mt-2 btn-block" data-dismiss="modal" @click="newOrder()">New Order</button>
                    <a :href="'/pos/sales/' + lastSaleId" class="btn btn-outline-primary btn-block btn-sm mt-1" x-show="lastSaleId">View Receipt</a>
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
        filteredProducts: [],
        cart: [],
        selectedCategory: 'all',
        searchQuery: '',
        customerId: '',
        paymentMethod: 'cash',
        amountTendered: 0,
        orderDiscount: 0,
        notes: '',
        processing: false,
        lastSaleId: null,
        heldOrders: @json($heldOrders->values()),
        // Shift
        shiftId: {{ $activeShift?->id ?? 'null' }},
        shiftOpenFloat: 0,
        shiftCloseFloat: 0,
        shiftNotes: '',
        shiftSummary: null,
        taxRate: {{ config('app.tax_rate', 0) }},

        // Quick Customer
        newCustomers: [],
        newCustomer: { name: '', phone: '', email: '', error: '' },

        // Variants
        activeProduct: null,
        activeProductVariants: [],

        init() {
            this.applyFilter();
            this.$watch('selectedCategory', () => this.applyFilter());
            this.$watch('searchQuery', () => this.applyFilter());
        },

        applyFilter() {
            let list = this.products;
            if (this.selectedCategory !== 'all') {
                list = list.filter(p => p.category_id == this.selectedCategory);
            }
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(p =>
                    p.name.toLowerCase().includes(q) ||
                    (p.sku && p.sku.toLowerCase().includes(q)) ||
                    (p.barcode && p.barcode.toLowerCase().includes(q))
                );
            }
            this.filteredProducts = list;
        },

        onSearch() { this.applyFilter(); },

        barcodeSearch() {
            const q = this.searchQuery.trim();
            if (!q) return;
            const exact = this.products.find(p =>
                p.barcode === q || p.sku === q
            );
            if (exact) {
                this.addToCart(exact);
                this.searchQuery = '';
                this.applyFilter();
            } else {
                this.applyFilter();
            }
        },


        addToCart(product) {
            if (product.out_of_stock) { alert('This product is out of stock.'); return; }
            
            if (product.has_variants && product.variants && product.variants.length > 0) {
                this.activeProduct = product;
                this.activeProductVariants = product.variants;
                $('#variantModal').modal('show');
                return;
            }

            const existing = this.cart.find(i => i.id === product.id && !i.variant_id);
            if (existing) {
                if (existing.qty < product.stock) {
                    existing.qty++;
                } else {
                    alert(`Only ${product.stock} units available.`);
                }
            } else {
                this.cart.push({ ...product, qty: 1, discount_pct: 0, variant_id: null });
            }
        },

        addVariantToCart(variant) {
            const product = this.activeProduct;
            if (!product) return;

            const existing = this.cart.find(i => i.id === product.id && i.variant_id === variant.id);
            if (existing) {
                if (existing.qty < product.stock) { // Simplified stock check for variant (should eventually be per-variant stock)
                    existing.qty++;
                } else {
                    alert(`Only ${product.stock} units available.`);
                }
            } else {
                this.cart.push({
                    id: product.id,
                    name: product.name + ' - ' + variant.name,
                    price: variant.price,
                    stock: product.stock,
                    qty: 1,
                    discount_pct: 0,
                    variant_id: variant.id
                });
            }
            $('#variantModal').modal('hide');
        },

        updateQty(idx, delta) {
            const item = this.cart[idx];
            if (!item) return;
            const newQty = item.qty + delta;
            if (newQty <= 0) {
                this.cart.splice(idx, 1);
            } else if (newQty > item.stock) {
                alert(`Only ${item.stock} units available.`);
            } else {
                item.qty = newQty;
            }
        },

        removeItem(idx) { this.cart.splice(idx, 1); },

        clearCart() {
            if (this.cart.length === 0) return;
            if (confirm('Clear current order?')) { this.cart = []; this.orderDiscount = 0; }
        },

        newOrder() { this.cart = []; this.orderDiscount = 0; this.notes = ''; this.amountTendered = 0; this.customerId = ''; },

        itemTotal(item) {
            const gross = item.price * item.qty;
            return gross - (gross * (item.discount_pct || 0) / 100);
        },

        get subtotal() {
            return this.cart.reduce((s, i) => s + this.itemTotal(i), 0);
        },
        get totalDiscount() {
            const itemDiscounts = this.cart.reduce((s, i) => s + (i.price * i.qty * (i.discount_pct || 0) / 100), 0);
            return itemDiscounts + (parseFloat(this.orderDiscount) || 0);
        },
        get tax() {
            return (this.subtotal - (parseFloat(this.orderDiscount) || 0)) * (this.taxRate / 100);
        },
        get total() {
            return Math.max(0, this.subtotal - (parseFloat(this.orderDiscount) || 0) + this.tax);
        },
        get changeDue() {
            return Math.max(0, (parseFloat(this.amountTendered) || 0) - this.total);
        },

        checkout() {
            if (this.cart.length === 0) return;
            if (this.paymentMethod === 'cash' && this.amountTendered < this.total) {
                alert('Amount tendered is less than the total.'); return;
            }
            this.processing = true;

            fetch('{{ route('pos.sale.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    items: this.cart.map(i => ({
                        id: i.id, qty: i.qty, price: i.price, 
                        discount_pct: i.discount_pct || 0,
                        variant_id: i.variant_id
                    })),
                    payment_method: this.paymentMethod,
                    discount: this.orderDiscount,
                    tax_rate: this.taxRate,
                    amount_tendered: this.amountTendered || this.total,
                    customer_id: this.customerId || null,
                    shift_id: this.shiftId,
                    notes: this.notes,
                })
            })
            .then(r => r.json())
            .then(data => {
                this.processing = false;
                if (data.success) {
                    this.lastSaleId = data.sale_id;
                    $('#successModal').modal('show');
                } else {
                    alert('Error: ' + (data.error || data.message || 'Unknown error'));
                }
            })
            .catch(() => { this.processing = false; alert('Network error. Please try again.'); });
        },

        // Quick Customer
        saveQuickCustomer() {
            if (!this.newCustomer.name.trim()) return;
            this.newCustomer.error = '';
            fetch('{{ route('pos.customer.quick-store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    name: this.newCustomer.name,
                    phone: this.newCustomer.phone,
                    email: this.newCustomer.email,
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.newCustomers.push(data.customer);
                    this.customerId = data.customer.id;
                    this.newCustomer = { name: '', phone: '', email: '', error: '' };
                    $('#quickCustomerModal').modal('hide');
                } else {
                    this.newCustomer.error = data.error || 'Failed to create customer.';
                }
            })
            .catch(() => { this.newCustomer.error = 'Network error. Please try again.'; });
        },

        // Hold Order
        holdCurrentOrder() {
            if (this.cart.length === 0) { alert('Cart is empty.'); return; }
            const label = prompt('Label this order (optional):') || '';
            fetch('{{ route('pos.hold') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ cart: this.cart, label })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.heldOrders.push(data.held);
                    this.newOrder();
                    alert('Order held successfully.');
                }
            });
        },

        resumeOrder(id) {
            fetch(`/pos/held/${id}/resume`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.cart = data.cart;
                    this.heldOrders = this.heldOrders.filter(h => h.id !== id);
                    $('#heldOrdersModal').modal('hide');
                }
            });
        },

        // Shift
        openShift() {
            fetch('{{ route('pos.shift.open') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ opening_float: this.shiftOpenFloat })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.shiftId = data.shift.id;
                    $('#openShiftModal').modal('hide');
                    alert('Shift opened successfully!');
                } else {
                    alert(data.error || 'Failed to open shift.');
                }
            });
        },

        closeShift() {
            if (!this.shiftId) return;
            if (!confirm('Are you sure you want to close this shift?')) return;
            fetch(`/pos/shift/${this.shiftId}/close`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ closing_float: this.shiftCloseFloat, notes: this.shiftNotes })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.shiftSummary = data;
                    alert('Shift closed successfully. Press OK to refresh.');
                    window.location.reload();
                } else {
                    alert(data.error || 'Failed to close shift.');
                }
            });
        },
    };
}
</script>
@endsection
