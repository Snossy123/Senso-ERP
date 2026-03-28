<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Senso') }} Store - Premium Shopping</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Alpine JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #4f46e5;
            --accent-color: #f43f5e;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --bg-glass: rgba(255, 255, 255, 0.8);
        }
        body { font-family: 'Outfit', sans-serif; color: var(--text-main); background: #f8fafc; }
        .store-header { 
            background: var(--bg-glass); 
            backdrop-filter: blur(10px); 
            position: sticky; top: 0; z-index: 1000; 
            border-bottom: 1px solid rgba(0,0,0,0.05); 
        }
        .navbar-brand { font-weight: 700; font-size: 1.5rem; color: var(--primary-color) !important; }
        .nav-link { font-weight: 500; color: var(--text-main); }
        .hero-section { 
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); 
            padding: 80px 0; color: white; border-bottom-left-radius: 50px; border-bottom-right-radius: 50px;
        }
        .btn-premium { 
            background: var(--primary-color); border: none; color: white; padding: 12px 25px; border-radius: 12px;
            font-weight: 600; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3); transition: all 0.2s;
        }
        .btn-premium:hover { background: var(--secondary-color); transform: translateY(-2px); }
        .product-card { 
            background: white; border-radius: 20px; border: none; overflow: hidden; 
            transition: all 0.3s; box-shadow: 0 4px 20px rgba(0,0,0,0.04); height: 100%;
        }
        .product-card:hover { transform: translateY(-10px); box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .badge-sale { background: var(--accent-color); position: absolute; top: 15px; left: 15px; }
        footer { background: #0f172a; color: #94a3b8; padding: 60px 0; margin-top: 80px; }
        footer h5 { color: white; margin-bottom: 25px; }
    </style>
    @yield('css')
</head>
<body x-data="{ cartCount: {{ count(session('cart', [])) }} }">
    <header class="store-header py-3">
        <nav class="navbar navbar-expand-lg navbar-light container">
            <a class="navbar-brand" href="{{ route('store.index') }}">Senso<span>Store</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
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

    @yield('hero')

    <main class="container py-5">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-pill px-4" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @yield('content')
    </main>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="navbar-brand text-white">Senso<span>Store</span></h5>
                    <p>Modern ERP-integrated ecommerce solution for small and medium businesses. Premium quality, guaranteed satisfaction.</p>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none text-muted">Shop All</a></li>
                        <li><a href="#" class="text-decoration-none text-muted">Latest Arrivals</a></li>
                        <li><a href="#" class="text-decoration-none text-muted">Special Offers</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none text-muted">Shipping Policy</a></li>
                        <li><a href="#" class="text-decoration-none text-muted">Returns</a></li>
                        <li><a href="#" class="text-decoration-none text-muted">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Stay Connected</h5>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control bg-dark border-0 text-white" placeholder="Email Address">
                        <button class="btn btn-primary" type="button">Join</button>
                    </div>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-muted fs-4"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-muted fs-4"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-muted fs-4"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <hr class="border-secondary my-4">
            <div class="text-center">
                <p class="mb-0">© 2026 Senso Systems. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('js')
</body>
</html>
