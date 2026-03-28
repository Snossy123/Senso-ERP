<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $data = [
            'products' => $this->dashboardService->getProductsOverview(),
            'todaySales' => $this->dashboardService->getTodaySales(),
            'pendingOrders' => $this->dashboardService->getPendingOrders(),
            'lowStock' => $this->dashboardService->getLowStockAlert(),
            'stockValue' => $this->dashboardService->getStockValue(),
            'recentSales' => $this->dashboardService->getRecentSales(),
            'topProducts' => $this->dashboardService->getTopSellingProducts(),
            'topCustomers' => $this->dashboardService->getTopCustomers(),
            'salesChart' => $this->dashboardService->getSalesChart(),
            'salesSummary' => $this->dashboardService->getSalesSummary(),
            'alerts' => $this->dashboardService->getAlerts(),
        ];

        return view('dashboard.index', $data);
    }

    public function widget(string $widget): JsonResponse
    {
        try {
            $data = $this->dashboardService->getWidget($widget);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        $widgets = $request->input('widgets', [
            'products', 'todaySales', 'pendingOrders', 'lowStock', 'stockValue',
            'recentSales', 'topProducts', 'topCustomers', 'salesChart', 'alerts'
        ]);

        $data = [];
        foreach ($widgets as $widget) {
            try {
                $data[$widget] = $this->dashboardService->getWidget($widget);
            } catch (\InvalidArgumentException $e) {
                $data[$widget] = null;
            }
        }

        return response()->json(['success' => true, 'data' => $data, 'timestamp' => now()->toIso8601String()]);
    }
}
