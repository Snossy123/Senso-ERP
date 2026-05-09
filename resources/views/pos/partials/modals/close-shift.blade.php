<!-- Close Shift Modal -->
<div class="modal fade" id="closeShiftModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg rounded-xl overflow-hidden">
            <div class="modal-header bg-danger text-white border-0"><h5 class="modal-title mb-0">Close register</h5></div>
            <div class="modal-body p-4">
                <label class="font-weight-bold">Counted cash</label>
                <div class="input-group input-group-lg mb-3 shadow-sm rounded-lg overflow-hidden border">
                    <div class="input-group-prepend"><span class="input-group-text font-weight-bold border-0" x-text="$store.pos.currencySymbol"></span></div>
                    <input type="number" id="closeShiftFloatInput" x-model.number="$store.pos.shiftCloseFloat" class="form-control border-0 font-weight-bold tx-20 text-center" tabindex="70">
                </div>
                <label class="font-weight-bold">Closing notes</label>
                <textarea x-model="$store.pos.shiftNotes" class="form-control rounded-lg border" rows="2" placeholder="Optional notes…" tabindex="71"></textarea>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-lg" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger px-4 rounded-lg font-weight-bold touch-target-modal" style="min-height:44px" @click="$store.pos.closeShift()">Close shift</button>
            </div>
        </div>
    </div>
</div>
