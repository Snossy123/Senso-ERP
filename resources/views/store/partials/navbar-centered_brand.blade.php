<header class="py-4 bg-white border-bottom">
    <div class="container text-center">
        <a class="navbar-brand d-inline-flex align-items-center justify-content-center mb-2" href="{{ route('store.index') }}">
            <span class="fs-3 fw-bold">Senso<span class="text-primary">Store</span></span>
        </a>
        <nav class="d-flex flex-wrap justify-content-center gap-3 align-items-center">
            <a class="text-decoration-none text-dark fw-semibold" href="{{ route('store.index') }}">Shop</a>

            @auth('customer')
                <a class="text-decoration-none text-dark fw-semibold" href="{{ route('store.account.dashboard') }}">Account</a>
                <a class="text-decoration-none text-danger fw-semibold" href="#" onclick="event.preventDefault(); document.getElementById('logout-customer-centered').submit();">Logout</a>
                <form id="logout-customer-centered" action="{{ route('store.logout') }}" method="POST" class="d-none">@csrf</form>
            @else
                <a class="text-decoration-none text-dark fw-semibold" href="{{ route('store.login') }}">Login</a>
            @endauth

            <a href="{{ route('store.cart.index') }}" class="btn btn-outline-primary btn-sm position-relative">
                Cart
                <template x-if="cartCount > 0">
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" x-text="cartCount"></span>
                </template>
            </a>
        </nav>
    </div>
</header>
