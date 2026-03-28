<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #007bff; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .header { text-align: center; margin-bottom: 20px; }
        .total { font-weight: bold; font-size: 16px; color: #007bff; }
        .meta { margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <h2>Sales Report</h2>
        @if($dateFrom || $dateTo)
            <p class="meta">Period: {{ $dateFrom ?? 'Start' }} to {{ $dateTo ?? 'Now' }}</p>
        @endif
        <p class="meta">Generated: {{ now()->format('Y-m-d H:i') }}</p>
        <p class="total">Total Revenue: {{ config('app.currency_symbol') }}{{ number_format($totalRevenue, 2) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Sale #</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Cashier</th>
                <th>Payment</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
            <tr>
                <td>{{ $sale->sale_number }}</td>
                <td>{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                <td>{{ $sale->user?->name ?? 'N/A' }}</td>
                <td>{{ ucfirst($sale->payment_method) }}</td>
                <td>{{ config('app.currency_symbol') }}{{ number_format($sale->total, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="6">No sales found</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
