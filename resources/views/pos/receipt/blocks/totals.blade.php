<div class="pos-receipt-totals">
    @if(isset($subtotal))
        <div class="pos-receipt-row"><span>Subtotal</span><span>{{ $subtotal }}</span></div>
    @endif
    @if(isset($discount) && $discount !== '' && (float) $discount > 0)
        <div class="pos-receipt-row"><span>Discount</span><span>-{{ $discount }}</span></div>
    @endif
    @if(isset($tax))
        <div class="pos-receipt-row"><span>Tax</span><span>{{ $tax }}</span></div>
    @endif
    @if(isset($total))
        <div class="pos-receipt-row pos-receipt-row--total"><span>Total</span><span>{{ $total }}</span></div>
    @endif
    @if(isset($payment))
        <div class="pos-receipt-row"><span>Payment</span><span>{{ $payment }}</span></div>
    @endif
    @if(isset($change))
        <div class="pos-receipt-row"><span>Change</span><span>{{ $change }}</span></div>
    @endif
</div>
