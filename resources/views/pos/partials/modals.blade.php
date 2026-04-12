<!-- Held Orders Modal -->
<div class="modal fade" id="heldOrdersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold"><i class="fe fe-pause-circle mr-2"></i>Held Orders</h5>
                <button class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <template x-if="$store.pos.heldOrders.length === 0">
                    <div class="text-muted text-center py-5">
                        <i class="fe fe-inbox tx-50 text-light mb-3 block"></i>
                        <h5>No held orders.</h5>
                    </div>
                </template>
                <div class="row">
                    <template x-for="held in $store.pos.heldOrders" :key="held.id">
                        <div class="col-md-6 mb-3">
                            <div class="card shadow-sm border-0 h-100 mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <strong x-text="held.label || 'Unnamed Order'" class="tx-15"></strong>
                                        <span class="badge badge-warning-transparent px-2 py-1">Hold</span>
                                    </div>
                                    <div class="text-muted tx-13 mb-3" x-text="held.cart_data.length + ' items'"></div>
                                    <button class="btn btn-block btn-outline-primary btn-sm" @click="$store.pos.resumeOrder(held.id)">Resume</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Open Shift Modal -->
<div class="modal fade" id="openShiftModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success"><h5 class="modal-title text-white text-center w-100">Open Register</h5></div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted tx-13">Enter initial cash amount</p>
                <div class="input-group input-group-lg">
                    <div class="input-group-prepend"><span class="input-group-text" x-text="$store.pos.currencySymbol"></span></div>
                    <input type="number" x-model.number="$store.pos.shiftOpenFloat" class="form-control text-center font-weight-bold">
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button class="btn btn-block btn-success btn-lg font-weight-bold" @click="$store.pos.openShift()">START SHIFT</button>
            </div>
        </div>
    </div>
</div>

<!-- Close Shift Modal -->
<div class="modal fade" id="closeShiftModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white"><h5 class="modal-title">Close Register</h5></div>
            <div class="modal-body p-4">
                <label class="font-weight-bold">Counted Cash Amount</label>
                <div class="input-group input-group-lg mb-3">
                    <div class="input-group-prepend"><span class="input-group-text font-weight-bold" x-text="$store.pos.currencySymbol"></span></div>
                    <input type="number" x-model.number="$store.pos.shiftCloseFloat" class="form-control font-weight-bold tx-20">
                </div>
                <textarea x-model="$store.pos.shiftNotes" class="form-control" placeholder="Closing notes..."></textarea>
            </div>
            <div class="modal-footer bg-light border-0">
                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button class="btn btn-danger px-4" @click="$store.pos.closeShift()">Close Shift</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Customer Modal -->
<div class="modal fade" id="quickCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">New Customer</h5></div>
            <div class="modal-body p-4">
                <div class="form-group mb-3"><input type="text" x-model="$store.pos.newCustomer.name" class="form-control" placeholder="Full Name *"></div>
                <div class="form-group mb-3"><input type="text" x-model="$store.pos.newCustomer.phone" class="form-control" placeholder="Phone"></div>
                <div x-show="$store.pos.newCustomer.error" class="alert alert-danger py-2 tx-12" x-text="$store.pos.newCustomer.error"></div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button class="btn btn-primary btn-block" @click="$store.pos.saveQuickCustomer()">Save Customer</button>
            </div>
        </div>
    </div>
</div>

<!-- Variant Selection Modal -->
<div class="modal fade" id="variantModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light"><h5 class="modal-title font-weight-bold">Options</h5></div>
            <div class="modal-body p-2">
                <div class="list-group list-group-flush border rounded overflow-hidden">
                    <template x-for="v in $store.pos.activeProductVariants" :key="v.id">
                        <button class="list-group-item list-group-item-action d-flex justify-content-between py-3" @click="$store.pos.addVariantToCart(v)">
                            <span x-text="v.name"></span>
                            <span class="badge badge-primary" x-text="$store.pos.currencySymbol + v.price.toFixed(2)"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div class="modal fade checkout-modal" id="checkoutModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-xl overflow-hidden">
            <div class="modal-header border-bottom bg-light py-3">
                <h4 class="modal-title font-weight-bold"><i class="fe fe-credit-card mr-2 text-primary"></i>Checkout</h4>
                <button class="close" data-dismiss="modal" @click="$store.pos.resetCheckout()"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <div class="row no-gutters">
                    <!-- Left: Breakdown -->
                    <div class="col-md-5 bg-light p-4">
                        <h6 class="text-uppercase text-muted font-weight-bold tx-11 mb-4">Summary</h6>
                        <div class="d-flex justify-content-between mb-2"><span>Subtotal</span> <span class="font-weight-bold" x-text="$store.pos.currencySymbol + $store.pos.subtotal.toFixed(2)"></span></div>
                        <div class="d-flex justify-content-between mb-2 text-danger" x-show="$store.pos.totalDiscount > 0"><span>Discount</span> <span x-text="'- ' + $store.pos.currencySymbol + $store.pos.totalDiscount.toFixed(2)"></span></div>
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-3"><span>Tax</span> <span x-text="$store.pos.currencySymbol + $store.pos.tax.toFixed(2)"></span></div>
                        
                        <div class="bg-primary text-white p-4 rounded text-center shadow">
                            <span class="d-block tx-12 opacity-75 mb-1">TOTAL DUE</span>
                            <h1 class="font-weight-bolder mb-0" x-text="$store.pos.currencySymbol + $store.pos.total.toFixed(2)"></h1>
                        </div>
                    </div>

                    <!-- Right: Payment -->
                    <div class="col-md-7 p-4 bg-white">
                        <h6 class="text-uppercase text-muted font-weight-bold tx-11 mb-4">Payment</h6>
                        <div class="row mb-4">
                            <div class="col-4 px-1"><div class="payment-method-btn" :class="$store.pos.paymentMethod === 'cash' ? 'active' : ''" @click="$store.pos.paymentMethod = 'cash'; $store.pos.amountTendered = $store.pos.total.toFixed(2)"><i class="fe fe-dollar-sign"></i> Cash</div></div>
                            <div class="col-4 px-1"><div class="payment-method-btn" :class="$store.pos.paymentMethod === 'card' ? 'active' : ''" @click="$store.pos.paymentMethod = 'card'; $store.pos.amountTendered = $store.pos.total.toFixed(2)"><i class="fe fe-credit-card"></i> Card</div></div>
                            <div class="col-4 px-1"><div class="payment-method-btn" :class="$store.pos.paymentMethod === 'bank_transfer' ? 'active' : ''" @click="$store.pos.paymentMethod = 'bank_transfer'; $store.pos.amountTendered = $store.pos.total.toFixed(2)"><i class="fe fe-smartphone"></i> Transfer</div></div>
                        </div>

                        <div x-show="$store.pos.paymentMethod === 'cash'">
                            <label class="tx-11 font-weight-bold">CASH TENDERED</label>
                            <div class="input-group input-group-lg mb-3">
                                <input type="number" x-model.number="$store.pos.amountTendered" class="form-control font-weight-bold tx-24 text-center py-4 bg-light border-0">
                                <div class="input-group-append"><button class="btn btn-secondary px-3" @click="$store.pos.amountTendered = $store.pos.total.toFixed(2)">Exact</button></div>
                            </div>
                            <div class="row row-xs mb-3">
                                <div class="col"><button class="btn btn-outline-secondary btn-block" @click="$store.pos.addTendered(10)">+<span x-text="$store.pos.currencySymbol"></span>10</button></div>
                                <div class="col"><button class="btn btn-outline-secondary btn-block" @click="$store.pos.addTendered(20)">+<span x-text="$store.pos.currencySymbol"></span>20</button></div>
                                <div class="col"><button class="btn btn-outline-secondary btn-block" @click="$store.pos.addTendered(50)">+<span x-text="$store.pos.currencySymbol"></span>50</button></div>
                            </div>
                            <div class="p-3 mb-3 rounded d-flex justify-content-between align-items-center" :class="$store.pos.changeDue >= 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger'">
                                <span class="font-weight-bold">Change Due:</span>
                                <h3 class="mb-0 font-weight-bold" x-text="$store.pos.currencySymbol + Math.max(0, $store.pos.changeDue).toFixed(2)"></h3>
                            </div>
                        </div>

                        <textarea x-model="$store.pos.notes" class="form-control" rows="2" placeholder="Note (optional)"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer p-3 bg-light">
                <button class="btn btn-block btn-primary btn-lg font-weight-bold" @click="$store.pos.processPayment()" :disabled="$store.pos.processing || ($store.pos.paymentMethod === 'cash' && $store.pos.amountTendered < $store.pos.total)">
                    <span x-show="!$store.pos.processing">COMPLETE SALES</span>
                    <span x-show="$store.pos.processing"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-20 overflow-hidden">
            <div class="modal-body text-center py-5 px-4 bg-white">
                <div class="mb-4 d-inline-block p-3 rounded-circle bg-success-transparent animate__animated animate__bounceIn">
                    <i class="fe fe-check-circle text-success tx-80"></i>
                </div>
                <h3 class="font-weight-bold text-dark">Order Completed!</h3>
                <p class="text-muted tx-14 mb-4">The transaction was successful.</p>
                
                <div class="bg-light p-3 rounded mb-4 shadow-inner" x-show="$store.pos.paymentMethod === 'cash' && $store.pos.changeDue > 0">
                    <div class="tx-11 text-muted text-uppercase font-weight-bold">Change to return</div>
                    <h2 class="font-weight-bolder text-success mb-0" x-text="$store.pos.currencySymbol + $store.pos.changeDue.toFixed(2)"></h2>
                </div>
                
                <a :href="'/pos/sales/' + $store.pos.lastSaleId" target="_blank" class="btn btn-outline-primary btn-lg btn-block mb-3">
                    <i class="fe fe-printer mr-2"></i> Print Receipt
                </a>
                <button class="btn btn-primary btn-lg btn-block" data-dismiss="modal" @click="$store.pos.clearCartState()">
                    <i class="fe fe-plus mr-2"></i> New Transaction
                </button>
            </div>
        </div>
    </div>
</div>
