<aside id="pos-cart-pane" class="pos-card h-full flex flex-col pos-cart-pane @if(!empty($posAppShell)) pos-cart-pane--app @endif">
    <div class="p-3 border-b border-slate-100 d-flex align-items-center justify-content-between flex-shrink-0" style="flex-shrink:0;">
        <h3 class="text-base font-semibold text-slate-900 m-0">Current order</h3>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 inline-flex items-center gap-2" style="min-height:32px;line-height:1.2;"
            x-text="$store.pos.cart.length + ' lines'"></span>
    </div>

    <div class="flex-1 overflow-y-auto pos-scroll min-h-0" id="cart-scroll-area">
        <template x-if="$store.pos.cart.length === 0">
            <div class="p-10 text-center text-slate-400">
                <i class="fe fe-shopping-bag mb-3 block mx-auto opacity-30" style="font-size:2.75rem;"></i>
                <p class="mb-2 font-medium text-slate-500">Basket is empty</p>
                <p class="text-xs text-slate-400 mb-0">Use arrows · Enter adds · +/- adjusts selected row</p>
            </div>
        </template>

        <template x-for="(item, idx) in $store.pos.cart" :key="idx">
            <div
                class="pos-cart-row cart-line-enter border-b border-slate-100 p-4"
                role="button"
                tabindex="-1"
                :data-cart-idx="idx"
                :aria-selected="idx === $store.pos.cartFocusedIndex ? 'true' : 'false'"
                :class="{
                    'pos-cart-row-selected': idx === $store.pos.cartFocusedIndex,
                    'pos-cart-row-pulse': idx === $store.pos._cartPulseIndex
                }"
                @click="$store.pos.cartFocusedIndex = idx">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <h4 class="text-sm font-semibold text-slate-900 m-0 product-title-lines" style="display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;" x-text="item.name"></h4>
                        <p class="text-xs text-slate-400 mt-1 mb-0" x-text="'@ ' + $store.pos.currencySymbol + Number(item.price).toFixed(2)"></p>
                    </div>
                    <button type="button"
                        class="pos-icon-btn-rounded text-rose-500 flex-shrink-0"
                        style="flex-shrink:0;"
                        style="min-width:44px;min-height:44px;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;"
                        tabindex="-1"
                        @click.stop="$store.pos.removeItem(idx)"
                        aria-label="Remove">
                        <i class="fe fe-trash-2"></i>
                    </button>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-3 justify-between">
                    <div class="flex items-center gap-2 qty-cluster">
                        <button type="button" class="qty-btn touch-mini" tabindex="-1" @click.stop="$store.pos.updateQty(idx, -1)"><i class="fe fe-minus"></i></button>
                        <input type="number"
                            class="qty-input-flat text-center"
                            x-model.number="item.qty"
                            @change="$store.pos.validateQty(idx)"
                            min="1"
                            style="width:54px;font-weight:700;"
                            tabindex="-1"
                            :aria-label="'Quantity ' + item.name">
                        <button type="button" class="qty-btn touch-mini" tabindex="-1" @click.stop="$store.pos.updateQty(idx, 1)"><i class="fe fe-plus"></i></button>
                    </div>
                    <div class="text-sm font-semibold text-indigo-700" x-text="$store.pos.currencySymbol + $store.pos.itemTotal(item).toFixed(2)"></div>
                </div>
                <div class="mt-3 flex items-center gap-2 flex-wrap">
                    <label class="tx-11 font-semibold text-slate-500 mb-0">Line disc%</label>
                    <input type="number"
                        x-model.number="item.discount_pct"
                        min="0"
                        max="100"
                        step="0.5"
                        class="rounded-lg border border-slate-200 px-2 py-1 tx-13"
                        style="width:70px;"
                        tabindex="-1">
                    <span class="tx-11 text-danger" x-show="item.discount_pct > 0"
                        x-text="'− ' + $store.pos.currencySymbol + (Number(item.price) * Number(item.qty) * Number(item.discount_pct||0) / 100).toFixed(2)"></span>
                </div>
            </div>
        </template>
    </div>

    <div id="cart-summary-sticky" class="border-top border-slate-100 bg-white p-4 pos-cart-sticky-summary shadow-soft-up flex-shrink-0" style="flex-shrink:0;">
        <div class="mb-3 flex items-center justify-between rounded-xl bg-slate-50 px-4 py-2 border border-slate-100 gap-3">
            <span class="text-xs fw-semibold text-slate-500 text-uppercase" style="font-weight:700;">Order discount</span>
            <div class="d-flex align-items-center gap-1">
                <span class="small font-weight-bold text-muted" x-text="$store.pos.currencySymbol"></span>
                <input type="number" x-model.number="$store.pos.orderDiscount" class="rounded-lg border border-slate-200 px-2 py-2 text-right touch-mini" style="width:104px;font-weight:700;"
                    aria-label="Order discount amount">
            </div>
        </div>

        <div class="space-y-2 text-sm pos-total-stack">
            <div class="d-flex justify-content-between text-muted"><span>Subtotal</span><span class="fw-semibold" style="font-weight:700;" x-text="$store.pos.currencySymbol + $store.pos.subtotal.toFixed(2)"></span></div>
            <div class="d-flex justify-content-between text-danger" style="font-weight:700;" x-show="$store.pos.totalDiscount > 0"><span>Discount</span><span x-text="'− ' + $store.pos.currencySymbol + $store.pos.totalDiscount.toFixed(2)"></span></div>
            <div class="d-flex justify-content-between text-muted"><span>Tax (<span x-text="$store.pos.taxRate"></span>%)</span><span style="font-weight:700;" x-text="$store.pos.currencySymbol + $store.pos.tax.toFixed(2)"></span></div>
            <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-2">
                <span class="fw-bold tx-17 text-dark">Due</span>
                <span class="fw-bolder tx-22" style="letter-spacing:-.02em;color:#059669;"
                    x-text="$store.pos.currencySymbol + $store.pos.total.toFixed(2)"></span>
            </div>
        </div>

        <button type="button"
            id="cart-pay-cta"
            class="btn btn-emerald-soft w-100 fw-bold rounded-xl mt-3 touch-target-modal pos-pay-cta"
            style="min-height:54px;background:linear-gradient(135deg,#059669,#047857);color:#fff;border:none;box-shadow:0 14px 30px rgba(5,150,105,0.25);"
            data-toggle="modal" data-target="#checkoutModal"
            :disabled="$store.pos.cart.length === 0 || !$store.pos.shiftId">
            Checkout <span x-text="$store.pos.currencySymbol + $store.pos.total.toFixed(2)"></span>
            <small class="d-block tx-11 opacity-80 fw-normal mt-1" style="line-height:1;">F4 quick checkout</small>
        </button>
        <div class="d-flex justify-content-center flex-wrap gap-2 mt-3 text-muted tx-11">
            <span><kbd class="rounded border px-1 bg-light tx-11">+/−</kbd> qty</span>
            <span><kbd class="rounded border px-1 bg-light tx-11">Del</kbd> line</span>
            <span><kbd class="rounded border px-1 bg-light tx-11">Arrows</kbd> grid</span>
        </div>
    </div>
</aside>
