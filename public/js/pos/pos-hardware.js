/**
 * Hardware abstraction layer — stub adapters only (Phase 4 integrates drivers).
 */
(function (global) {
    'use strict';

    function noopAsync() {
        return Promise.resolve({ ok: true, skipped: true });
    }

    const BarcodeScanner = {
        async listen(callback) {
            global.Pos?.EventBus?.on?.('hardware_barcode_raw', callback);
            return () => global.Pos?.EventBus?.off?.('hardware_barcode_raw', callback);
        },
        /** Simulate scan from keyboard wedge / tests */
        ingest(code) {
            global.Pos?.EventBus?.emit?.('hardware_barcode_raw', { code: String(code || '') });
        },
    };

    const ReceiptPrinter = {
        async print(htmlFragment) {
            return noopAsync();
        },
        async openCashDrawer() {
            return noopAsync();
        },
    };

    const CashDrawer = {
        async kick() {
            return noopAsync();
        },
    };

    const WeightScale = {
        async readStableGrams() {
            return { grams: null, skipped: true };
        },
    };

    const SecondaryDisplay = {
        async show(htmlOrPayload) {
            return noopAsync();
        },
    };

    global.PosHardware = {
        BarcodeScanner,
        ReceiptPrinter,
        CashDrawer,
        WeightScale,
        SecondaryDisplay,
    };
})(typeof window !== 'undefined' ? window : globalThis);
