{{-- Product detail / configurator — catalog drill-in (Slice 3 revised). Uses Bootstrap modal JS only; styling is token-scoped. --}}
<div class="modal fade pos-product-detail-modal" id="productDetailModal" tabindex="-1" aria-labelledby="productDetailModalTitle" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg pos-product-detail-dialog">
        <div class="modal-content pos-product-detail-sheet border-0">
            <div class="modal-header pos-product-detail-head align-items-start border-0 pb-0">
                <div class="min-w-0 flex-grow-1 pe-3">
                    <h4 id="productDetailModalTitle" class="modal-title pos-product-detail-title mb-1"
                        x-text="$store.pos.activeProduct?.name || 'Product'"></h4>
                    <p class="pos-product-detail-meta mb-0"
                        x-show="$store.pos.activeProduct?.category"
                        x-text="$store.pos.activeProduct?.category"></p>
                </div>
                <button type="button" class="close pos-modal-close-fix rounded-xl border-0 bg-transparent" data-dismiss="modal" aria-label="Close"
                    @click="$store.pos.closeProductDetailModal()"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body pos-product-detail-body pt-2">
                <template x-if="$store.pos.activeProduct">
                    <div class="pos-product-detail-layout">
                        <div class="pos-product-detail-visual">
                            <div class="pos-product-detail-img-wrap">
                                <template x-if="$store.pos.activeProduct.image">
                                    <img class="pos-product-detail-img"
                                        :src="$store.pos.activeProduct.image"
                                        :alt="$store.pos.activeProduct.name"
                                        loading="lazy" decoding="async">
                                </template>
                                <div x-show="!$store.pos.activeProduct.image" class="pos-product-detail-ph"
                                    x-text="$store.pos.initialsFromName($store.pos.activeProduct.name)" x-cloak></div>
                            </div>
                            <div class="pos-product-detail-price-block">
                                <span class="pos-product-detail-price-label">Price</span>
                                <p class="pos-product-detail-price pos-tabular mb-0"
                                    x-text="$store.pos.activeProduct.has_variants && $store.pos.activeProduct.variants?.length
                                        ? $store.pos.moneyLabel(($store.pos.activeProduct.variants.find(v => v.id === $store.pos.detailSelectedVariantId) || $store.pos.activeProduct.variants[0]).price)
                                        : $store.pos.moneyLabel($store.pos.activeProduct.price)"></p>
                            </div>
                            <dl class="pos-product-detail-dl mb-0">
                                <div class="pos-product-detail-dl-row">
                                    <dt>Stock</dt>
                                    <dd class="pos-tabular mb-0"
                                        x-text="Math.floor($store.pos.activeProduct.stock) + ' units'"></dd>
                                </div>
                                <div class="pos-product-detail-dl-row" x-show="$store.pos.activeProduct.sku">
                                    <dt>SKU</dt>
                                    <dd class="mb-0 font-monospace small" x-text="$store.pos.activeProduct.sku"></dd>
                                </div>
                            </dl>
                        </div>
                        <div class="pos-product-detail-controls">
                            <template x-if="$store.pos.activeProduct.has_variants && $store.pos.activeProduct.variants?.length">
                                <div class="pos-product-detail-field mb-4">
                                    <label class="pos-product-detail-label" for="detailVariantSelect">Variant</label>
                                    <select id="detailVariantSelect" class="pos-product-detail-select form-control border-0"
                                        x-model.number="$store.pos.detailSelectedVariantId">
                                        <template x-for="v in $store.pos.activeProduct.variants" :key="v.id">
                                            <option :value="v.id" x-text="v.name + ' — ' + $store.pos.moneyLabel(v.price)"></option>
                                        </template>
                                    </select>
                                </div>
                            </template>
                            <div class="pos-product-detail-field mb-4">
                                <label class="pos-product-detail-label" for="detailModalQty">Quantity</label>
                                <div class="pos-product-detail-stepper">
                                    <button type="button" class="pos-product-detail-step" tabindex="71"
                                        @click="$store.pos.detailModalQty = Math.max(1, ($store.pos.detailModalQty || 1) - 1)"
                                        aria-label="Decrease quantity"><i class="fe fe-minus"></i></button>
                                    <input id="detailModalQty" type="number" min="1"
                                        class="pos-product-detail-qty-input pos-tabular text-center border-0"
                                        x-model.number="$store.pos.detailModalQty"
                                        :max="Math.floor($store.pos.activeProduct.stock)">
                                    <button type="button" class="pos-product-detail-step" tabindex="72"
                                        @click="$store.pos.detailModalQty = Math.min(Math.floor($store.pos.activeProduct.stock), ($store.pos.detailModalQty || 1) + 1)"
                                        aria-label="Increase quantity"><i class="fe fe-plus"></i></button>
                                </div>
                            </div>
                            <div class="pos-product-detail-field">
                                <label class="pos-product-detail-label">Line discount</label>
                                <div class="pos-disc-mode-toggle mb-2">
                                    <button type="button" class="pos-disc-mode-btn"
                                        :class="{ active: $store.pos.detailModalDiscountMode !== 'amount' }"
                                        @click="$store.pos.detailModalDiscountMode = 'pct'">%</button>
                                    <button type="button" class="pos-disc-mode-btn"
                                        :class="{ active: $store.pos.detailModalDiscountMode === 'amount' }"
                                        @click="$store.pos.detailModalDiscountMode = 'amount'">$</button>
                                </div>
                                <input id="detailModalDisc" type="number" min="0" max="100" step="0.5"
                                    x-show="$store.pos.detailModalDiscountMode !== 'amount'"
                                    class="pos-product-detail-disc form-control border-0 pos-tabular"
                                    x-model.number="$store.pos.detailModalDiscountPct" placeholder="0">
                                <input type="number" min="0" step="0.01"
                                    x-show="$store.pos.detailModalDiscountMode === 'amount'" x-cloak
                                    class="pos-product-detail-disc form-control border-0 pos-tabular"
                                    x-model.number="$store.pos.detailModalDiscountAmount" placeholder="0.00">
                            </div>
                            <div class="pos-product-detail-field mb-0">
                                <label class="pos-product-detail-label" for="detailModalNotes">Notes</label>
                                <textarea id="detailModalNotes"
                                    class="pos-product-detail-notes form-control border-0"
                                    x-model="$store.pos.detailModalNotes"
                                    placeholder="Add a note (optional)"
                                    rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <div class="modal-footer pos-product-detail-foot flex-column flex-sm-row gap-2 border-0 pt-0">
                <button type="button" class="btn pos-product-detail-btn-cancel flex-grow-1 flex-sm-grow-0 order-2 order-sm-1" data-dismiss="modal"
                    @click="$store.pos.closeProductDetailModal()">Cancel</button>
                <button type="button" class="btn pos-product-detail-btn-add flex-grow-1 order-1 order-sm-2"
                    tabindex="73"
                    data-pos-detail-add
                    @click="$store.pos.confirmProductDetailAdd()"
                    :disabled="!$store.pos.activeProduct || $store.pos.activeProduct.out_of_stock">
                    Add to cart
                </button>
            </div>
        </div>
    </div>
</div>
