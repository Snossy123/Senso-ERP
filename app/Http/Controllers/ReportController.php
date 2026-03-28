<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function index()
    {
        $stats = [
            'totalSales'      => Sale::sum('total'),
            'totalOrders'     => Order::count(),
            'totalProducts'   => Product::count(),
            'totalCustomers'  => Customer::count(),
            'lowStockCount'   => Product::whereColumn('stock_quantity', '<=', 'min_stock_alert')->count(),
            'todaySales'      => Sale::whereDate('created_at', today())->sum('total'),
            'monthlySales'    => Sale::whereMonth('created_at', now()->month)->sum('total'),
            'pendingOrders'   => Order::where('status', 'pending')->count(),
        ];

        return view('reports.index', compact('stats'));
    }

    public function sales(Request $request)
    {
        $query = Sale::with('user', 'customer');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $sales = $query->latest()->paginate(20)->withQueryString();
        $totalRevenue = $query->sum('total');
        $totalTax = $query->sum('tax_amount');
        $totalDiscount = $query->sum('discount_amount');

        $monthlyData = Sale::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, SUM(total) as total, COUNT(*) as count')
            ->whereYear('created_at', now()->year)
            ->groupBy('year', 'month')
            ->orderBy('month')
            ->get();

        $chartLabels = collect(range(1, 12))->map(fn($m) => date('M', mktime(0, 0, 0, $m, 1)));
        $chartData = collect(range(1, 12))->map(fn($m) => $monthlyData->firstWhere('month', $m)?->total ?? 0);

        return view('reports.sales', compact('sales', 'totalRevenue', 'totalTax', 'totalDiscount', 'chartLabels', 'chartData'));
    }

    public function inventory(Request $request)
    {
        $lowStock = Product::whereColumn('stock_quantity', '<=', 'min_stock_alert')
            ->where('is_active', true)
            ->with('category', 'warehouse')
            ->paginate(20);

        $outOfStock = Product::where('stock_quantity', 0)
            ->where('is_active', true)
            ->with('category')
            ->get();

        $categoryStats = Product::selectRaw('category_id, COUNT(*) as count, SUM(stock_quantity) as total_stock')
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->with('category')
            ->get();

        return view('reports.inventory', compact('lowStock', 'outOfStock', 'categoryStats'));
    }

    public function profit(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : now()->startOfMonth();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : now()->endOfMonth();

        $salesData = Sale::selectRaw('DATE(created_at) as date, SUM(subtotal) as revenue, SUM(tax_amount) as tax, SUM(total) as total')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalRevenue = Sale::whereBetween('created_at', [$dateFrom, $dateTo])->sum('subtotal');
        $totalTax = Sale::whereBetween('created_at', [$dateFrom, $dateTo])->sum('tax_amount');
        $totalSales = Sale::whereBetween('created_at', [$dateFrom, $dateTo])->sum('total');
        $orderCount = Sale::whereBetween('created_at', [$dateFrom, $dateTo])->count();

        $avgOrderValue = $orderCount > 0 ? $totalSales / $orderCount : 0;

        $topProducts = Product::withCount([
            'saleItems as total_sold' => fn($q) => $q->whereHas('sale', fn($s) => $s->whereBetween('created_at', [$dateFrom, $dateTo]))
        ])
            ->having('total_sold', '>', 0)
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        return view('reports.profit', compact(
            'totalRevenue', 'totalTax', 'totalSales', 'orderCount', 'avgOrderValue',
            'topProducts', 'dateFrom', 'dateTo', 'salesData'
        ));
    }

    public function customers(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : now()->startOfMonth();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : now()->endOfMonth();

        $topCustomers = Customer::withCount([
            'orders as order_count' => fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo]),
        ])
            ->withSum([
                'orders as total_spent' => fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo])->where('status', '!=', 'cancelled')
            ], 'total')
            ->orderByDesc('total_spent')
            ->limit(20)
            ->get();

        $newCustomers = Customer::whereBetween('created_at', [$dateFrom, $dateTo])->count();

        return view('reports.customers', compact('topCustomers', 'newCustomers', 'dateFrom', 'dateTo'));
    }
}
