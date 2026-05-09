{{-- Vertical category rail + compact register actions --}}
<aside class="pos-app-rail pos-app-rail--premium" id="pos-app-category-rail">
    <div class="pos-app-rail-scroll">
        <button type="button"
            class="pos-app-rail-item"
            :class="{ 'is-active': $store.pos.selectedCategory === 'all' }"
            @click="$store.pos.setCategory('all')">
            <span class="pos-app-rail-icon"><i class="fe fe-grid"></i></span>
            <span class="pos-app-rail-label">All</span>
        </button>
        @foreach($categories as $category)
            <button type="button"
                class="pos-app-rail-item"
                :class="{ 'is-active': $store.pos.selectedCategory == {{ $category->id }} }"
                @click="$store.pos.setCategory({{ $category->id }})">
                <span class="pos-app-rail-icon pos-app-rail-icon--letter">{{ strtoupper(substr($category->name, 0, 1)) }}</span>
                <span class="pos-app-rail-label">{{ $category->name }}</span>
            </button>
        @endforeach
    </div>

    <div class="pos-app-rail-tools">
        @if($activeShift)
            <button type="button" class="pos-app-rail-tool pos-app-rail-tool--danger" data-toggle="modal" data-target="#closeShiftModal" title="Close register">
                <i class="fe fe-lock"></i>
            </button>
        @else
            <button type="button" class="pos-app-rail-tool pos-app-rail-tool--success" data-toggle="modal" data-target="#openShiftModal" title="Open register">
                <i class="fe fe-unlock"></i>
            </button>
        @endif
    </div>
</aside>
