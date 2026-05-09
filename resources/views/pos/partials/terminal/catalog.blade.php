<section class="pos-card pos-catalog-card h-full flex flex-col @if(!empty($posAppShell)) pos-catalog-card--app @endif">
    @unless(!empty($posAppShell))
    <div class="pos-catalog-search px-3 py-2 border-b border-slate-100">
        <div class="relative">
            <i class="fe fe-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 tx-15"></i>
            <input
                id="pos-search"
                type="text"
                autocomplete="off"
                autocapitalize="none"
                x-model="$store.pos.searchQuery"
                @input.debounce.300ms="$store.pos.onSearch()"
                @keydown.enter.prevent="$store.pos.barcodeSearch()"
                class="w-full pos-catalog-search-input border border-slate-200 bg-white py-2 pl-10 pr-3 outline-none transition duration-150 pos-search-ring"
                placeholder="Scan barcode or search · F2"
            >
        </div>
    </div>

    <div class="px-3 py-2 border-b border-slate-100 overflow-x-auto pos-scroll pos-catalog-cats">
        <div class="flex items-center gap-2">
            <button class="cat-chip" :class="{ 'active': $store.pos.selectedCategory === 'all' }" @click="$store.pos.setCategory('all')">All</button>
            @foreach($categories as $category)
                <button class="cat-chip" :class="{ 'active': $store.pos.selectedCategory == {{ $category->id }} }" @click="$store.pos.setCategory({{ $category->id }})">{{ $category->name }}</button>
            @endforeach
        </div>
    </div>
    @endunless

    <div class="flex-1 overflow-y-auto pos-scroll pos-catalog-grid-wrap p-3 @if(!empty($posAppShell)) pos-catalog-grid-wrap--app @endif" id="product-scroll-area" @scroll.passive="$store.pos.onCatalogScroll($event)">
        @if(!$activeShift)
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-700">
                Register is closed. Open a shift to start selling.
            </div>
        @endif

        <template x-if="$store.pos.loadingProducts && !$store.pos.filteredProducts.length">
            <div class="product-skeleton-grid pos-mb-gap" aria-busy="true">
                @for ($s = 0; $s < 8; $s++)
                    <div class="pos-skel-card">
                        <div class="pos-skel-shimmer pos-skel-img"></div>
                        <div class="p-3">
                            <div class="pos-skel-line pos-skel-shimmer pos-skel-w25 rounded mb-2"></div>
                            <div class="pos-skel-line pos-skel-shimmer pos-skel-w100 rounded mb-2"></div>
                            <div class="pos-skel-line pos-skel-shimmer pos-skel-w60 rounded"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </template>

        <div id="pos-product-grid" class="product-grid-rows pos-product-grid-gap" data-pos-catalog-root="1">
            <template x-for="(product, pIdx) in $store.pos.filteredProducts" :key="product.id">
                <article
                    class="product-card relative"
                    :data-pos-prod-idx="pIdx"
                    :class="{
                        'out-stock': product.out_of_stock,
                        'product-card-keyboard': pIdx === $store.pos.keyboardProductIndex,
                        'product-card-scan': product.id === $store.pos.recentFlashProductId,
                        'product-card-lowpulse': product.low_stock && !product.out_of_stock,
                        'product-card-stale': $store.pos.catalogStale
                    }"
                    @click.prevent="!product.out_of_stock && $store.pos.tapCatalogProduct(pIdx, product)"
                    role="button"
                    tabindex="-1"
                    :aria-current="pIdx === $store.pos.keyboardProductIndex ? 'true' : 'false'"
                >
                    <button type="button"
                        class="pos-quick-add-float"
                        :disabled="product.out_of_stock"
                        tabindex="-1"
                        @click.stop="!product.out_of_stock && $store.pos.tapCatalogProduct(pIdx, product)"
                        aria-label="Add to cart">
                        <i class="fe fe-plus"></i>
                    </button>
                    <div class="product-img-frame relative bg-slate-100 overflow-hidden">
                        <img loading="lazy"
                            decoding="async"
                            :src="product.image || 'https://via.placeholder.com/320x200'"
                            :alt="product.name"
                            class="product-img-zoom h-full w-full object-cover block">
                        <span x-show="product.badge === 'low'" class="absolute left-2 top-2 rounded-lg bg-amber-500 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-white shadow">Low stock</span>
                        <span x-show="product.badge === 'out'" class="absolute left-2 top-2 rounded-lg bg-rose-600 px-2 py-1 text-[10px] font-bold uppercase text-white shadow">Sold out</span>
                    </div>
                    <div class="relative bg-slate-100 pos-stock-meter">
                        <div class="pos-stock-meter-fill h-full rounded-r bg-emerald-500 transition-[width] duration-300"
                             :style="`width:${product.out_of_stock ? '5%' : (product.low_stock ? '38%' : '92%')}%;opacity:${product.out_of_stock ? 0.35 : 1}`"></div>
                    </div>
                    <div class="p-3 pb-4">
                        <p class="mb-1 tx-11 uppercase tracking-wide text-slate-400 fw-semibold" style="font-weight:700;" x-text="product.category || 'General'"></p>
                        <h4 class="text-sm leading-snug font-semibold text-slate-900 product-title-lines text-truncate" style="display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;" x-text="product.name"></h4>
                        <div class="mt-3 flex items-end justify-between gap-2">
                            <p class="text-base font-semibold text-indigo-700 leading-none" x-text="$store.pos.currencySymbol + Number(product.price).toFixed(2)"></p>
                            <p class="text-[11px] font-medium whitespace-nowrap" :class="product.out_of_stock ? 'text-rose-600' : (product.low_stock ? 'text-amber-600' : 'text-emerald-600')" x-text="Math.floor(product.stock) + ' on hand'"></p>
                        </div>
                    </div>
                </article>
            </template>
        </div>

        <div x-show="$store.pos.loadingProducts && $store.pos.filteredProducts.length" class="py-5 text-center text-sm text-slate-400"><span class="pos-inline-shimmer-inline">Loading more…</span></div>
        <div x-show="!$store.pos.loadingProducts && !$store.pos.filteredProducts.length" class="py-10 text-center text-slate-500">No products found.</div>
    </div>
</section>
