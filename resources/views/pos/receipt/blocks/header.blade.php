<div class="pos-receipt-block pos-receipt-header">
    @if(!empty($tenantName))
        <div class="pos-receipt-brand">{{ $tenantName }}</div>
    @endif
    <div class="pos-receipt-title">Sales receipt</div>
    @if(!empty($saleId))
        <div class="pos-receipt-meta">Sale #{{ $saleId }}</div>
    @endif
</div>
