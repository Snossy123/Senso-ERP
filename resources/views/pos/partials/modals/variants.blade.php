<!-- Variant Selection Modal -->
<div class="modal fade" id="variantModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-xl overflow-hidden">
            <div class="modal-header bg-light border-bottom"><h5 class="modal-title font-weight-bold mb-0">Variants</h5></div>
            <div class="modal-body p-2">
                <div class="list-group list-group-flush border rounded-lg overflow-hidden">
                    <template x-for="v in $store.pos.activeProductVariants" :key="v.id">
                        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 px-3 touch-target-modal" style="min-height:48px"
                            tabindex="72"
                            @click="$store.pos.addVariantToCart(v)">
                            <span x-text="v.name"></span>
                            <span class="badge badge-primary rounded-pill px-3 py-2 pos-tabular" x-text="$store.pos.moneyLabel(v.price)"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
