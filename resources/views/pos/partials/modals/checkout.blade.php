<!-- Checkout Modal -->
<div class="modal fade checkout-modal pos-checkout-modal-root" id="checkoutModal" tabindex="-1" data-backdrop="static" aria-labelledby="checkoutModalLabel">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-xl overflow-hidden rounded-xl">
            <div class="modal-header border-bottom bg-white py-3 px-4 align-items-center">
                <div>
                    <h4 id="checkoutModalLabel" class="modal-title font-weight-bold mb-0"><i class="fe fe-credit-card mr-2 text-primary"></i>Checkout</h4>
                    <small class="text-muted">Tab through payment controls · <kbd class="rounded border px-1 bg-light tx-11">Esc</kbd> close</small>
                </div>
                <button type="button" class="close pos-modal-close-fix" data-dismiss="modal" @click="$store.pos.resetCheckout()"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <div class="row no-gutters">
                    <!-- Left: Breakdown -->
                    <div class="col-md-5 bg-light p-4 border-md-right checkout-summary-pane">
                        <h6 class="text-uppercase text-muted font-weight-bold tx-11 mb-4">Summary</h6>
                        <div class="d-flex justify-content-between mb-2"><span>Subtotal</span> <span class="font-weight-bold" x-text="$store.pos.currencySymbol + $store.pos.subtotal.toFixed(2)"></span></div>
                        <div class="d-flex justify-content-between mb-2 text-danger" x-show="$store.pos.totalDiscount > 0"><span>Discount</span> <span x-text="'- ' + $store.pos.currencySymbol + $store.pos.totalDiscount.toFixed(2)"></span></div>
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-3"><span>Tax</span> <span x-text="$store.pos.currencySymbol + $store.pos.tax.toFixed(2)"></span></div>

                        <div class="bg-dark text-white p-4 rounded-lg text-center shadow-sm">
                            <span class="d-block tx-12 opacity-75 mb-1 text-uppercase">Total due</span>
                            <h1 class="font-weight-bolder mb-0 checkout-total-display" tabindex="91" aria-live="polite" x-text="$store.pos.currencySymbol + $store.pos.total.toFixed(2)"></h1>
                        </div>
                    </div>

                    <!-- Right: Payment -->
                    <div class="col-md-7 p-4 bg-white">
                        <h6 class="text-uppercase text-muted font-weight-bold tx-11 mb-3">Payment method</h6>
                        <div class="row mb-4 checkout-payment-row">
                            <div class="col-12 col-lg-4 mb-2 px-1">
                                <div id="checkoutPayCash"
                                    tabindex="92"
                                    role="button"
                                    class="payment-method-btn rounded-xl touch-target-modal h-100 d-flex flex-column align-items-center justify-content-center"
                                    style="min-height:56px;"
                                    :class="$store.pos.paymentMethod === 'cash' ? 'active' : ''"
                                    @keydown.enter.prevent="$store.pos.paymentMethod = 'cash'; $store.pos.amountTendered = $store.pos.total.toFixed(2)"
                                    @click="$store.pos.paymentMethod = 'cash'; $store.pos.amountTendered = $store.pos.total.toFixed(2)">
                                    <i class="fe fe-dollar-sign mb-1"></i><span class="small font-weight-bold">Cash</span>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 mb-2 px-1">
                                <div id="checkoutPayCard"
                                    tabindex="93"
                                    role="button"
                                    class="payment-method-btn rounded-xl touch-target-modal h-100 d-flex flex-column align-items-center justify-content-center"
                                    style="min-height:56px;"
                                    :class="$store.pos.paymentMethod === 'card' ? 'active' : ''"
                                    @keydown.enter.prevent="$store.pos.paymentMethod = 'card'; $store.pos.amountTendered = $store.pos.total.toFixed(2)"
                                    @click="$store.pos.paymentMethod = 'card'; $store.pos.amountTendered = $store.pos.total.toFixed(2)">
                                    <i class="fe fe-credit-card mb-1"></i><span class="small font-weight-bold">Card</span>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 mb-2 px-1">
                                <div id="checkoutPayTransfer"
                                    tabindex="94"
                                    role="button"
                                    class="payment-method-btn rounded-xl touch-target-modal h-100 d-flex flex-column align-items-center justify-content-center"
                                    style="min-height:56px;"
                                    :class="$store.pos.paymentMethod === 'bank_transfer' ? 'active' : ''"
                                    @keydown.enter.prevent="$store.pos.paymentMethod = 'bank_transfer'; $store.pos.amountTendered = $store.pos.total.toFixed(2)"
                                    @click="$store.pos.paymentMethod = 'bank_transfer'; $store.pos.amountTendered = $store.pos.total.toFixed(2)">
                                    <i class="fe fe-smartphone mb-1"></i><span class="small font-weight-bold">Transfer</span>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2 checkout-payment-row">
                            <div class="col-12 col-lg-4 mb-2 px-1">
                                <div id="checkoutPaySplit"
                                    tabindex="94b"
                                    role="button"
                                    class="payment-method-btn rounded-xl touch-target-modal w-100 d-flex flex-column align-items-center justify-content-center"
                                    style="min-height:56px;"
                                    :class="$store.pos.paymentMethod === 'split' ? 'active' : ''"
                                    @keydown.enter.prevent="$store.pos.selectSplitPayment()"
                                    @click="$store.pos.selectSplitPayment()">
                                    <i class="fe fe-layers mb-1"></i><span class="small font-weight-bold">Split pay</span>
                                </div>
                            </div>
                        </div>

                        <div x-show="$store.pos.paymentMethod === 'split'" x-transition class="mb-4">
                            <h6 class="text-uppercase text-muted font-weight-bold tx-11 mb-2">Split tenders (preview)</h6>
                            <p class="tx-12 text-muted mb-2">Architecture only — backend settlement unchanged. Amounts must match total due.</p>
                            <template x-for="(row, sIdx) in $store.pos.splitRows" :key="sIdx">
                                <div class="pos-split-tender-row">
                                    <select class="form-control rounded-xl border" x-model="row.method" :aria-label="'Tender ' + (sIdx + 1)">
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="bank_transfer">Transfer</option>
                                    </select>
                                    <input type="number" step="0.01" min="0" class="form-control rounded-xl border text-right font-weight-bold"
                                        placeholder="0.00" x-model.number="row.amount"
                                        :aria-label="'Amount tender ' + (sIdx + 1)">
                                </div>
                            </template>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-xl" @click="$store.pos.addSplitRow()">Add tender</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-xl" x-show="$store.pos.splitRows.length > 2" @click="$store.pos.removeLastSplitRow()">Remove last</button>
                            </div>
                            <div class="pos-split-remainder-pill mb-2"
                                :class="Math.abs($store.pos.splitRemainingAmount) <= 0.02 ? 'pos-split-remainder-pill--ok' : 'pos-split-remainder-pill--warn'">
                                <span class="mr-2">Remaining:</span>
                                <span x-text="$store.pos.currencySymbol + Number($store.pos.splitRemainingAmount || 0).toFixed(2)"></span>
                            </div>
                        </div>

                        <div x-show="$store.pos.paymentMethod === 'cash'" x-transition>
                            <label class="tx-11 font-weight-bold text-muted">Cash tendered</label>
                            <div class="input-group input-group-lg mb-3 shadow-sm rounded-xl overflow-hidden border">
                                <input type="number" id="checkoutAmountTendered" tabindex="95" aria-label="Amount tendered"
                                    x-model.number="$store.pos.amountTendered"
                                    class="form-control font-weight-bold tx-22 text-center py-3 bg-light border-0">
                                <div class="input-group-append"><button type="button" tabindex="96" class="btn btn-secondary px-3 border-0" @click="$store.pos.amountTendered = $store.pos.total.toFixed(2)">Exact</button></div>
                            </div>
                            <div class="row row-xs mb-3 mx-n1">
                                <div class="col px-1"><button type="button" tabindex="97" class="btn btn-outline-secondary btn-block rounded-xl touch-target-modal" style="min-height:44px" @click="$store.pos.addTendered(10)">+<span x-text="$store.pos.currencySymbol"></span>10</button></div>
                                <div class="col px-1"><button type="button" tabindex="98" class="btn btn-outline-secondary btn-block rounded-xl touch-target-modal" style="min-height:44px" @click="$store.pos.addTendered(20)">+<span x-text="$store.pos.currencySymbol"></span>20</button></div>
                                <div class="col px-1"><button type="button" tabindex="99" class="btn btn-outline-secondary btn-block rounded-xl touch-target-modal" style="min-height:44px" @click="$store.pos.addTendered(50)">+<span x-text="$store.pos.currencySymbol"></span>50</button></div>
                            </div>
                            <div class="p-3 mb-3 rounded-xl d-flex justify-content-between align-items-center border" :class="$store.pos.changeDue >= 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger'">
                                <span class="font-weight-bold text-uppercase tx-11">Change due</span>
                                <h3 class="mb-0 font-weight-bold" x-text="$store.pos.currencySymbol + Math.max(0, $store.pos.changeDue).toFixed(2)"></h3>
                            </div>
                        </div>

                        <label class="tx-11 font-weight-bold text-muted mb-2">Receipt note</label>
                        <textarea id="checkoutNotes" tabindex="100" aria-label="Order notes" x-model="$store.pos.notes" class="form-control rounded-xl border mb-3" rows="2" placeholder="Optional note shown on audit trail"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer p-4 bg-white border-top">
                <button type="button"
                    tabindex="101"
                    id="checkoutSubmitBtn"
                    class="btn btn-block btn-dark btn-lg font-weight-bold rounded-xl checkout-complete-btn shadow-sm touch-target-modal"
                    style="min-height:54px;"
                    data-pos-checkout-complete
                    @click="$store.pos.processPayment()"
                    :disabled="$store.pos.processing || $store.pos.paymentBlocked || $store.pos.cartStockBlocked || ($store.pos.paymentMethod === 'cash' && $store.pos.amountTendered < $store.pos.total) || ($store.pos.paymentMethod === 'split' && Math.abs($store.pos.splitRemainingAmount) > 0.02)">
                    <span x-show="!$store.pos.processing">Complete sale</span>
                    <span x-show="$store.pos.processing"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
            </div>
        </div>
    </div>
</div>
