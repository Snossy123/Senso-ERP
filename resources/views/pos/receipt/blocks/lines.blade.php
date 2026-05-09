<table class="pos-receipt-lines" aria-label="Line items">
    <thead>
        <tr><th class="pos-receipt-col-qty">Qty</th><th class="pos-receipt-col-item">Item</th><th class="pos-receipt-col-amt">Amt</th></tr>
    </thead>
    <tbody>
        @foreach($lines ?? [] as $line)
            <tr>
                <td class="pos-receipt-col-qty">{{ $line['qty'] ?? '' }}</td>
                <td class="pos-receipt-col-item">{{ $line['name'] ?? '' }}</td>
                <td class="pos-receipt-col-amt">{{ $line['total'] ?? '' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
