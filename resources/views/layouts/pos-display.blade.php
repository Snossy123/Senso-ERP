<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Customer display') · Senso ERP</title>
    <link href="{{ URL::asset('assets/plugins/feather/feather.css') }}" rel="stylesheet">
    <link href="{{ asset('css/pos/main.css') }}" rel="stylesheet">
    <link href="{{ asset('css/pos/customer-display.css') }}" rel="stylesheet">
    @yield('head')
</head>
<body class="pos-cd-page">
<script src="{{ asset('js/pos/pos-contracts.js') }}"></script>
<script src="{{ asset('js/pos/pos-runtime.js') }}"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@yield('content')
</body>
</html>
