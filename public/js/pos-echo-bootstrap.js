/**
 * Instantiates Laravel Echo when Pusher + Echo library globals exist.
 * Loaded after pusher.min.js and echo.iife.js (see POS terminal blade).
 */
(function () {
    'use strict';
    if (typeof window.Pusher === 'undefined') return;
    var EchoCtor = window.Echo;
    if (EchoCtor && typeof EchoCtor !== 'function' && typeof EchoCtor.default === 'function') {
        EchoCtor = EchoCtor.default;
    }
    if (typeof EchoCtor !== 'function') return;
    var cfg = window.posEchoBroadcastConfig;
    if (!cfg || !cfg.key) return;
    try {
        window.Echo = new EchoCtor({
            broadcaster: 'pusher',
            key: cfg.key,
            cluster: cfg.cluster || 'mt1',
            forceTLS: true,
            encrypted: true,
            authEndpoint: cfg.authEndpoint,
            auth: {
                headers: {
                    'X-CSRF-TOKEN': cfg.csrf || '',
                    Accept: 'application/json',
                },
            },
        });
    } catch (e) {
        console.warn('[POS Echo] bootstrap failed', e);
    }
})();
