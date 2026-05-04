<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $dir ?? 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storefront Preview</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @if(!empty($isRtl))
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @else
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif
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
    </style>
</head>
<body class="bg-light @if(!empty($isRtl)) rtl @endif" x-data="{ cartCount: 0 }">
<div class="container py-5">
    <h2 class="mb-1">{{ $storefront->name }} - Preview</h2>
    <p class="text-muted">Page type: {{ $pageType }} | Template: {{ $storefront->active_template_key }}</p>

    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong>Purchased Uomo theme (static)</strong>
                <div class="small opacity-75">Same HTML/CSS as the pack under <code>/uomo</code>, served at <code>/__uomo/…</code></div>
            </div>
            <a class="btn btn-sm btn-light" href="{{ $uomoVisualUrl }}" target="_blank" rel="noopener">Open in new tab</a>
        </div>
        <div class="card-body p-0">
            <div style="height: 75vh; min-height: 520px;">
                <iframe class="w-100 h-100 border-0 d-block" src="{{ $uomoVisualUrl }}" title="Uomo static reference"></iframe>
            </div>
            <div class="p-2 small text-muted border-top">File: <code>{{ $uomoVisualPath }}</code></div>
        </div>
    </div>

    <p class="small text-muted mb-4">
        The JSON blocks below are <strong>ERP builder data</strong> (sections/payload), not the storefront UI.
        Use the frame above for the real purchased layout; the Laravel <code>/store</code> pages merge builder settings with your catalog over time.
    </p>

    @if($pageType === 'home')
        @php
            $builderSections = $sections->map(fn($s) => [
                'section_key' => $s->section_key,
                'section_type' => $s->section_type,
                'payload' => $s->payload ?? [],
            ]);
            $heroSectionArr = $builderSections->firstWhere('section_type', 'hero');
            $heroPayload = $heroSectionArr['payload'] ?? [];

            $settings = $storefront->settings ?? [];
            $slotSvc = app(\App\Modules\StorefrontBuilder\Services\LayoutSlotService::class);
            $reg = app(\App\Modules\StorefrontBuilder\Services\TemplateRegistryService::class);
            $uomoNavKey = $slotSvc->globalNavbarKey($settings);
            $uomoHeroKey = $slotSvc->pageSlot($settings, 'home', 'hero');
            $uomoNavMeta = $uomoNavKey ? $reg->findTemplate($uomoNavKey) : null;
            $uomoHeroMeta = $uomoHeroKey ? $reg->findTemplate($uomoHeroKey) : null;
            $uomoNavUrl = $uomoNavMeta ? url($uomoNavMeta['preview']) : '';
            $uomoHeroUrl = $uomoHeroMeta ? url($uomoHeroMeta['preview']) : '';

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

        <div class="card mb-4">
            <div class="card-header">Live layout preview (navbar + hero)</div>
            <div class="card-body p-0">
                <div class="border rounded overflow-hidden">
                    @if($uomoNavUrl !== '')
                        @include('store.partials.uomo-slot-clip', ['url' => $uomoNavUrl, 'clipHeight' => 200, 'iframeHeight' => 780])
                    @else
                        @include($navbarPartial)
                    @endif
                    @if($heroSectionArr)
                        @if($uomoHeroUrl !== '')
                            @include('store.partials.uomo-slot-clip', ['url' => $uomoHeroUrl, 'clipHeight' => 460, 'iframeHeight' => 1100])
                        @else
                            @include($heroPartial)
                        @endif
                    @else
                        <div class="p-4 text-muted">No hero section found for this page.</div>
                    @endif
                </div>
                <div class="p-3 text-muted small">
                    Navbar variant: <code>{{ $navbarVariant }}</code> · Hero variant: <code>{{ $heroVariant }}</code>
                </div>
            </div>
        </div>
    @endif

    <details class="mb-4">
        <summary class="fw-semibold">Section payload (advanced / debugging)</summary>
        @foreach($sections as $section)
            <div class="card mb-3 mt-3">
                <div class="card-header d-flex justify-content-between">
                    <strong>{{ $section->section_type }}</strong>
                    <span class="badge {{ $section->is_enabled ? 'bg-success' : 'bg-secondary' }}">
                        {{ $section->is_enabled ? 'enabled' : 'disabled' }}
                    </span>
                </div>
                <div class="card-body">
                    <pre class="mb-0 small">{{ json_encode($section->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @endforeach
    </details>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
