<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $order->order_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0;">Order Confirmed!</h1>
        <p style="margin: 10px 0 0 0;">Thank you for your order</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 0 0 8px 8px;">
        <h2 style="margin-top: 0;">Order #{{ $order->order_number }}</h2>
        <p>Dear {{ $customer->name }},</p>
        <p>Your order has been successfully placed and is now being processed.</p>
        
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;"><strong>Order Date:</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $order->created_at->format('F d, Y H:i') }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;"><strong>Status:</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                    <span style="background: #ffc107; padding: 3px 10px; border-radius: 3px;">Pending</span>
                </td>
            </tr>
        </table>
        
        <h3 style="border-bottom: 2px solid #007bff; padding-bottom: 10px;">Order Summary</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #e9ecef;">
                    <th style="padding: 10px; text-align: left;">Item</th>
                    <th style="padding: 10px; text-align: center;">Qty</th>
                    <th style="padding: 10px; text-align: right;">Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $item->product_name ?? 'Product' }}</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center;">{{ $item->quantity }}</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; text-align: right;">{{ config('app.currency_symbol') }}{{ number_format($item->price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="padding: 10px; text-align: right;"><strong>Total:</strong></td>
                    <td style="padding: 10px; text-align: right; font-size: 1.2em;"><strong>{{ config('app.currency_symbol') }}{{ number_format($order->total, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
        
        <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <strong>What's Next?</strong>
            <p style="margin: 10px 0 0 0;">We will notify you when your order ships. You can track your order status in your account dashboard.</p>
        </div>
        
        <p style="margin-top: 30px; font-size: 0.9em; color: #666;">
            Thank you for shopping with us!<br>
            <strong>{{ config('app.name') }}</strong><br>
            {{ config('app.address') }}<br>
            {{ config('app.phone') }}
        </p>
    </div>
</body>
</html>
