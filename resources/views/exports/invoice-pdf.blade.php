<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .company { text-align: left; }
        .invoice { text-align: right; }
        .info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #007bff; color: white; }
        .total-section { margin-top: 20px; text-align: right; }
        .footer { margin-top: 50px; text-align: center; color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">
            <h2>{{ config('app.name') }}</h2>
            <p>{{ config('app.address') }}</p>
            <p>Tel: {{ config('app.phone') }}</p>
            <p>Email: {{ config('app.email') }}</p>
        </div>
        <div class="invoice">
            <h1>INVOICE</h1>
            <p><strong>#{{ $order->order_number }}</strong></p>
            <p>Date: {{ $order->created_at->format('Y-m-d') }}</p>
            <p>Status: {{ ucfirst($order->status) }}</p>
        </div>
    </div>

    <div class="info">
        <strong>Bill To:</strong><br>
        {{ $order->customer_name }}<br>
        {{ $order->shipping_address }}<br>
        {{ $order->city }}<br>
        @if($order->customer_email)
            {{ $order->customer_email }}<br>
        @endif
        @if($order->customer_phone)
            {{ $order->customer_phone }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ config('app.currency_symbol') }}{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ config('app.currency_symbol') }}{{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <p><strong>Subtotal:</strong> {{ config('app.currency_symbol') }}{{ number_format($order->subtotal, 2) }}</p>
        @if($order->discount > 0)
            <p><strong>Discount:</strong> -{{ config('app.currency_symbol') }}{{ number_format($order->discount, 2) }}</p>
        @endif
        @if($order->shipping_cost > 0)
            <p><strong>Shipping:</strong> {{ config('app.currency_symbol') }}{{ number_format($order->shipping_cost, 2) }}</p>
        @endif
        <p style="font-size: 16px;"><strong>TOTAL:</strong> {{ config('app.currency_symbol') }}{{ number_format($order->total, 2) }}</p>
    </div>

    @if($order->notes)
    <div style="margin-top: 30px;">
        <strong>Notes:</strong>
        <p>{{ $order->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>{{ config('app.name') }} | {{ config('app.email') }}</p>
    </div>
</body>
</html>
