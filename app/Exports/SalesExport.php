<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesExport implements FromCollection, WithHeadings
{
    public function __construct($sales)
    {
        $this->sales = $sales;
    }

    public function collection()
    {
        return $this->sales->map(function ($sale) {
            return [
                $sale->sale_number,
                $sale->created_at->format('Y-m-d H:i'),
                $sale->customer?->name ?? 'Walk-in',
                $sale->user?->name ?? 'N/A',
                $sale->payment_method,
                number_format($sale->subtotal, 2),
                number_format($sale->tax_amount, 2),
                number_format($sale->total, 2),
            ];
        });
    }

    public function headings(): array
    {
        return ['Sale #', 'Date', 'Customer', 'Cashier', 'Payment', 'Subtotal', 'Tax', 'Total'];
    }
}
