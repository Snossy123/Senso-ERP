<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Low Stock Alert</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0;">Low Stock Alert!</h1>
        <p style="margin: 10px 0 0 0;">Action Required</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 0 0 8px 8px;">
        <p>Dear Administrator,</p>
        <p>The following product has reached low stock levels and requires attention:</p>
        
        <div style="background: white; padding: 20px; border-radius: 5px; border-left: 4px solid #dc3545; margin: 20px 0;">
            <h2 style="margin: 0 0 15px 0; color: #dc3545;">{{ $product->name }}</h2>
            
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 5px 0;"><strong>SKU:</strong></td>
                    <td style="padding: 5px 0;">{{ $product->sku }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>Category:</strong></td>
                    <td style="padding: 5px 0;">{{ $product->category?->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>Current Stock:</strong></td>
                    <td style="padding: 5px 0;">
                        <span style="background: #dc3545; color: white; padding: 3px 10px; border-radius: 3px; font-weight: bold;">
                            {{ $product->stock_quantity }} {{ $product->unit }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>Minimum Alert Level:</strong></td>
                    <td style="padding: 5px 0;">{{ $product->min_stock_alert }} {{ $product->unit }}</td>
                </tr>
            </table>
        </div>
        
        <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <strong>Recommended Actions:</strong>
            <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                <li>Contact supplier to reorder</li>
                <li>Review current sales velocity</li>
                <li>Consider temporary promotional pricing for remaining stock</li>
            </ul>
        </div>
        
        <p style="margin-top: 30px;">
            <a href="{{ url('/inventory/products/' . $product->id) }}" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                View Product in Inventory
            </a>
        </p>
        
        <p style="margin-top: 30px; font-size: 0.9em; color: #666;">
            This is an automated notification from your ERP system.<br>
            <strong>{{ config('app.name') }}</strong>
        </p>
    </div>
</body>
</html>
