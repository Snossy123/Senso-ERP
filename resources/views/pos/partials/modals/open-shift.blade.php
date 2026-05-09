<!-- Open Shift Modal -->
<div class="modal fade" id="openShiftModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-xl overflow-hidden">
            <div class="modal-header bg-success border-0"><h5 class="modal-title text-white text-center w-100 mb-0">Open Register</h5></div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted tx-13">Enter opening float</p>
                <div class="input-group input-group-lg rounded-lg overflow-hidden shadow-sm border">
                    <div class="input-group-prepend"><span class="input-group-text border-0" x-text="$store.pos.currencySymbol"></span></div>
                    <input type="number" id="openShiftFloatInput" x-model.number="$store.pos.shiftOpenFloat" class="form-control border-0 text-center font-weight-bold" tabindex="60">
                </div>
            </div>
            <div class="modal-footer bg-light border-0 px-4 pb-4">
                <button type="button" class="btn btn-block btn-success btn-lg font-weight-bold rounded-xl touch-target-modal" style="min-height:48px" @click="$store.pos.openShift()">Start shift</button>
            </div>
        </div>
    </div>
</div>
