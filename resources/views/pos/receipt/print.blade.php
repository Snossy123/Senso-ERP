<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $receipt['sale_number'] ?? $receipt['sale_id'] ?? '' }}</title>
    <style>
        @page { margin: 4mm; }
        body { font-family: ui-monospace, monospace; font-size: 12px; margin: 0; padding: 8px; color: #111; }
        .pos-receipt-print-root { max-width: 72mm; margin: 0 auto; }
        .pos-receipt-brand { font-weight: 700; font-size: 14px; text-align: center; }
        .pos-receipt-title { text-align: center; margin: 4px 0 8px; font-size: 11px; text-transform: uppercase; }
        .pos-receipt-meta-grid { font-size: 10px; margin-bottom: 8px; }
        .pos-receipt-meta-grid div { display: flex; justify-content: space-between; gap: 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { padding: 2px 0; text-align: left; }
        th:last-child, td:last-child { text-align: right; }
        .pos-receipt-row { display: flex; justify-content: space-between; margin: 2px 0; }
        .pos-receipt-row--total { font-weight: 700; font-size: 13px; margin-top: 6px; padding-top: 4px; border-top: 1px dashed #999; }
        .pos-receipt-footer { text-align: center; font-size: 10px; margin-top: 12px; color: #666; }
    </style>
</head>
<body>
    @include('pos.receipt.print-frame', ['receipt' => $receipt])

    <p class="pos-receipt-footer">Thank you</p>

    @if(!empty($autoPrint))
    <script>window.onload = function () { setTimeout(function () { window.print(); }, 300); };</script>
    @endif
</body>
</html>
