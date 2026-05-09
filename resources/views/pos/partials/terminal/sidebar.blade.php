<aside class="pos-card h-full p-3 pos-scroll overflow-y-auto pos-sidebar-register">
    <div class="mb-3">
        <h3 class="pos-sidebar-heading">Register</h3>
        @if($activeShift)
            <button class="pos-sidebar-btn pos-sidebar-btn--danger" data-toggle="modal" data-target="#closeShiftModal">
                <i class="fe fe-lock mr-2"></i> Close register
            </button>
        @else
            <button class="pos-sidebar-btn pos-sidebar-btn--success" data-toggle="modal" data-target="#openShiftModal">
                <i class="fe fe-unlock mr-2"></i> Open register
            </button>
        @endif
    </div>

    <div class="mb-2">
        <div class="flex items-center justify-between mb-1">
            <h3 class="pos-sidebar-heading m-0">Customer</h3>
            <button type="button" class="pos-sidebar-link" data-toggle="modal" data-target="#quickCustomerModal">
                <i class="fe fe-user-plus mr-1"></i>Add
            </button>
        </div>
        <select class="pos-sidebar-select pos-sidebar-select--compact" x-model="$store.pos.customerId">
            <option value="">Walk-in Customer</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
            @endforeach
            <template x-for="c in $store.pos.newCustomers" :key="c.id">
                <option :value="c.id" x-text="c.name" :selected="$store.pos.customerId == c.id"></option>
            </template>
        </select>
    </div>

    <p class="pos-sidebar-hint pos-sidebar-hint--compact text-slate-500 m-0" title="Shortcuts: command bar · F6 Held orders">
        <span class="d-none d-xl-inline">Shortcuts in the command bar · </span><strong>Held</strong> recalls parked orders.
    </p>
</aside>
