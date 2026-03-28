<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventoryExport implements FromCollection, WithHeadings
{
    public function __construct($products)
    {
        $this->products = $products;
    }

    public function collection()
    {
        return $this->products->map(function ($product) {
            return [
                $product->sku,
                $product->name,
                $product->category?->name ?? 'N/A',
                $product->stock_quantity,
                $product->min_stock_alert,
                $product->selling_price,
                $product->warehouse?->name ?? 'N/A',
                $product->is_active ? 'Active' : 'Inactive',
            ];
        });
    }

    public function headings(): array
    {
        return ['SKU', 'Name', 'Category', 'Stock', 'Min Stock', 'Price', 'Warehouse', 'Status'];
    }
}
