<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventory Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #007bff; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .header { text-align: center; margin-bottom: 20px; }
        .low-stock { color: #dc3545; font-weight: bold; }
        .section { margin-top: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <h2>Inventory Report</h2>
        <p>Generated: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    <div class="section">
        <h3>Low Stock Alert ({{ $lowStock->count() }} items)</h3>
        @if($lowStock->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Min</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lowStock as $product)
                <tr>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category?->name ?? 'N/A' }}</td>
                    <td class="low-stock">{{ $product->stock_quantity }}</td>
                    <td>{{ $product->min_stock_alert }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No low stock items</p>
        @endif
    </div>

    <div class="section">
        <h3>All Products ({{ $products->count() }} items)</h3>
        <table>
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Price</th>
                    <th>Warehouse</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                <tr>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category?->name ?? 'N/A' }}</td>
                    <td class="{{ $product->stock_quantity <= $product->min_stock_alert ? 'low-stock' : '' }}">{{ $product->stock_quantity }}</td>
                    <td>{{ config('app.currency_symbol') }}{{ number_format($product->selling_price, 2) }}</td>
                    <td>{{ $product->warehouse?->name ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
