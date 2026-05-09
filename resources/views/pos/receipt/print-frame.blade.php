{{--
  Printable receipt shell — include from terminal success modal or dedicated print route.
  Expects: $receipt array with keys optional for blocks partials.
--}}
@php
    $r = $receipt ?? [];
    $compact = $r['compact'] ?? false;
@endphp
<div class="pos-receipt-print-root {{ $compact ? 'pos-receipt-print-root--compact' : '' }}">
    @include('pos.receipt.blocks.header', [
        'tenantName' => $r['tenant_name'] ?? config('app.name'),
        'saleId' => $r['sale_id'] ?? null,
    ])
    @include('pos.receipt.blocks.lines', ['lines' => $r['lines'] ?? []])
    @include('pos.receipt.blocks.totals', [
        'subtotal' => $r['subtotal'] ?? null,
        'discount' => $r['discount'] ?? null,
        'tax' => $r['tax'] ?? null,
        'total' => $r['total'] ?? null,
        'payment' => $r['payment'] ?? null,
        'change' => $r['change'] ?? null,
    ])
    @include('pos.receipt.blocks.footer', [
        'qrPayload' => $r['qr_payload'] ?? null,
        'barcodeValue' => $r['barcode'] ?? null,
    ])
</div>
