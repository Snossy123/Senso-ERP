/**
 * Laravel Echo → Pos.Realtime (optional).
 * Subscribes to tenant inventory + POS channels when window.Echo exists.
 * No Echo / no Pusher → transport no-op; POS remains fully functional.
 */
(function (global) {
    'use strict';

    if (!global.Pos?.Realtime?.registerTransport) return;

    global.Pos.Realtime.registerTransport(function (ctx) {
        var Echo = global.Echo;
        if (!Echo || typeof Echo.private !== 'function') {
            return {
                publish: function () {},
                subscribe: function () {
                    return function () {};
                },
                status: 'disabled',
            };
        }

        var cfg = global.posRealtimeEchoConfig || {};
        var tenantId = cfg.tenantId;
        var shiftId = cfg.shiftId;
        var cleanups = [];

        function safeListen(channel, eventName, handler) {
            if (!channel || typeof channel.listen !== 'function') return;
            try {
                channel.listen(eventName, handler);
                cleanups.push(function () {
                    try {
                        channel.stopListening?.(eventName);
                    } catch (e) {
                        /* ignore */
                    }
                });
            } catch (e) {
                console.warn('[POS Echo] listen', eventName, e);
            }
        }

        try {
            if (tenantId != null) {
                var inv = Echo.private('tenant.' + tenantId + '.inventory');
                safeListen(inv, '.inventory.bulk.updated', function (payload) {
                    ctx.EventBus.emit('inventory_remote_bulk', payload || {});
                    global.Pos?.Ops?.record?.('ws_inventory', { tenantId: tenantId });
                });

                var pos = Echo.private('tenant.' + tenantId + '.pos');
                safeListen(pos, '.sale.completed', function (payload) {
                    ctx.EventBus.emit('pos_sale_completed_remote', payload || {});
                    global.Pos?.Ops?.record?.('ws_sale', { tenantId: tenantId });
                });
            }

            if (shiftId != null) {
                var sh = Echo.private('shift.' + shiftId);
                /** Reserved for shift.closed / cash events — avoid duplicating sale.completed */
                safeListen(sh, '.shift.updated', function (payload) {
                    ctx.EventBus.emit('pos_shift_remote', payload || {});
                });
            }
        } catch (e) {
            console.warn('[POS Echo] channel setup', e);
        }

        global.Pos?.Ops?.record?.('ws_connect', { ok: true });

        return {
            status: 'connected',
            publish: function (topic, payload) {
                global.Pos?.EventBus?.emit?.('realtime_publish', { topic: topic, payload: payload });
            },
            subscribe: function (topic, cb) {
                var off = global.Pos?.EventBus?.on?.(topic, cb);
                return typeof off === 'function' ? off : function () {};
            },
            disconnect: function () {
                cleanups.forEach(function (fn) {
                    try {
                        fn();
                    } catch (e) {
                        /* ignore */
                    }
                });
            },
        };
    });
})(typeof window !== 'undefined' ? window : globalThis);
