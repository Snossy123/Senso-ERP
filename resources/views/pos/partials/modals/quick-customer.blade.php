<!-- Quick Customer Modal -->
<div class="modal fade" id="quickCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg border-0 rounded-xl">
            <div class="modal-header bg-primary text-white border-0"><h5 class="modal-title mb-0">New customer</h5></div>
            <div class="modal-body p-4">
                <div class="form-group mb-3"><input id="qcName" type="text" x-model="$store.pos.newCustomer.name" class="form-control rounded-lg" placeholder="Full name *" tabindex="80"></div>
                <div class="form-group mb-3"><input id="qcPhone" type="text" x-model="$store.pos.newCustomer.phone" class="form-control rounded-lg" placeholder="Phone" tabindex="81"></div>
                <div x-show="$store.pos.newCustomer.error" class="alert alert-danger py-2 tx-12 rounded-lg" x-text="$store.pos.newCustomer.error"></div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-primary btn-block rounded-lg font-weight-bold touch-target-modal" style="min-height:44px" tabindex="82" @click="$store.pos.saveQuickCustomer()">Save customer</button>
            </div>
        </div>
    </div>
</div>
