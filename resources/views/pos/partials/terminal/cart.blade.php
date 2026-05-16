<aside id="pos-cart-pane" class="pos-card h-full flex flex-col min-h-0 overflow-hidden pos-cart-pane pos-cart-pane--slice2 pos-cart-pane--retail @if(!empty($posAppShell)) pos-cart-pane--app @endif">
    <div class="pos-cart-header d-flex align-items-center justify-content-between flex-shrink-0">
        <h3 class="pos-cart-header-title">Current order</h3>
        <div class="pos-cart-header-end">
            <span class="pos-cart-header-badge"
                x-text="$store.pos.cart.length + ' lines'"></span>
            <button type="button" class="pos-app-icon-btn pos-cart-sheet-close" data-pos-cart-close aria-label="Close cart">
                <i class="fe fe-x"></i>
            </button>
        </div>
    </div>

    <div class="flex-1 overflow-y-scroll pos-scroll min-h-0" id="cart-scroll-area">
        <template x-if="$store.pos.cart.length === 0">
            <div class="pos-cart-empty">
                <div class="pos-cart-empty-inner">
                    <div class="pos-cart-empty-icon" aria-hidden="true">
                        <i class="fe fe-shopping-bag"></i>
                    </div>
                    <p class="pos-cart-empty-title">No order yet</p>
                    <p class="pos-cart-empty-hint">Tap a product in the grid to add — or scan a barcode.</p>
                    <p class="pos-cart-empty-keys">Shortcuts: arrows · Enter · +/- quantity</p>
                </div>
            </div>
        </template>

        <template x-for="(item, idx) in $store.pos.cart" :key="idx">
            <div class="pos-cart-line-wrap">
            <div
                class="pos-cart-row cart-line-enter"
                role="button"
                tabindex="-1"
                :data-cart-idx="idx"
                :aria-expanded="idx === $store.pos.cartExpandedIndex ? 'true' : 'false'"
                :aria-selected="idx === $store.pos.cartFocusedIndex ? 'true' : 'false'"
                :class="{
                    'pos-cart-row-selected': idx === $store.pos.cartFocusedIndex,
                    'pos-cart-row-expanded': idx === $store.pos.cartExpandedIndex,
                    'pos-cart-row-pulse': idx === $store.pos._cartPulseIndex
                }"
                @click="$store.pos.toggleCartLineExpand(idx)">
                <div class="pos-cart-line-compact">
                    <div class="pos-cart-line-text min-w-0 flex-1">
                        <h4 class="pos-cart-line-title product-title-lines" x-text="item.name"></h4>
                        <p class="pos-cart-line-meta mb-0 pos-tabular">
                            <span x-text="'@ ' + $store.pos.moneyLabel(item.price)"></span>
                            <span class="pos-cart-line-qty-pill" x-text="'× ' + item.qty"></span>
                            <span class="pos-cart-line-disc-tag"
                                x-show="$store.pos.lineDiscountGross(item) > 0"
                                x-text="$store.pos.discountLabel($store.pos.lineDiscountGross(item))"></span>
                        </p>
                    </div>
                    <div class="pos-cart-line-compact-end">
                        <span class="pos-cart-line-total pos-tabular"
                            x-text="$store.pos.moneyLabel($store.pos.itemTotal(item))"></span>
                        <button type="button"
                            class="pos-cart-line-expand-btn"
                            tabindex="-1"
                            :aria-label="idx === $store.pos.cartExpandedIndex ? 'Collapse line' : 'Edit quantity and discount'"
                            @click.stop="$store.pos.toggleCartLineExpand(idx)">
                            <i class="fe" :class="idx === $store.pos.cartExpandedIndex ? 'fe-chevron-up' : 'fe-chevron-down'"></i>
                        </button>
                        <button type="button"
                            class="pos-cart-line-remove pos-icon-btn-rounded"
                            tabindex="-1"
                            @click.stop="$store.pos.removeItem(idx)"
                            aria-label="Remove">
                            <i class="fe fe-trash-2"></i>
                        </button>
                    </div>
                </div>

                <div class="pos-cart-line-details"
                    x-show="idx === $store.pos.cartExpandedIndex"
                    x-cloak
                    @click.stop>
                    <div class="pos-cart-line-actions">
                        <div class="pos-cart-qty-cluster qty-cluster">
                            <button type="button" class="qty-btn touch-mini" tabindex="-1" @click.stop="$store.pos.updateQty(idx, -1)"><i class="fe fe-minus"></i></button>
                            <input type="number"
                                class="qty-input-flat text-center"
                                x-model.number="item.qty"
                                @change="$store.pos.validateQty(idx)"
                                min="1"
                                tabindex="-1"
                                :aria-label="'Quantity ' + item.name">
                            <button type="button" class="qty-btn touch-mini" tabindex="-1" @click.stop="$store.pos.updateQty(idx, 1)"><i class="fe fe-plus"></i></button>
                        </div>
                    </div>
                    <div class="pos-cart-line-discount">
                        <span class="pos-cart-line-discount-label">Line discount</span>
                        <div class="pos-disc-mode-toggle">
                            <button type="button" class="pos-disc-mode-btn"
                                :class="{ active: (item.discount_mode || 'pct') === 'pct' }"
                                @click.stop="$store.pos.setLineDiscountMode(idx, 'pct')">%</button>
                            <button type="button" class="pos-disc-mode-btn"
                                :class="{ active: item.discount_mode === 'amount' }"
                                @click.stop="$store.pos.setLineDiscountMode(idx, 'amount')">$</button>
                        </div>
                        <template x-if="(item.discount_mode || 'pct') === 'pct'">
                            <input type="number" x-model.number="item.discount_pct" min="0" max="100" step="0.5" tabindex="-1" aria-label="Line discount percent">
                        </template>
                        <template x-if="item.discount_mode === 'amount'">
                            <input type="number" x-model.number="item.discount_amount" min="0" step="0.01" tabindex="-1" aria-label="Line discount amount">
                        </template>
                    </div>
                </div>
            </div>
            <div
                x-show="idx < $store.pos.cart.length - 1"
                class="pos-cart-item-split"
                aria-hidden="true"></div>
            </div>
        </template>
    </div>

    <div id="cart-summary-sticky" class="pos-cart-summary-panel pos-cart-sticky-summary flex-shrink-0">
        <div class="pos-cart-order-discount">
            <div class="pos-cart-order-discount-text">
                <p class="pos-cart-order-discount-label mb-0">Order discount</p>
                <span class="pos-cart-order-discount-hint"
                    x-show="($store.pos.orderDiscountMode || 'amount') === 'amount'">Flat amount off order</span>
                <span class="pos-cart-order-discount-hint"
                    x-show="$store.pos.orderDiscountMode === 'pct'">Percent off order subtotal</span>
            </div>
            <div class="pos-cart-order-discount-controls">
                <div class="pos-disc-mode-toggle pos-disc-mode-toggle--order">
                    <button type="button" class="pos-disc-mode-btn"
                        :class="{ active: ($store.pos.orderDiscountMode || 'amount') === 'pct' }"
                        @click="$store.pos.setOrderDiscountMode('pct')">%</button>
                    <button type="button" class="pos-disc-mode-btn"
                        :class="{ active: $store.pos.orderDiscountMode === 'amount' }"
                        @click="$store.pos.setOrderDiscountMode('amount')">$</button>
                </div>
                <div class="pos-cart-order-discount-input-wrap">
                    <span class="pos-cart-order-discount-prefix pos-tabular"
                        x-show="($store.pos.orderDiscountMode || 'amount') === 'amount'"
                        x-text="$store.pos.currencySymbol"></span>
                    <span class="pos-cart-order-discount-prefix"
                        x-show="$store.pos.orderDiscountMode === 'pct'">%</span>
                    <input type="number"
                        x-model.number="$store.pos.orderDiscount"
                        class="pos-cart-order-discount-input touch-mini"
                        min="0"
                        :max="$store.pos.orderDiscountMode === 'pct' ? 100 : null"
                        :step="$store.pos.orderDiscountMode === 'pct' ? '0.5' : '0.01'"
                        inputmode="decimal"
                        :placeholder="$store.pos.orderDiscountMode === 'pct' ? '0' : '0.00'"
                        :aria-label="$store.pos.orderDiscountMode === 'pct' ? 'Order discount percent' : 'Order discount amount'">
                </div>
            </div>
        </div>

        <div class="pos-cart-totals pos-total-stack">
            <div class="pos-cart-totals-row"><span>Subtotal</span><span class="pos-tabular fw-semibold" x-text="$store.pos.moneyLabel($store.pos.subtotal)"></span></div>
            <div class="pos-cart-totals-row text-danger fw-bold pos-tabular" x-show="$store.pos.totalDiscount > 0"><span>Discount</span><span x-text="$store.pos.discountLabel($store.pos.totalDiscount)"></span></div>
            <div class="pos-cart-totals-row"><span>Tax (<span x-text="$store.pos.taxRateDisplay"></span>%)</span><span class="pos-tabular fw-semibold" x-text="$store.pos.moneyLabel($store.pos.tax)"></span></div>
            <div class="pos-cart-totals-row pos-cart-totals-row--due">
                <span class="pos-cart-due-label">Due</span>
                <span class="pos-cart-due-amount pos-tabular"
                    x-text="$store.pos.moneyLabel($store.pos.total)"></span>
            </div>
        </div>

        <button type="button"
            id="cart-pay-cta"
            class="pos-cart-cta pos-pay-cta"
            data-toggle="modal" data-target="#checkoutModal"
            :disabled="$store.pos.cart.length === 0 || !$store.pos.shiftId">
            Checkout <span class="pos-tabular" x-text="$store.pos.moneyLabel($store.pos.total)"></span>
            <small class="pos-cart-cta-sub">F4 quick checkout</small>
        </button>
        <div class="pos-cart-footer-hints">
            <span><kbd>+/−</kbd> qty</span>
            <span><kbd>Del</kbd> line</span>
            <span><kbd>Arrows</kbd> grid</span>
        </div>
    </div>
</aside>
