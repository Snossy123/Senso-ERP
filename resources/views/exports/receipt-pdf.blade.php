<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $sale->sale_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; width: 300px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
        .items table { width: 100%; border-collapse: collapse; }
        .items th, .items td { padding: 4px 0; }
        .total { border-top: 1px dashed #000; padding-top: 10px; margin-top: 10px; }
        .total-row { display: flex; justify-content: space-between; }
        .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ config('app.name') }}</h2>
        <p>{{ config('app.address') }}</p>
        <p>Tel: {{ config('app.phone') }}</p>
        <h3>RECEIPT</h3>
        <p><strong>#{{ $sale->sale_number }}</strong></p>
        <p>{{ $sale->created_at->format('Y-m-d H:i') }}</p>
        <p>Cashier: {{ $sale->user?->name ?? 'N/A' }}</p>
    </div>

    <div class="items">
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr>
                    <td>{{ $item->product?->name ?? 'Product' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ config('app.currency_symbol') }}{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="total">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>{{ config('app.currency_symbol') }}{{ number_format($sale->subtotal, 2) }}</span>
        </div>
        @if($sale->tax_amount > 0)
        <div class="total-row">
            <span>Tax:</span>
            <span>{{ config('app.currency_symbol') }}{{ number_format($sale->tax_amount, 2) }}</span>
        </div>
        @endif
        @if($sale->discount_amount > 0)
        <div class="total-row">
            <span>Discount:</span>
            <span>-{{ config('app.currency_symbol') }}{{ number_format($sale->discount_amount, 2) }}</span>
        </div>
        @endif
        <div class="total-row" style="font-weight: bold; font-size: 14px; margin-top: 5px;">
            <span>TOTAL:</span>
            <span>{{ config('app.currency_symbol') }}{{ number_format($sale->total, 2) }}</span>
        </div>
        <div class="total-row" style="margin-top: 5px;">
            <span>Payment:</span>
            <span>{{ ucfirst($sale->payment_method) }}</span>
        </div>
    </div>

    <div class="footer">
        <p>Thank you for your purchase!</p>
        <p>{{ config('app.email') }}</p>
    </div>
</body>
</html>
