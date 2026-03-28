<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomersExport implements FromCollection, WithHeadings
{
    public function __construct($customers)
    {
        $this->customers = $customers;
    }

    public function collection()
    {
        return $this->customers->map(function ($customer) {
            return [
                $customer->name,
                $customer->email,
                $customer->phone ?? 'N/A',
                $customer->city ?? 'N/A',
                $customer->orders->count(),
                number_format($customer->orders->sum('total'), 2),
                $customer->created_at->format('Y-m-d'),
            ];
        });
    }

    public function headings(): array
    {
        return ['Name', 'Email', 'Phone', 'City', 'Orders', 'Total Spent', 'Joined'];
    }
}
