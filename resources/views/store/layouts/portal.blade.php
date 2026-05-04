<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $dir ?? 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Senso') }} Store - Premium Shopping</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 CSS -->
    @if(!empty($isRtl))
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @else
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif
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
@php
    $builderSections = collect($storefrontRender['sections'] ?? []);
    $heroSection = $builderSections->firstWhere('section_type', 'hero');
    $heroPayload = $heroSection['payload'] ?? [];
    $ctaSection = $builderSections->firstWhere('section_type', 'cta');
    $ctaPayload = $ctaSection['payload'] ?? [];

    $storefrontModel = $storefrontRender['storefront'] ?? null;
    $pageType = $storefrontRender['page_type'] ?? null;
    $builderSettings = $storefrontRender['builder_settings'] ?? (is_object($storefrontModel) ? ($storefrontModel->settings ?? []) : []);
    $layoutSlots = app(\App\Modules\StorefrontBuilder\Services\LayoutSlotService::class);
    $uomoNavbarKey = $layoutSlots->globalNavbarKey($builderSettings);
    $useHomeVisualSlots = in_array($pageType, ['home', 'shop'], true);
    $uomoHomeHeroKey = $useHomeVisualSlots ? $layoutSlots->pageSlot($builderSettings, 'home', 'hero') : null;
    $uomoHomeFooterKey = $useHomeVisualSlots ? $layoutSlots->pageSlot($builderSettings, 'home', 'footer') : null;

    $uomoNavbarMeta = $uomoNavbarKey ? app(\App\Modules\StorefrontBuilder\Services\TemplateRegistryService::class)->findTemplate($uomoNavbarKey) : null;
    $uomoNavbarUrl = $uomoNavbarMeta ? url($uomoNavbarMeta['preview']) : '';

    $uomoHeroMeta = $uomoHomeHeroKey ? app(\App\Modules\StorefrontBuilder\Services\TemplateRegistryService::class)->findTemplate($uomoHomeHeroKey) : null;
    $uomoHeroUrl = $uomoHeroMeta ? url($uomoHeroMeta['preview']) : '';

    $uomoFooterMeta = $uomoHomeFooterKey ? app(\App\Modules\StorefrontBuilder\Services\TemplateRegistryService::class)->findTemplate($uomoHomeFooterKey) : null;
    $uomoFooterUrl = $uomoFooterMeta ? url($uomoFooterMeta['preview']) : '';

    $uomoNavbarFragmentHtml = $uomoNavbarKey
        ? app(\App\Modules\StorefrontBuilder\Services\UomoFragmentService::class)->navbarHtml($uomoNavbarKey)
        : null;

    $settings = is_array($builderSettings) ? $builderSettings : [];
    $navbarVariant = (string) data_get($settings, 'navbar_variant', 'glass_sticky');
    $heroVariant = (string) data_get($settings, 'hero_variant', 'gradient_split');

    $navbarPartial = 'store.partials.navbar-' . $navbarVariant;
    $heroPartial = 'store.partials.hero-' . $heroVariant;
    if (!view()->exists($navbarPartial)) {
        $navbarPartial = 'store.partials.navbar-glass_sticky';
    }
    if (!view()->exists($heroPartial)) {
        $heroPartial = 'store.partials.hero-gradient_split';
    }
@endphp
<body class="@if(!empty($isRtl)) rtl @endif" x-data="{ cartCount: {{ count(session('cart', [])) }} }" data-template-key="{{ $storefrontRender['template_key'] ?? 'legacy' }}" data-page-type="{{ $pageType ?? '' }}">
    @if($uomoNavbarFragmentHtml)
        <div class="store-uomo-navbar-fragment border-bottom">{!! $uomoNavbarFragmentHtml !!}</div>
    @elseif($uomoNavbarUrl !== '')
        @include('store.partials.uomo-slot-clip', ['url' => $uomoNavbarUrl, 'clipHeight' => 200, 'iframeHeight' => 780])
    @else
        @include($navbarPartial)
    @endif

    @if($heroSection)
        @if($uomoHeroUrl !== '')
            @include('store.partials.uomo-slot-clip', ['url' => $uomoHeroUrl, 'clipHeight' => 460, 'iframeHeight' => 1100])
        @else
            @include($heroPartial)
        @endif
    @else
        @yield('hero')
    @endif

    <main class="container py-5">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-pill px-4" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @yield('content')
    </main>

    @if($uomoFooterUrl !== '')
        @include('store.partials.uomo-footer-slot', ['url' => $uomoFooterUrl, 'clipHeight' => 360, 'iframeHeight' => 1600])
    @else
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
    @endif

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('js')
</body>
</html>
