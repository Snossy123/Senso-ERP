<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $dir ?? 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'POS') · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ URL::asset('assets/img/brand/favicon.png') }}" type="image/x-icon"/>
    <link href="{{ URL::asset('assets/plugins/feather/feather.css') }}" rel="stylesheet">
    <link href="@localizedAsset('icons.css')" rel="stylesheet">
    <link href="@localizedAsset('style.css')" rel="stylesheet">
    <link href="{{ asset('css/pos/main.css') }}" rel="stylesheet">
    @stack('styles')
    @yield('css')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="pos-app-body">
@yield('content')

<script src="{{ URL::asset('assets/plugins/jquery/jquery.min.js') }}"></script>
@if(!empty($isRtl))
<script src="{{ URL::asset('assets/plugins/bootstrap/js/bootstrap-rtl.js') }}"></script>
@else
<script src="{{ URL::asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
@endif
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@include('pos.partials.echo-scripts')
@yield('js')
</body>
</html>
