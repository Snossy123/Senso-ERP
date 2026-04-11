@extends('layouts.master')
@section('title', 'POS Terminal')
@section('css')
<style>
    body { overflow: hidden; background: #f0f2f5; }
    .pos-wrap { display: flex; height: calc(100vh - 60px); overflow: hidden; }
    
    /* 1. Sidebar - Left */
    .pos-sidebar { width: 260px; background: #fff; border-right: 1px solid #e0e0e0; display: flex; flex-direction: column; overflow-y: auto; padding-bottom: 20px;}
    .pos-sidebar-header { padding: 15px; border-bottom: 1px solid #eee; background: #1a237e; color: white;}
    .pos-sidebar-section { padding: 15px; border-bottom: 1px solid #eee; }
    .pos-sidebar-section-title { font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px; color: #888; font-weight: 700; margin-bottom: 10px; }
    
    /* 2. Catalog - Middle */
    .pos-catalog { flex: 1; display: flex; flex-direction: column; overflow: hidden; background: #f4f5f8; }
    .pos-toolbar { background: #fff; padding: 12px 20px; border-bottom: 1px solid #e0e0e0; display: flex; gap: 12px; align-items: center; }
    .pos-products { flex: 1; overflow-y: auto; padding: 20px; }
    
    /* Category Tabs */
    .pos-category-tabs { display: flex; gap: 8px; overflow-x: auto; padding: 10px 20px; background: #fff; border-bottom: 1px solid #eee; }
    .pos-category-tabs::-webkit-scrollbar { height: 0px; } /* Hide scrollbar for aesthetics */
    .cat-tab { white-space: nowrap; padding: 8px 18px; border-radius: 30px; border: 1px solid #ddd; cursor: pointer; font-size: 0.9em; font-weight: 500; background: #fff; transition: all 0.2s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
    .cat-tab:hover { border-color: #1a237e; color: #1a237e; }
    .cat-tab.active { background: #1a237e; color: #fff; border-color: #1a237e; box-shadow: 0 4px 6px rgba(26, 35, 126, 0.2); }
    
    /* Product Cards */
    .pos-product-card { cursor: pointer; transition: all 0.2s cubic-bezier(0.165, 0.84, 0.44, 1); border: 1px solid transparent; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); background: #fff; }
    .pos-product-card:hover { transform: translateY(-4px); box-shadow: 0 8px 16px rgba(0,0,0,0.08); border-color:#e0e0e0; }
    .pos-product-card.out-stock { opacity: 0.5; filter: grayscale(100%); cursor: not-allowed; }
    .stock-warn { position: absolute; top: 8px; right: 8px; font-size: 10px; z-index: 10; font-weight: bold; padding: 3px 6px; border-radius: 4px;}
    .product-img-wrapper { height: 120px; width: 100%; overflow: hidden; position: relative; background: #f8f9fa; }
    .product-img-wrapper img { width: 100%; height: 100%; object-fit: cover; }
    
    /* 3. Cart - Right */
    .pos-cart { width: 380px; display: flex; flex-direction: column; background: #fff; border-left: 1px solid #e0e0e0; box-shadow: -4px 0 15px rgba(0,0,0,0.03); z-index: 10;}
    .pos-cart-header { padding: 16px 20px; border-bottom: 1px solid #e0e0e0; background: #fafbfc; }
    .pos-cart-body { flex: 1; overflow-y: auto; padding: 0; }
    .pos-cart-footer { border-top: 1px solid #e0e0e0; padding: 16px 20px; background: #fff; }
    
    /* Cart Items */
    .cart-row { display: flex; align-items: flex-start; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #f0f0f0; transition: background 0.1s;}
    .cart-row:hover { background: #f8f9fa; }
    .qty-control { display: flex; align-items: center; border: 1px solid #e0e0e0; border-radius: 6px; overflow: hidden; background: #fff; margin-top: 8px; }
    .qty-btn { width: 32px; height: 30px; background: #f8f9fa; border: none; cursor: pointer; font-weight: bold; color: #444; transition: all 0.1s; display:flex; align-items:center; justify-content:center;}
    .qty-btn:hover { background: #e9ecef; color: #000; }
    .qty-input { width: 40px; border: none; text-align: center; font-weight: 600; font-size: 0.95em; border-left: 1px solid #e0e0e0; border-right: 1px solid #e0e0e0; height: 30px;}
    
    /* Totals */
    .pos-total-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.95em; color: #555; }
    .pos-grand-total { font-size: 1.6em; font-weight: 800; color: #1a237e; letter-spacing: -0.5px;}
    
    /* Main Actions */
    .btn-pay { height: 60px; font-size: 1.3em; letter-spacing: 1px; text-transform: uppercase; border-radius: 8px; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3); }
    
    /* Checkout Modal */
    .checkout-modal .modal-content { border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
    .payment-method-btn { padding: 15px; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer; text-align: center; transition: all 0.2s; font-weight: 600; color: #555;}
    .payment-method-btn.active { border-color: #1a237e; background: #f0f2fb; color: #1a237e; }
    .quick-cash-btn { background: #f8f9fa; border: 1px solid #ddd; border-radius: 6px; padding: 10px; font-weight: bold; color: #333; transition: all 0.1s;}
    .quick-cash-btn:hover { background: #e9ecef; border-color: #ccc; }
    [x-cloak] { display: none !important; }
</style>
@endsection

@section('content')
<script>
    // Inject configuration once into global scope to avoid attribute parsing errors
    window.posTerminalConfig = {
        products: @json($products),
        customers: @json($customers),
        heldOrders: @json($heldOrders->values()),
        shiftId: {{ $activeShift?->id ?? 'null' }},
        taxRate: {{ config('app.tax_rate', 0) }},
        currencySymbol: '{{ config('app.currency_symbol', '$') }}',
        csrfToken: '{{ csrf_token() }}',
        routes: {
            storeSale: '{{ route('pos.sale.store') }}',
            quickCustomer: '{{ route('pos.customer.quick-store') }}',
            holdOrder: '{{ route('pos.hold') }}',
            resumeOrder: '/pos/held/:id/resume',
            openShift: '{{ route('pos.shift.open') }}',
            closeShift: '/pos/shift/:id/close'
        }
    };
</script>

<div class="pos-wrap" x-data x-init="$store.pos.initStore(window.posTerminalConfig)">

    <!-- 1. SIDEBAR -->
    <div class="pos-sidebar">
        <div class="pos-sidebar-header d-flex justify-content-between align-items-center">
            <div class="font-weight-bold tx-18"><i class="fe fe-terminal mr-2"></i>POS Workspace</div>
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-light"><i class="fe fe-x"></i></a>
        </div>

        <div class="pos-sidebar-section bg-light">
            <div class="pos-sidebar-section-title">Terminal State</div>
            @if($activeShift)
                <div class="d-flex align-items-center mb-2">
                    <span class="badge badge-success p-2 mr-2"><i class="fe fe-unlock"></i> Open</span>
                    <small class="text-muted">Since {{ $activeShift->opened_at->format('H:i') }}</small>
                </div>
                <button class="btn btn-outline-danger btn-sm btn-block" data-toggle="modal" data-target="#closeShiftModal">
                    <i class="fe fe-lock mr-1"></i> Close Register
                </button>
            @else
                <div class="d-flex align-items-center mb-2">
                    <span class="badge badge-danger p-2 mr-2"><i class="fe fe-lock"></i> Closed</span>
                    <small class="text-muted">Register is locked</small>
                </div>
                <button class="btn btn-success btn-sm btn-block" data-toggle="modal" data-target="#openShiftModal">
                    <i class="fe fe-unlock mr-1"></i> Open Register
                </button>
            @endif
        </div>

        <div class="pos-sidebar-section">
            <div class="pos-sidebar-section-title d-flex justify-content-between align-items-center">
                <span>Customer</span>
                <button class="btn btn-link p-0 tx-12" data-toggle="modal" data-target="#quickCustomerModal"><i class="fe fe-user-plus"></i> Add New</button>
            </div>
            <select class="form-control" x-model="$store.pos.customerId">
                <option value="">— Walk-in Customer —</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
                <template x-for="c in $store.pos.newCustomers" :key="c.id">
                    <option :value="c.id" x-text="c.name" :selected="$store.pos.customerId == c.id"></option>
                </template>
            </select>
        </div>

        <div class="pos-sidebar-section">
            <div class="pos-sidebar-section-title">Quick Actions</div>
            <div class="d-grid gap-2">
                <button class="btn btn-outline-warning btn-block text-left mb-2" @click="$store.pos.holdCurrentOrder()">
                    <i class="fe fe-pause-circle mr-2"></i> Hold Current Order
                </button>
                <button class="btn btn-outline-primary btn-block text-left mb-2" data-toggle="modal" data-target="#heldOrdersModal">
                    <i class="fe fe-list mr-2"></i> Held Orders 
                    <span x-show="$store.pos.heldOrders.length > 0" x-text="$store.pos.heldOrders.length" class="badge badge-danger float-right mt-1"></span>
                </button>
                <a href="{{ route('pos.sales.index') }}" class="btn btn-outline-info btn-block text-left">
                    <i class="fe fe-archive mr-2"></i> View Sales History
                </a>
            </div>
        </div>
    </div>

    <!-- 2. CATALOG -->
    <div class="pos-catalog">
        <div class="pos-toolbar mt-2 mx-3 rounded" style="box-shadow: 0 4px 12px rgba(0,0,0,0.05); border:none;">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-right-0"><i class="fe fe-search text-muted tx-18"></i></span>
                </div>
                <input type="text" x-model="$store.pos.searchQuery" @input.debounce.300ms="$store.pos.onSearch()"
                       @keydown.enter.prevent="$store.pos.barcodeSearch()"
                       class="form-control border-left-0 form-control-lg pl-0" 
                       placeholder="Scan barcode or type name..." id="pos-search">
            </div>
        </div>

        <div class="pos-category-tabs mt-2 mx-3 bg-transparent border-0 px-0">
            <span class="cat-tab" :class="{'active': $store.pos.selectedCategory === 'all'}" @click="$store.pos.selectedCategory='all'">All Products</span>
            @foreach($categories as $cat)
                <span class="cat-tab" :class="{'active': $store.pos.selectedCategory == {{ $cat->id }}}" @click="$store.pos.selectedCategory={{ $cat->id }}">{{ $cat->name }}</span>
            @endforeach
        </div>

        <div class="pos-products">
            @if(!$activeShift)
            <div class="alert alert-danger px-4 py-3 shadow-sm border-0 d-flex align-items-center">
                <i class="fe fe-alert-triangle tx-24 mr-3"></i>
                <div>
                    <h5 class="mb-1 text-danger font-weight-bold">Register is Closed</h5>
                    <p class="mb-0">Please open a shift to start selling.</p>
                </div>
            </div>
            @endif

            <ul class="row row-sm list-unstyled">
                <template x-for="product in $store.pos.filteredProducts" :key="product.id">
                    <li class="col-6 col-md-4 col-lg-3 col-xl-3 mb-4">
                        <div class="pos-product-card h-100" :class="{'out-stock': product.out_of_stock}" @click="!product.out_of_stock && $store.pos.addToCart(product)">
                            <template x-if="product.low_stock && !product.out_of_stock"><span class="badge badge-warning stock-warn shadow-sm">Low Stock</span></template>
                            <template x-if="product.out_of_stock"><span class="badge badge-danger stock-warn shadow-sm">Out of Stock</span></template>
                            <div class="product-img-wrapper"><img :src="product.image || 'https://via.placeholder.com/150'" :alt="product.name"></div>
                            <div class="p-3">
                                <div class="text-muted tx-11 text-uppercase mb-1" x-text="product.category || 'General'"></div>
                                <h6 class="font-weight-bold mb-2 text-truncate" x-text="product.name"></h6>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <h5 class="text-primary font-weight-bold mb-0" x-text="$store.pos.currencySymbol + product.price.toFixed(2)"></h5>
                                    <small :class="product.out_of_stock ? 'text-danger' : 'text-success'" x-text="product.stock + ' in stock'"></small>
                                </div>
                            </div>
                        </div>
                    </li>
                </template>
            </ul>
        </div>
    </div>

    <!-- 3. CART -->
    <div class="pos-cart">
        <div class="pos-cart-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 font-weight-bold tx-18"><i class="fe fe-shopping-cart mr-2"></i>Current Order</h5>
                <span class="badge badge-light border bg-white p-2">
                    <span class="font-weight-bold tx-14" x-text="$store.pos.cart.length"></span> items
                </span>
            </div>
        </div>

        <div class="pos-cart-body" id="cart-scroll-area">
            <template x-if="$store.pos.cart.length === 0">
                <div class="text-center py-5">
                    <div class="mb-4"><i class="fe fe-shopping-cart text-light" style="font-size: 80px; opacity: 0.2;"></i></div>
                    <h6 class="text-muted">Cart is empty</h6>
                </div>
            </template>

            <template x-for="(item, idx) in $store.pos.cart" :key="idx">
                <div class="cart-row position-relative">
                    <button class="btn btn-sm btn-icon text-muted position-absolute bg-transparent border-0" style="top: 10px; right: 10px;" @click="$store.pos.removeItem(idx)"><i class="fe fe-trash-2 text-danger"></i></button>
                    <div class="w-100 pr-4">
                        <div class="font-weight-bold tx-15 mb-1" x-text="item.name"></div>
                        <div class="d-flex justify-content-between align-items-end mt-2">
                            <div class="qty-control">
                                <button class="qty-btn" @click="$store.pos.updateQty(idx, -1)"><i class="fe fe-minus"></i></button>
                                <input type="number" class="qty-input" x-model.number="item.qty" @change="$store.pos.validateQty(idx)" min="1">
                                <button class="qty-btn" @click="$store.pos.updateQty(idx, 1)"><i class="fe fe-plus"></i></button>
                            </div>
                            <div class="text-right">
                                <div class="text-muted tx-12" x-text="'@ ' + $store.pos.currencySymbol + item.price.toFixed(2)"></div>
                                <div class="font-weight-bold text-primary tx-16" x-text="$store.pos.currencySymbol + $store.pos.itemTotal(item).toFixed(2)"></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-2">
                            <span class="tx-11 text-muted mr-2">Disc%</span>
                            <input type="number" x-model.number="item.discount_pct" min="0" max="100" step="0.5"
                                class="form-control form-control-sm py-0" style="width:65px; height:26px; font-size:0.82em;"
                                placeholder="0">
                            <span class="tx-11 text-danger ml-2" x-show="item.discount_pct > 0"
                                x-text="'- ' + $store.pos.currencySymbol + (item.price * item.qty * item.discount_pct / 100).toFixed(2)"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="pos-cart-footer shadow-lg">
            <div class="d-flex align-items-center justify-content-between mb-3 bg-light p-2 rounded">
                <span class="tx-12 font-weight-bold">Order Discount</span>
                <div class="input-group input-group-sm w-50">
                    <div class="input-group-prepend"><span class="input-group-text" x-text="$store.pos.currencySymbol"></span></div>
                    <input type="number" x-model.number="$store.pos.orderDiscount" class="form-control font-weight-bold text-right py-0">
                </div>
            </div>

            <div class="px-2">
                <div class="pos-total-row"><span>Subtotal :</span><span x-text="$store.pos.currencySymbol + $store.pos.subtotal.toFixed(2)"></span></div>
                <div class="pos-total-row text-danger" x-show="$store.pos.totalDiscount > 0"><span>Discount :</span><span x-text="'- ' + $store.pos.currencySymbol + $store.pos.totalDiscount.toFixed(2)"></span></div>
                <div class="pos-total-row"><span>Tax (<span x-text="$store.pos.taxRate"></span>%) :</span><span x-text="$store.pos.currencySymbol + $store.pos.tax.toFixed(2)"></span></div>
                <div class="pos-total-row border-top pt-3 mt-2 mb-3">
                    <span class="pos-grand-total">Total</span>
                    <span class="pos-grand-total text-success" x-text="$store.pos.currencySymbol + $store.pos.total.toFixed(2)"></span>
                </div>
            </div>

            <button class="btn btn-success btn-block btn-pay" data-toggle="modal" data-target="#checkoutModal" :disabled="$store.pos.cart.length === 0 || !$store.pos.shiftId">
                <i class="fe fe-credit-card mr-2"></i> PAY <span x-text="$store.pos.currencySymbol + $store.pos.total.toFixed(2)"></span>
            </button>

            <div class="d-flex justify-content-center mt-2" style="gap:12px;">
                <span style="font-size:0.72em; color:#bbb;"><kbd style="background:#f0f0f0;border:1px solid #ddd;border-radius:3px;padding:1px 5px;font-size:0.9em;">F2</kbd> Search</span>
                <span style="font-size:0.72em; color:#bbb;"><kbd style="background:#f0f0f0;border:1px solid #ddd;border-radius:3px;padding:1px 5px;font-size:0.9em;">F4</kbd> Checkout</span>
                <span style="font-size:0.72em; color:#bbb;"><kbd style="background:#f0f0f0;border:1px solid #ddd;border-radius:3px;padding:1px 5px;font-size:0.9em;">Esc</kbd> Clear Search</span>
            </div>
        </div>
    </div>

    <!-- MODALS (Held, Reg, Success etc) -->
    @include('pos.partials.modals') 

</div>
@endsection

@section('js')
<script src="{{ asset('js/pos-terminal.js') }}"></script>
@endsection
