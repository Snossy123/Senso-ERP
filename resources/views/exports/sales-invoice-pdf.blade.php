<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #333; color: white; }
        .totals { margin-top: 16px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h2>{{ config('app.name') }}</h2>
        </div>
        <div style="text-align:right">
            <h1>SALES INVOICE</h1>
            <p><strong>#{{ $invoice->invoice_number }}</strong></p>
            <p>Date: {{ $invoice->invoice_date?->format('Y-m-d') }}</p>
            <p>Status: {{ ucfirst($invoice->status) }}</p>
        </div>
    </div>
    <p><strong>Customer:</strong> {{ $invoice->customer->name }}<br>
    @if($invoice->customer->tax_number)Tax: {{ $invoice->customer->tax_number }}<br>@endif
    {{ $invoice->customer->phone }} {{ $invoice->customer->email }}</p>

    <table>
        <thead>
            <tr><th>Description</th><th>Qty</th><th>Unit</th><th>Total</th></tr>
        </thead>
        <tbody>
        @foreach($invoice->lines as $line)
            <tr>
                <td>{{ $line->description ?? $line->product?->name }}</td>
                <td>{{ $line->quantity }}</td>
                <td>{{ number_format($line->unit_price, 2) }}</td>
                <td>{{ number_format($line->line_total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="totals">
        <p>Subtotal: {{ number_format($invoice->subtotal, 2) }}</p>
        <p>Discount: {{ number_format($invoice->discount_amount, 2) }}</p>
        <p>Tax: {{ number_format($invoice->tax_amount, 2) }}</p>
        <p><strong>Total: {{ number_format($invoice->total, 2) }}</strong></p>
        <p>Paid: {{ number_format($invoice->paid_amount, 2) }} | Balance: {{ number_format($invoice->balance_due, 2) }}</p>
    </div>
</body>
</html>
