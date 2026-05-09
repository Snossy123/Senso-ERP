<div class="pos-receipt-block pos-receipt-footer">
    <div class="pos-receipt-thanks">Thank you</div>
    @if(!empty($qrPayload))
        <div class="pos-receipt-qr" data-qr="{{ $qrPayload }}" aria-label="QR placeholder"></div>
    @endif
    @if(!empty($barcodeValue))
        <div class="pos-receipt-barcode" aria-label="Barcode">{{ $barcodeValue }}</div>
    @endif
</div>
