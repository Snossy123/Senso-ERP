<!-- Held Orders Modal -->
<div class="modal fade" id="heldOrdersModal" tabindex="-1" aria-labelledby="heldOrdersModalLabel">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-xl border-0 shadow-lg overflow-hidden">
            <div class="modal-header align-items-center border-bottom py-3">
                <div>
                    <h5 id="heldOrdersModalLabel" class="modal-title font-weight-bold mb-0"><i class="fe fe-pause-circle mr-2"></i>Held Orders</h5>
                    <small class="text-muted">Resume a parked sale or swipe through on tablet.</small>
                </div>
                <button type="button" class="close pos-modal-close-fix" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <template x-if="$store.pos.heldOrders.length === 0">
                    <div class="text-muted text-center py-5 rounded-xl bg-white shadow-sm border">
                        <i class="fe fe-inbox tx-50 text-muted mb-3 block"></i>
                        <h5 class="font-weight-semibold">No held orders</h5>
                        <p class="tx-13 mb-0">Park a sale from the terminal to retrieve it here.</p>
                    </div>
                </template>
                <div class="row">
                    <template x-for="held in $store.pos.heldOrders" :key="held.id">
                        <div class="col-md-6 mb-3">
                            <div class="card shadow-sm border-0 h-100 mb-0 rounded-xl">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <strong class="tx-14" x-text="held.label || 'Unnamed Order'"></strong>
                                        <span class="badge badge-warning-transparent px-2 py-1 rounded-pill">Hold</span>
                                    </div>
                                    <div class="text-muted tx-13 mb-3" x-text="held.cart_data.length + ' items'"></div>
                                    <button type="button" class="btn btn-block btn-outline-primary btn-sm rounded-lg touch-target-modal" style="min-height:44px" @click="$store.pos.resumeOrder(held.id)">Resume</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
