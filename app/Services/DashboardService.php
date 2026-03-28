<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    protected int $cacheMinutes = 5;

    public function getProductsOverview(): array
    {
        return Cache::remember('dashboard_products_overview', $this->cacheMinutes * 60, function () {
            $total = Product::where('is_active', true)->count();
            $outOfStock = Product::where('is_active', true)->where('stock_quantity', 0)->count();
            $lowStock = Product::where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->whereColumn('stock_quantity', '<=', 'min_stock_alert')
                ->count();

            return [
                'total' => $total,
                'out_of_stock' => $outOfStock,
                'low_stock' => $lowStock,
                'healthy_stock' => $total - $outOfStock - $lowStock,
            ];
        });
    }

    public function getTodaySales(): array
    {
        return Cache::remember('dashboard_today_sales', $this->cacheMinutes * 60, function () {
            $sales = Sale::whereDate('created_at', today());
            $posOrders = $sales->count();
            $revenue = $sales->sum('total');
            $avgOrderValue = $posOrders > 0 ? $revenue / $posOrders : 0;

            $onlineOrders = Order::whereDate('created_at', today())->count();
            $onlineRevenue = Order::whereDate('created_at', today())->where('payment_status', 'paid')->sum('total');

            return [
                'pos_orders' => $posOrders,
                'pos_revenue' => $revenue,
                'avg_order_value' => round($avgOrderValue, 2),
                'online_orders' => $onlineOrders,
                'online_revenue' => $onlineRevenue,
                'total_orders' => $posOrders + $onlineOrders,
                'total_revenue' => $revenue + $onlineRevenue,
            ];
        });
    }

    public function getPendingOrders(): array
    {
        return Cache::remember('dashboard_pending_orders', $this->cacheMinutes * 60, function () {
            $pendingPos = Sale::where('payment_status', '!=', 'paid')->count();
            $pendingOnline = Order::whereIn('status', ['pending', 'processing'])->count();

            $orders = Order::select('id', 'order_number', 'customer_name', 'total', 'status', 'created_at')
                ->whereIn('status', ['pending', 'processing'])
                ->orderByRaw("FIELD(status, 'pending', 'processing')")
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return [
                'pos_pending' => $pendingPos,
                'online_pending' => $pendingOnline,
                'total_pending' => $pendingPos + $pendingOnline,
                'recent_pending' => $orders,
            ];
        });
    }

    public function getLowStockAlert(): array
    {
        return Cache::remember('dashboard_low_stock', $this->cacheMinutes * 60, function () {
            $products = Product::where('is_active', true)
                ->whereColumn('stock_quantity', '<=', 'min_stock_alert')
                ->select('id', 'name', 'sku', 'stock_quantity', 'min_stock_alert', 'selling_price')
                ->orderByRaw('stock_quantity / min_stock_alert ASC')
                ->limit(10)
                ->get();

            $critical = $products->where('stock_quantity', 0)->count();
            $warning = $products->where('stock_quantity', '>', 0)->count();

            return [
                'total' => $products->count(),
                'critical' => $critical,
                'warning' => $warning,
                'products' => $products,
            ];
        });
    }

    public function getOutOfStock(): array
    {
        return Cache::remember('dashboard_out_of_stock', $this->cacheMinutes * 60, function () {
            $products = Product::where('is_active', true)
                ->where('stock_quantity', 0)
                ->select('id', 'name', 'sku', 'category_id', 'selling_price')
                ->with('category:id,name')
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get();

            return [
                'count' => $products->count(),
                'products' => $products,
            ];
        });
    }

    public function getTopSellingProducts(string $period = '30days'): array
    {
        $cacheKey = "dashboard_top_products_{$period}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($period) {
            $days = match($period) {
                '7days' => 7,
                '30days' => 30,
                '90days' => 90,
                default => 30,
            };

            $products = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(total) as total_revenue'))
                ->whereHas('sale', fn($q) => $q->where('created_at', '>=', now()->subDays($days)))
                ->groupBy('product_id')
                ->orderByDesc('total_sold')
                ->limit(10)
                ->get()
                ->map(fn($item) => [
                    'id' => $item->product_id,
                    'name' => $item->product?->name ?? 'Unknown',
                    'sku' => $item->product?->sku ?? 'N/A',
                    'total_sold' => (int) $item->total_sold,
                    'total_revenue' => round($item->total_revenue, 2),
                ]);

            return [
                'period' => $period,
                'days' => $days,
                'products' => $products,
            ];
        });
    }

    public function getTopCustomers(string $period = '30days'): array
    {
        $cacheKey = "dashboard_top_customers_{$period}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($period) {
            $days = match($period) {
                '7days' => 7,
                '30days' => 30,
                '90days' => 90,
                default => 30,
            };

            $customers = Sale::select('customer_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total) as total_spent'))
                ->where('customer_id', '!=', null)
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('customer_id')
                ->orderByDesc('total_spent')
                ->limit(10)
                ->get()
                ->map(fn($sale) => [
                    'id' => $sale->customer_id,
                    'name' => $sale->customer?->name ?? 'Unknown',
                    'email' => $sale->customer?->email ?? 'N/A',
                    'order_count' => (int) $sale->order_count,
                    'total_spent' => round($sale->total_spent, 2),
                ]);

            return [
                'period' => $period,
                'days' => $days,
                'customers' => $customers,
            ];
        });
    }

    public function getStockValue(): array
    {
        return Cache::remember('dashboard_stock_value', $this->cacheMinutes * 60, function () {
            $products = Product::where('is_active', true)
                ->selectRaw('SUM(stock_quantity * purchase_price) as total_cost')
                ->selectRaw('SUM(stock_quantity * selling_price) as total_retail_value')
                ->selectRaw('SUM(stock_quantity) as total_units')
                ->first();

            return [
                'total_units' => (int) ($products->total_units ?? 0),
                'total_cost' => round($products->total_cost ?? 0, 2),
                'total_retail_value' => round($products->total_retail_value ?? 0, 2),
                'potential_profit' => round(($products->total_retail_value ?? 0) - ($products->total_cost ?? 0), 2),
            ];
        });
    }

    public function getSalesChart(string $type = 'monthly'): array
    {
        $cacheKey = "dashboard_sales_chart_{$type}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($type) {
            if ($type === 'daily') {
                $data = Sale::selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->map(fn($row) => [
                        'label' => \Carbon\Carbon::parse($row->date)->format('M d'),
                        'revenue' => round($row->revenue, 2),
                        'orders' => $row->orders,
                    ]);

                return [
                    'type' => $type,
                    'labels' => $data->pluck('label'),
                    'revenue' => $data->pluck('revenue'),
                    'orders' => $data->pluck('orders'),
                ];
            }

            $months = collect(range(1, 12))->map(fn($m) => date('M', mktime(0, 0, 0, $m, 1)));
            $sales = Sale::selectRaw('MONTH(created_at) as month, COUNT(*) as orders, SUM(total) as revenue')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month');

            $revenue = collect(range(1, 12))->map(fn($m) => round($sales[$m]->revenue ?? 0, 2));
            $orders = collect(range(1, 12))->map(fn($m) => $sales[$m]->orders ?? 0);

            return [
                'type' => $type,
                'labels' => $months,
                'revenue' => $revenue,
                'orders' => $orders,
            ];
        });
    }

    public function getRecentSales(int $limit = 10): array
    {
        $sales = Sale::with(['customer:id,name', 'user:id,name'])
            ->select('id', 'sale_number', 'customer_id', 'user_id', 'total', 'payment_status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($sale) => [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'customer' => $sale->customer?->name ?? 'Walk-in Customer',
                'cashier' => $sale->user?->name ?? 'Unknown',
                'total' => round($sale->total, 2),
                'payment_status' => $sale->payment_status,
                'created_at' => $sale->created_at->toIso8601String(),
                'time_ago' => $sale->created_at->diffForHumans(),
            ]);

        return [
            'sales' => $sales,
            'count' => $sales->count(),
        ];
    }

    public function getAlerts(): array
    {
        return Cache::remember('dashboard_alerts', 2 * 60, function () {
            $alerts = [];

            $outOfStock = Product::where('is_active', true)->where('stock_quantity', 0)->count();
            if ($outOfStock > 0) {
                $alerts[] = [
                    'type' => 'critical',
                    'icon' => 'fe fe-alert-circle',
                    'title' => 'Out of Stock',
                    'message' => "{$outOfStock} product(s) have zero stock",
                    'action' => route('inventory.products.index', ['filter' => 'out_of_stock']),
                    'count' => $outOfStock,
                ];
            }

            $lowStock = Product::where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->whereColumn('stock_quantity', '<=', 'min_stock_alert')
                ->count();
            if ($lowStock > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => 'fe fe-alert-triangle',
                    'title' => 'Low Stock Warning',
                    'message' => "{$lowStock} product(s) below minimum stock",
                    'action' => route('inventory.products.index', ['filter' => 'low_stock']),
                    'count' => $lowStock,
                ];
            }

            $pendingOrders = Order::where('status', 'pending')->count();
            if ($pendingOrders > 0) {
                $alerts[] = [
                    'type' => 'info',
                    'icon' => 'fe fe-shopping-bag',
                    'title' => 'Pending Orders',
                    'message' => "{$pendingOrders} order(s) awaiting processing",
                    'action' => route('admin.orders.index', ['status' => 'pending']),
                    'count' => $pendingOrders,
                ];
            }

            $unpaidOrders = Order::where('payment_status', 'pending')->where('status', '!=', 'cancelled')->count();
            if ($unpaidOrders > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => 'fe fe-credit-card',
                    'title' => 'Unpaid Orders',
                    'message' => "{$unpaidOrders} order(s) with pending payment",
                    'action' => route('admin.orders.index', ['payment' => 'pending']),
                    'count' => $unpaidOrders,
                ];
            }

            return $alerts;
        });
    }

    public function getSalesSummary(): array
    {
        return Cache::remember('dashboard_sales_summary', $this->cacheMinutes * 60, function () {
            $today = Sale::whereDate('created_at', today())->sum('total');
            $yesterday = Sale::whereDate('created_at', today()->subDay())->sum('total');
            $thisMonth = Sale::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');
            $lastMonth = Sale::whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->sum('total');
            $thisYear = Sale::whereYear('created_at', now()->year)->sum('total');

            $todayGrowth = $yesterday > 0 ? (($today - $yesterday) / $yesterday) * 100 : ($today > 0 ? 100 : 0);
            $monthGrowth = $lastMonth > 0 ? (($thisMonth - $lastMonth) / $lastMonth) * 100 : ($thisMonth > 0 ? 100 : 0);

            return [
                'today' => round($today, 2),
                'yesterday' => round($yesterday, 2),
                'today_growth' => round($todayGrowth, 1),
                'this_month' => round($thisMonth, 2),
                'last_month' => round($lastMonth, 2),
                'month_growth' => round($monthGrowth, 1),
                'this_year' => round($thisYear, 2),
            ];
        });
    }

    public function getWidget(string $widget): array
    {
        return match($widget) {
            'products' => $this->getProductsOverview(),
            'today_sales' => $this->getTodaySales(),
            'pending_orders' => $this->getPendingOrders(),
            'low_stock' => $this->getLowStockAlert(),
            'out_of_stock' => $this->getOutOfStock(),
            'top_products' => $this->getTopSellingProducts(),
            'top_customers' => $this->getTopCustomers(),
            'stock_value' => $this->getStockValue(),
            'sales_chart' => $this->getSalesChart(),
            'recent_sales' => $this->getRecentSales(),
            'alerts' => $this->getAlerts(),
            'summary' => $this->getSalesSummary(),
            default => throw new \InvalidArgumentException("Unknown widget: {$widget}"),
        };
    }
}
