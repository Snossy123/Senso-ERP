<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" data-backdrop="static" aria-labelledby="successModalLabel">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg rounded-2xl overflow-hidden">
            <div class="modal-body text-center py-5 px-4 bg-white pos-success-reveal">
                <p id="successModalLabel" class="text-muted sr-only-emulate" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0;">Sale completed</p>
                <div class="mb-4 d-inline-block p-3 rounded-circle bg-success-transparent animate__animated animate__bounceIn">
                    <i class="fe fe-check-circle text-success tx-72"></i>
                </div>
                <h3 class="font-weight-bold text-dark">Payment received</h3>
                <p class="text-muted tx-14 mb-4">Sale recorded · inventory and accounting synced.</p>

                <div id="pos-receipt-print-mount" class="mb-4 text-left" x-show="$store.pos.lastReceiptPreview" x-cloak>
                    <div class="pos-receipt-print-root pos-receipt-print-root--compact">
                        <div class="pos-receipt-logo-slot">Logo</div>
                        <div class="pos-receipt-brand" x-text="$store.pos.receiptPreviewNormalized.tenant_name"></div>
                        <div class="pos-receipt-title">Sales receipt</div>
                        <div class="pos-receipt-meta-grid">
                            <div x-show="$store.pos.receiptPreviewNormalized.sale_number"><span>Sale #</span><span x-text="$store.pos.receiptPreviewNormalized.sale_number"></span></div>
                            <div x-show="!$store.pos.receiptPreviewNormalized.sale_number && $store.pos.receiptPreviewNormalized.sale_id"><span>Sale ID</span><span x-text="$store.pos.receiptPreviewNormalized.sale_id"></span></div>
                            <div x-show="$store.pos.receiptPreviewNormalized.sale_date"><span>Date</span><span x-text="$store.pos.receiptPreviewNormalized.sale_date"></span></div>
                            <div x-show="$store.pos.receiptPreviewNormalized.cashier_name"><span>Cashier</span><span x-text="$store.pos.receiptPreviewNormalized.cashier_name"></span></div>
                            <div x-show="$store.pos.receiptPreviewNormalized.customer_name"><span>Customer</span><span x-text="$store.pos.receiptPreviewNormalized.customer_name"></span></div>
                        </div>
                        <table class="pos-receipt-lines">
                            <thead>
                                <tr>
                                    <th class="pos-receipt-col-qty">Qty</th>
                                    <th class="pos-receipt-col-item">Item</th>
                                    <th class="pos-receipt-col-unit">Each</th>
                                    <th class="pos-receipt-col-amt">Line</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(line, li) in $store.pos.receiptPreviewNormalized.lines" :key="li">
                                    <tr>
                                        <td class="pos-receipt-col-qty" x-text="line.qty"></td>
                                        <td class="pos-receipt-col-item" x-text="line.name"></td>
                                        <td class="pos-receipt-col-unit" x-text="line.unit_price || '—'"></td>
                                        <td class="pos-receipt-col-amt" x-text="line.total"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <div class="pos-receipt-totals">
                            <div class="pos-receipt-row"><span>Subtotal</span><span x-text="$store.pos.receiptPreviewNormalized.subtotal"></span></div>
                            <div class="pos-receipt-row" x-show="$store.pos.receiptPreviewNormalized.discount"><span>Discount</span><span x-text="$store.pos.receiptPreviewNormalized.discount"></span></div>
                            <div class="pos-receipt-row"><span>Tax</span><span x-text="$store.pos.receiptPreviewNormalized.tax"></span></div>
                            <div class="pos-receipt-row pos-receipt-row--total"><span>Total</span><span x-text="$store.pos.receiptPreviewNormalized.total"></span></div>
                            <div class="pos-receipt-row"><span>Payment</span><span class="text-uppercase" x-text="$store.pos.receiptPreviewNormalized.payment"></span></div>
                            <div class="pos-receipt-row"><span>Change</span><span x-text="$store.pos.receiptPreviewNormalized.change"></span></div>
                        </div>
                        <div class="pos-receipt-qr mt-2">QR · digital receipt · loyalty</div>
                    </div>
                </div>

                <button type="button" class="btn btn-outline-dark btn-lg btn-block rounded-xl mb-3 touch-target-modal" style="min-height:48px" x-show="$store.pos.lastReceiptPreview" @click="$store.pos.printLastReceipt()">
                    <i class="fe fe-printer mr-2"></i> Print receipt preview
                </button>

                <div class="rounded-xl border shadow-inner p-3 mb-4 bg-gradient-light" x-show="$store.pos.paymentMethod === 'cash' && $store.pos.lastSaleChangeDue > 0">
                    <div class="tx-11 text-muted text-uppercase font-weight-bold">Change to customer</div>
                    <h2 class="font-weight-bolder text-success mb-0 pos-tabular" x-text="$store.pos.moneyLabel($store.pos.lastSaleChangeDue)"></h2>
                </div>

                <a :href="'/pos/sales/' + $store.pos.lastSaleId" target="_blank" class="btn btn-outline-primary btn-lg btn-block rounded-xl mb-2 touch-target-modal" style="min-height:48px">
                    <i class="fe fe-external-link mr-2"></i> Open sale detail
                </a>
                <button type="button" class="btn btn-primary btn-lg btn-block rounded-xl font-weight-bold touch-target-modal" style="min-height:48px" data-dismiss="modal" @click="$store.pos.clearCartState()">
                    <i class="fe fe-plus mr-2"></i> New transaction
                </button>
            </div>
        </div>
    </div>
</div>
