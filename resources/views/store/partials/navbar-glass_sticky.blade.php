<header class="store-header py-3">
    <nav class="navbar navbar-expand-lg navbar-light container">
        <a class="navbar-brand" href="{{ route('store.index') }}">Senso<span>Store</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                @include('store.partials.lang-switcher')
                <li class="nav-item mx-2"><a class="nav-link" href="{{ route('store.index') }}">Shop</a></li>

                @auth('customer')
                    <li class="nav-item mx-2 dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                            Hi, {{ Auth::guard('customer')->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2">
                            <li><a class="dropdown-item p-2 rounded" href="{{ route('store.account.dashboard') }}"><i class="fa fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item p-2 rounded text-danger" href="#" onclick="event.preventDefault(); document.getElementById('logout-customer').submit();"><i class="fa fa-sign-out-alt me-2"></i>Sign Out</a></li>
                        </ul>
                        <form id="logout-customer" action="{{ route('store.logout') }}" method="POST" class="d-none">@csrf</form>
                    </li>
                @else
                    <li class="nav-item mx-2"><a class="nav-link" href="{{ route('store.login') }}">Login</a></li>
                @endauth

                <li class="nav-item ms-3">
                    <a href="{{ route('store.cart.index') }}" class="btn position-relative">
                        <i class="fa fa-shopping-cart fa-lg"></i>
                        <template x-if="cartCount > 0">
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" x-text="cartCount"></span>
                        </template>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</header>
