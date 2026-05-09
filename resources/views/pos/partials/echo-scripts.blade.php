{{-- Laravel Echo + Pusher — only when broadcasting uses Pusher with credentials --}}
@php
    $bcDriver = config('broadcasting.default');
    $pusherKey = config('broadcasting.connections.pusher.key');
@endphp
@if($bcDriver === 'pusher' && !empty($pusherKey))
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0-rc2/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
<script>
    window.posEchoBroadcastConfig = {
        key: @json($pusherKey),
        cluster: @json(config('broadcasting.connections.pusher.options.cluster', 'mt1')),
        authEndpoint: @json(url('/broadcasting/auth')),
        csrf: @json(csrf_token()),
    };
</script>
<script src="{{ asset('js/pos-echo-bootstrap.js') }}"></script>
@endif
