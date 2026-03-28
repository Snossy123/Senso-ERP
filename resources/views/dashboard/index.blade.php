@extends('layouts.master')
@section('title', 'Dashboard')

@section('page-header')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Dashboard</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<style>
    .dashboard-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .dashboard-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1), 0 2px 4px rgba(0,0,0,0.06);
    }
    .stat-card {
        padding: 1.5rem;
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .stat-trend {
        font-size: 0.75rem;
        font-weight: 600;
    }
    .stat-trend.up { color: #10b981; }
    .stat-trend.down { color: #ef4444; }
    .stat-subtitle {
        font-size: 0.8rem;
        color: #6b7280;
    }
    .widget-header {
        border-bottom: 1px solid #e5e7eb;
        padding: 1rem 1.25rem;
    }
    .alert-item {
        border-left: 3px solid;
        padding: 0.75rem 1rem;
        margin-bottom: 0.5rem;
        border-radius: 0 8px 8px 0;
        background: #f9fafb;
    }
    .alert-item.critical { border-color: #ef4444; }
    .alert-item.warning { border-color: #f59e0b; }
    .alert-item.info { border-color: #3b82f6; }
    .table-mini td, .table-mini th {
        padding: 0.75rem 0.5rem;
        font-size: 0.85rem;
    }
    .progress-mini {
        height: 6px;
        border-radius: 3px;
    }
    .rank-badge {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
    }
    .rank-1 { background: #fef3c7; color: #d97706; }
    .rank-2 { background: #f3f4f6; color: #6b7280; }
    .rank-3 { background: #fed7aa; color: #ea580c; }
    .metric-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .metric-row:last-child { border-bottom: none; }
</style>

<div class="content-wrapper">
    <!-- KPI Cards Row -->
    <div class="row g-4 mb-4">
        <!-- Products Overview -->
        <div class="col-lg-3 col-6">
            <div class="dashboard-card stat-card bg-white">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-subtitle mb-1">Total Products</p>
                        <h3 class="mb-1 font-weight-bold text-dark">{{ $products['total'] }}</h3>
                        <div class="d-flex gap-3 mt-2">
                            <span class="stat-trend">
                                <span class="text-success">{{ $products['healthy_stock'] }}</span> in stock
                            </span>
                        </div>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10">
                        <i class="fe fe-package text-success"></i>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between text-sm">
                        <span class="text-muted">Out of stock</span>
                        <span class="font-weight-semibold text-danger">{{ $products['out_of_stock'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between text-sm mt-1">
                        <span class="text-muted">Low stock</span>
                        <span class="font-weight-semibold text-warning">{{ $products['low_stock'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="col-lg-3 col-6">
            <a href="{{ route('inventory.products.index', ['filter' => 'low_stock']) }}" class="text-decoration-none">
                <div class="dashboard-card stat-card bg-white position-relative overflow-hidden">
                    @if($lowStock['total'] > 0)
                        <div class="position-absolute top-0 end-0 p-2">
                            <span class="badge badge-danger pulse-badge">Alert</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-subtitle mb-1">Low Stock Alert</p>
                            <h3 class="mb-1 font-weight-bold {{ $lowStock['total'] > 0 ? 'text-danger' : 'text-success' }}">
                                {{ $lowStock['total'] }}
                            </h3>
                            <p class="stat-trend {{ $lowStock['critical'] > 0 ? 'down' : '' }}">items need attention</p>
                        </div>
                        <div class="stat-icon {{ $lowStock['total'] > 0 ? 'bg-danger' : 'bg-success' }} bg-opacity-10">
                            <i class="fe fe-alert-triangle {{ $lowStock['total'] > 0 ? 'text-danger' : 'text-success' }}"></i>
                        </div>
                    </div>
                    @if($lowStock['total'] > 0)
                        <div class="mt-3">
                            <div class="d-flex justify-content-between text-xs text-muted mb-1">
                                <span>Critical (0 stock)</span>
                                <span class="font-weight-semibold">{{ $lowStock['critical'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between text-xs text-muted">
                                <span>Warning (low)</span>
                                <span class="font-weight-semibold">{{ $lowStock['warning'] }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </a>
        </div>

        <!-- Today's Sales -->
        <div class="col-lg-3 col-6">
            <div class="dashboard-card stat-card bg-white">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-subtitle mb-1">Today's Sales</p>
                        <h3 class="mb-1 font-weight-bold text-success">{{ config('app.currency_symbol') }}{{ number_format($todaySales['pos_revenue'], 2) }}</h3>
                        <span class="stat-trend {{ $salesSummary['today_growth'] >= 0 ? 'up' : 'down' }}">
                            <i class="fe {{ $salesSummary['today_growth'] >= 0 ? 'fe-arrow-up-right' : 'fe-arrow-down-right' }}"></i>
                            {{ abs($salesSummary['today_growth']) }}% vs yesterday
                        </span>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10">
                        <i class="fe fe-trending-up text-success"></i>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between text-sm">
                        <span class="text-muted">POS Orders</span>
                        <span class="font-weight-semibold">{{ $todaySales['pos_orders'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between text-sm mt-1">
                        <span class="text-muted">Avg. Order</span>
                        <span class="font-weight-semibold">{{ config('app.currency_symbol') }}{{ number_format($todaySales['avg_order_value'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="col-lg-3 col-6">
            <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="text-decoration-none">
                <div class="dashboard-card stat-card bg-white">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="stat-subtitle mb-1">Pending Orders</p>
                            <h3 class="mb-1 font-weight-bold text-warning">{{ $pendingOrders['total_pending'] }}</h3>
                            <span class="stat-trend">awaiting action</span>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10">
                            <i class="fe fe-clock text-warning"></i>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between text-sm">
                            <span class="text-muted">Online Orders</span>
                            <span class="font-weight-semibold">{{ $pendingOrders['online_pending'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-sm mt-1">
                            <span class="text-muted">Unpaid POS</span>
                            <span class="font-weight-semibold">{{ $pendingOrders['pos_pending'] }}</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Sales Activity Chart -->
        <div class="col-lg-8">
            <div class="dashboard-card bg-white">
                <div class="widget-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 font-weight-semibold">Sales Activity</h5>
                        <p class="text-muted mb-0" style="font-size: 0.8rem;">Revenue & Orders over time</p>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active" data-period="daily">Daily</button>
                        <button type="button" class="btn btn-outline-secondary" data-period="monthly">Monthly</button>
                    </div>
                </div>
                <div class="card-body py-4">
                    <canvas id="salesChart" height="280"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-lg-4">
            <div class="dashboard-card bg-white h-100">
                <div class="widget-header">
                    <h5 class="mb-0 font-weight-semibold">Quick Stats</h5>
                </div>
                <div class="card-body">
                    <!-- Stock Value -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Inventory Value</span>
                            <span class="font-weight-semibold text-primary">{{ config('app.currency_symbol') }}{{ number_format($stockValue['total_retail_value'], 0) }}</span>
                        </div>
                        <div class="progress progress-mini bg-light">
                            <div class="progress-bar bg-primary" style="width: 70%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-1 text-xs text-muted">
                            <span>Cost: {{ config('app.currency_symbol') }}{{ number_format($stockValue['total_cost'], 0) }}</span>
                            <span>{{ number_format($stockValue['total_units']) }} units</span>
                        </div>
                    </div>

                    <!-- Monthly Summary -->
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase text-xs font-weight-semibold mb-3">This Month</h6>
                        <div class="metric-row">
                            <span class="text-muted">Total Revenue</span>
                            <span class="font-weight-semibold text-success">{{ config('app.currency_symbol') }}{{ number_format($salesSummary['this_month'], 2) }}</span>
                        </div>
                        <div class="metric-row">
                            <span class="text-muted">vs Last Month</span>
                            <span class="font-weight-semibold {{ $salesSummary['month_growth'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $salesSummary['month_growth'] >= 0 ? '+' : '' }}{{ $salesSummary['month_growth'] }}%
                            </span>
                        </div>
                        <div class="metric-row">
                            <span class="text-muted">This Year</span>
                            <span class="font-weight-semibold">{{ config('app.currency_symbol') }}{{ number_format($salesSummary['this_year'], 2) }}</span>
                        </div>
                    </div>

                    <!-- Online Sales -->
                    <div>
                        <h6 class="text-muted text-uppercase text-xs font-weight-semibold mb-3">Online Store</h6>
                        <div class="metric-row">
                            <span class="text-muted">Orders</span>
                            <span class="font-weight-semibold">{{ $todaySales['online_orders'] }}</span>
                        </div>
                        <div class="metric-row">
                            <span class="text-muted">Revenue</span>
                            <span class="font-weight-semibold text-info">{{ config('app.currency_symbol') }}{{ number_format($todaySales['online_revenue'], 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts & Top Performers Row -->
    <div class="row g-4 mb-4">
        <!-- Alerts -->
        <div class="col-lg-4">
            <div class="dashboard-card bg-white">
                <div class="widget-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-semibold">
                        <i class="fe fe-bell text-warning mr-1"></i> Alerts
                    </h5>
                    <span class="badge badge-danger">{{ count($alerts) }}</span>
                </div>
                <div class="card-body" style="max-height: 320px; overflow-y: auto;">
                    @forelse($alerts as $alert)
                        <a href="{{ $alert['action'] }}" class="text-decoration-none">
                            <div class="alert-item {{ $alert['type'] }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="{{ $alert['icon'] }} text-{{ $alert['type'] === 'critical' ? 'danger' : ($alert['type'] === 'warning' ? 'warning' : 'info') }} mr-2"></i>
                                        <span class="font-weight-medium text-dark">{{ $alert['title'] }}</span>
                                    </div>
                                    <span class="badge badge-{{ $alert['type'] === 'critical' ? 'danger' : ($alert['type'] === 'warning' ? 'warning' : 'info') }}">
                                        {{ $alert['count'] }}
                                    </span>
                                </div>
                                <p class="text-muted mb-0 mt-1" style="font-size: 0.8rem;">{{ $alert['message'] }}</p>
                            </div>
                        </a>
                    @empty
                        <div class="text-center py-4">
                            <i class="fe fe-check-circle text-success" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2 mb-0">All clear! No alerts at this time.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Top Selling Products -->
        <div class="col-lg-4">
            <div class="dashboard-card bg-white">
                <div class="widget-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-semibold">Top Selling Products</h5>
                    <a href="{{ route('reports.sales') }}" class="btn btn-link btn-sm p-0">View all</a>
                </div>
                <div class="card-body p-0">
                    @forelse($topProducts['products'] as $index => $product)
                        <div class="d-flex align-items-center px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <span class="rank-badge rank-{{ $index + 1 }} mr-3">{{ $index + 1 }}</span>
                            <div class="flex-grow-1 min-w-0">
                                <p class="mb-0 text-truncate font-weight-medium">{{ $product['name'] }}</p>
                                <p class="text-muted mb-0 text-xs">{{ $product['sku'] }}</p>
                            </div>
                            <div class="text-right">
                                <p class="mb-0 font-weight-semibold text-success">{{ $product['total_sold'] }} sold</p>
                                <p class="text-muted mb-0 text-xs">{{ config('app.currency_symbol') }}{{ number_format($product['total_revenue'], 0) }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No sales data available</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="col-lg-4">
            <div class="dashboard-card bg-white">
                <div class="widget-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-semibold">Top Customers</h5>
                    <a href="{{ route('reports.customers') }}" class="btn btn-link btn-sm p-0">View all</a>
                </div>
                <div class="card-body p-0">
                    @forelse($topCustomers['customers'] as $index => $customer)
                        <div class="d-flex align-items-center px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <span class="rank-badge rank-{{ $index + 1 }} mr-3">{{ $index + 1 }}</span>
                            <div class="flex-grow-1 min-w-0">
                                <p class="mb-0 text-truncate font-weight-medium">{{ $customer['name'] }}</p>
                                <p class="text-muted mb-0 text-xs">{{ $customer['order_count'] }} orders</p>
                            </div>
                            <div class="text-right">
                                <p class="mb-0 font-weight-semibold text-primary">{{ config('app.currency_symbol') }}{{ number_format($customer['total_spent'], 0) }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No customer data available</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="row g-4">
        <!-- Recent POS Sales -->
        <div class="col-lg-8">
            <div class="dashboard-card bg-white">
                <div class="widget-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-semibold">Recent POS Sales</h5>
                    <a href="{{ route('pos.sales.index') }}" class="btn btn-link btn-sm p-0">View all</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-mini mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 text-muted text-xs text-uppercase">Sale #</th>
                                    <th class="border-0 text-muted text-xs text-uppercase">Customer</th>
                                    <th class="border-0 text-muted text-xs text-uppercase">Cashier</th>
                                    <th class="border-0 text-muted text-xs text-uppercase">Amount</th>
                                    <th class="border-0 text-muted text-xs text-uppercase">Status</th>
                                    <th class="border-0 text-muted text-xs text-uppercase">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSales['sales'] as $sale)
                                    <tr>
                                        <td>
                                            <a href="{{ route('pos.sales.show', $sale['id']) }}" class="font-weight-medium text-primary">
                                                {{ $sale['sale_number'] }}
                                            </a>
                                        </td>
                                        <td>{{ $sale['customer'] }}</td>
                                        <td class="text-muted">{{ $sale['cashier'] }}</td>
                                        <td class="font-weight-semibold">{{ config('app.currency_symbol') }}{{ number_format($sale['total'], 2) }}</td>
                                        <td>
                                            @if($sale['payment_status'] === 'paid')
                                                <span class="badge badge-success badge-pill px-2">Paid</span>
                                            @elseif($sale['payment_status'] === 'pending')
                                                <span class="badge badge-warning badge-pill px-2">Pending</span>
                                            @else
                                                <span class="badge badge-secondary badge-pill px-2">{{ ucfirst($sale['payment_status']) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-muted text-xs">{{ $sale['time_ago'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No recent sales</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Out of Stock Products -->
        <div class="col-lg-4">
            <div class="dashboard-card bg-white">
                <div class="widget-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-semibold">
                        <i class="fe fe-alert-circle text-danger mr-1"></i> Out of Stock
                    </h5>
                    <span class="badge badge-danger">{{ $outOfStock['count'] ?? 0 }}</span>
                </div>
                <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                    @forelse($lowStock['products']->where('stock_quantity', 0) as $product)
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="flex-grow-1 min-w-0">
                                <p class="mb-0 text-truncate font-weight-medium">{{ $product->name }}</p>
                                <p class="text-danger mb-0 text-xs">SKU: {{ $product->sku }}</p>
                            </div>
                            <span class="badge badge-danger">0 left</span>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="fe fe-check-circle text-success" style="font-size: 1.5rem;"></i>
                            <p class="text-muted mt-2 mb-0" style="font-size: 0.85rem;">All products in stock!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    .pulse-badge {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
</style>
@endsection

@section('js')
<script src="{{URL::asset('assets/plugins/chart.js/Chart.bundle.min.js')}}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    const chartData = {
        labels: @json($salesChart['labels']),
        revenue: @json($salesChart['revenue']),
        orders: @json($salesChart['orders'])
    };

    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Revenue',
                    data: chartData.revenue,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Orders',
                    data: chartData.orders,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += '$' + context.parsed.y.toLocaleString();
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#6b7280' }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    grid: { color: '#f3f4f6' },
                    ticks: {
                        color: '#10b981',
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { color: '#3b82f6' }
                }
            }
        }
    });

    // Period toggle
    document.querySelectorAll('[data-period]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-period]').forEach(b => b.classList.remove('active', 'btn-primary'));
            document.querySelectorAll('[data-period]').forEach(b => b.classList.add('btn-outline-secondary'));
            this.classList.remove('btn-outline-secondary');
            this.classList.add('active', 'btn-primary');

            // Fetch new data
            fetch(`/api/dashboard/widgets/sales_chart?period=${this.dataset.period}`)
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        salesChart.data.labels = response.data.labels;
                        salesChart.data.datasets[0].data = response.data.revenue;
                        salesChart.data.datasets[1].data = response.data.orders;
                        salesChart.update();
                    }
                });
        });
    });

    // Auto-refresh every 60 seconds
    setInterval(function() {
        fetch('/api/dashboard/widgets/refresh', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                widgets: ['todaySales', 'pendingOrders', 'recentSales', 'alerts']
            })
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                console.log('Dashboard refreshed at', response.timestamp);
                // Optionally update specific widgets without page reload
            }
        })
        .catch(err => console.log('Auto-refresh failed:', err));
    }, 60000);
});
</script>
@endsection
