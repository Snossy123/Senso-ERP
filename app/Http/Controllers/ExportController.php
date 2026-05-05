<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function salesPdf(Request $request)
    {
        $query = Sale::with('user', 'customer');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sales = $query->latest()->get();
        $totalRevenue = $sales->sum('total');

        $pdf = Pdf::loadView('exports.sales-pdf', [
            'sales' => $sales,
            'totalRevenue' => $totalRevenue,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ]);

        return $pdf->download('sales-report-'.date('Y-m-d').'.pdf');
    }

    public function salesExcel(Request $request)
    {
        $query = Sale::with('user', 'customer');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sales = $query->latest()->get();

        return Excel::download(new \App\Exports\SalesExport($sales), 'sales-report-'.date('Y-m-d').'.xlsx');
    }

    public function inventoryPdf()
    {
        $products = Product::with('category', 'warehouse')->get();
        $lowStock = Product::whereColumn('stock_quantity', '<=', 'min_stock_alert')->get();

        $pdf = Pdf::loadView('exports.inventory-pdf', [
            'products' => $products,
            'lowStock' => $lowStock,
        ]);

        return $pdf->download('inventory-report-'.date('Y-m-d').'.pdf');
    }

    public function inventoryExcel()
    {
        $products = Product::with('category', 'warehouse')->get();

        return Excel::download(new \App\Exports\InventoryExport($products), 'inventory-report-'.date('Y-m-d').'.xlsx');
    }

    public function ordersPdf(Request $request)
    {
        $query = Order::with('customer');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->get();

        $pdf = Pdf::loadView('exports.orders-pdf', [
            'orders' => $orders,
        ]);

        return $pdf->download('orders-report-'.date('Y-m-d').'.pdf');
    }

    public function customersExcel()
    {
        $customers = Customer::with('orders')->get();

        return Excel::download(new \App\Exports\CustomersExport($customers), 'customers-report-'.date('Y-m-d').'.xlsx');
    }

    public function receiptPdf(Sale $sale)
    {
        $sale->load('items.product', 'user', 'customer');

        $pdf = Pdf::loadView('exports.receipt-pdf', [
            'sale' => $sale,
        ]);

        return $pdf->download('receipt-'.$sale->sale_number.'.pdf');
    }

    public function invoicePdf(Order $order)
    {
        $order->load('items');

        $pdf = Pdf::loadView('exports.invoice-pdf', [
            'order' => $order,
        ]);

        return $pdf->download('invoice-'.$order->order_number.'.pdf');
    }
}
