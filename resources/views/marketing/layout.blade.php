<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name')) — ERP</title>
    <link rel="stylesheet" href="{{ asset('assets/plugins/bootstrap/css/bootstrap.min.css') }}">
    <style>
        :root { --brand: #4338ca; --bg: #f8fafc; --text: #0f172a; }
        body { font-family: Inter, system-ui, sans-serif; background: var(--bg); color: var(--text); }
        .mkt-nav { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 14px 0; }
        .mkt-hero { padding: 72px 0 56px; background: linear-gradient(135deg, #eef2ff, #f8fafc); }
        .mkt-hero h1 { font-weight: 800; letter-spacing: -0.03em; font-size: clamp(2rem, 5vw, 3rem); }
        .mkt-card { border: 0; border-radius: 16px; box-shadow: 0 12px 40px rgba(15,23,42,.08); height: 100%; }
        .mkt-btn { background: var(--brand); color: #fff; border-radius: 999px; padding: 10px 22px; font-weight: 600; }
        .mkt-btn:hover { color: #fff; opacity: .92; }
        .mkt-footer { padding: 32px 0; color: #64748b; font-size: 14px; }
    </style>
</head>
<body>
    <nav class="mkt-nav">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="{{ route('marketing.home') }}" class="font-weight-bold text-dark h5 mb-0">{{ config('app.name') }}</a>
            <div>
                <a href="{{ route('marketing.pos') }}" class="mr-3">POS</a>
                <a href="{{ route('login') }}" class="mkt-btn d-inline-block">Sign in</a>
            </div>
        </div>
    </nav>
    @yield('content')
    <footer class="mkt-footer text-center border-top bg-white">
        <div class="container">&copy; {{ date('Y') }} {{ config('app.name') }} — Multi-tenant ERP</div>
    </footer>
</body>
</html>
