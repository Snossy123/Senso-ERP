<header class="py-3">
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark rounded-4 px-3" style="background: #0f172a;">
            <a class="navbar-brand text-white" href="{{ route('store.index') }}">Senso<span class="text-primary">Store</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDark">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavDark">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item mx-2"><a class="nav-link text-white-50" href="{{ route('store.index') }}">Shop</a></li>

                    @auth('customer')
                        <li class="nav-item mx-2 dropdown">
                            <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                                {{ Auth::guard('customer')->user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2">
                                <li><a class="dropdown-item p-2 rounded" href="{{ route('store.account.dashboard') }}">Dashboard</a></li>
                                <li><a class="dropdown-item p-2 rounded text-danger" href="#" onclick="event.preventDefault(); document.getElementById('logout-customer-dark').submit();">Sign Out</a></li>
                            </ul>
                            <form id="logout-customer-dark" action="{{ route('store.logout') }}" method="POST" class="d-none">@csrf</form>
                        </li>
                    @else
                        <li class="nav-item mx-2"><a class="nav-link text-white-50" href="{{ route('store.login') }}">Login</a></li>
                    @endauth

                    <li class="nav-item ms-3">
                        <a href="{{ route('store.cart.index') }}" class="btn btn-primary position-relative">
                            <i class="fa fa-shopping-cart"></i>
                            <template x-if="cartCount > 0">
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-light text-dark" x-text="cartCount"></span>
                            </template>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</header>
