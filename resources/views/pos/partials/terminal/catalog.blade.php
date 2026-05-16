<section class="pos-card pos-catalog-card h-full flex flex-col min-h-0 @if(!empty($posAppShell)) pos-catalog-card--app @endif">
    @unless(!empty($posAppShell))
    <div class="pos-catalog-search px-3 py-2 border-b border-slate-100 flex-shrink-0">
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

    <div class="px-3 py-2 border-b border-slate-100 overflow-x-auto pos-scroll pos-catalog-cats flex-shrink-0">
        <div class="flex items-center gap-2">
            <button class="cat-chip" :class="{ 'active': $store.pos.selectedCategory === 'all' }" @click="$store.pos.setCategory('all')">All</button>
            @foreach($categories as $category)
                <button class="cat-chip" :class="{ 'active': $store.pos.selectedCategory == {{ $category->id }} }" @click="$store.pos.setCategory({{ $category->id }})">{{ $category->name }}</button>
            @endforeach
        </div>
    </div>
    @endunless

    <div class="flex-1 flex flex-col min-h-0 overflow-hidden pos-catalog-viewport" id="product-scroll-area">
        @if(!$activeShift)
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-700 flex-shrink-0 mx-3 mt-3">
                Register is closed. Open a shift to start selling.
            </div>
        @endif

        <div class="flex-1 flex flex-col min-h-0 overflow-hidden pos-catalog-grid-view">
            <div class="pos-catalog-grid-scroller flex-1 min-h-0 overflow-y-auto pos-scroll pos-catalog-grid-wrap p-3 @if(!empty($posAppShell)) pos-catalog-grid-wrap--app @endif">
                <template x-if="$store.pos.loadingProducts && !$store.pos.filteredProducts.length">
                    <div class="pos-catalog-skel-grid" aria-busy="true">
                        @for ($s = 0; $s < 12; $s++)
                            <div class="pos-catalog-card-modern pos-catalog-card-modern--skel">
                                <div class="pos-skel-shimmer pos-catalog-card-modern-media-skel" aria-hidden="true"></div>
                                <div class="pos-catalog-card-modern-body-skel">
                                    <div class="pos-skel-line pos-skel-shimmer rounded mb-2"></div>
                                    <div class="pos-skel-line pos-skel-shimmer pos-skel-w40 rounded"></div>
                                </div>
                            </div>
                        @endfor
                    </div>
                </template>

                <div id="pos-product-grid" class="product-grid-rows pos-product-grid-gap pos-catalog-product-grid pos-catalog-product-grid--retail" data-pos-catalog-root="1">
                    <template x-for="(product, pIdx) in $store.pos.catalogPagedProducts" :key="product.id + '-' + $store.pos.catalogUiPage">
                        <article
                            class="pos-catalog-card-modern"
                            :data-pos-prod-idx="pIdx"
                            :class="{
                                'pos-catalog-card-modern--out': product.out_of_stock,
                                'pos-catalog-card-modern--keyboard': pIdx === $store.pos.keyboardProductIndex,
                                'pos-catalog-card-modern--scan': product.id === $store.pos.recentFlashProductId,
                            }"
                            @click.prevent="!product.out_of_stock && $store.pos.openCatalogProductDetail(pIdx, product)"
                            role="button"
                            tabindex="-1"
                            :aria-current="pIdx === $store.pos.keyboardProductIndex ? 'true' : 'false'"
                        >
                            <div class="pos-catalog-card-modern-media">
                                <template x-if="product.image">
                                    <img loading="lazy" decoding="async" :src="product.image" :alt="product.name"
                                        class="pos-catalog-card-modern-img">
                                </template>
                                <div x-show="!product.image" class="pos-catalog-card-modern-ph" x-cloak
                                    x-text="$store.pos.initialsFromName(product.name)"></div>
                            </div>
                            <div class="pos-catalog-card-modern-body">
                                <h4 class="pos-catalog-card-modern-name" x-text="product.name"></h4>
                                <p class="pos-catalog-card-modern-price pos-tabular" x-text="$store.pos.moneyLabel(product.price)"></p>
                            </div>
                        </article>
                    </template>
                </div>

                <div x-show="$store.pos.loadingProducts && $store.pos.filteredProducts.length" class="py-5 text-center text-sm text-slate-400"><span class="pos-inline-shimmer-inline">Loading more…</span></div>
                <div x-show="!$store.pos.loadingProducts && !$store.pos.filteredProducts.length" class="py-10 text-center text-slate-500">No products found.</div>
            </div>

            <div class="pos-catalog-pagination flex-shrink-0"
                x-show="$store.pos.filteredProducts.length > 0"
                x-cloak>
                <div class="pos-catalog-pagination-inner">
                    <button type="button" class="pos-catalog-page-btn"
                        @click="$store.pos.catalogPrevPage()"
                        :disabled="$store.pos.catalogUiPage <= 1">
                        <i class="fe fe-chevron-left" aria-hidden="true"></i><span class="sr-only">Previous page</span>
                    </button>
                    <div class="pos-catalog-page-indicator pos-tabular">
                        <span class="pos-catalog-page-label">Page</span>
                        <span class="pos-catalog-page-current" x-text="$store.pos.catalogUiPage"></span>
                        <span class="pos-catalog-page-sep">/</span>
                        <span class="pos-catalog-page-total" x-text="$store.pos.catalogTotalLoadedPages"></span>
                        <template x-if="$store.pos.hasMoreProducts">
                            <span class="pos-catalog-page-more">· more available</span>
                        </template>
                    </div>
                    <button type="button" class="pos-catalog-page-btn"
                        @click="$store.pos.catalogNextPage()"
                        :disabled="($store.pos.catalogUiPage >= $store.pos.catalogTotalLoadedPages && !$store.pos.hasMoreProducts) || $store.pos.loadingProducts">
                        <i class="fe fe-chevron-right" aria-hidden="true"></i><span class="sr-only">Next page</span>
                    </button>
                </div>
                <p class="pos-catalog-page-hint mb-0"><span x-text="$store.pos.catalogPageSize"></span> per page · tap product for options · Enter quick-add</p>
            </div>
        </div>
    </div>
</section>
