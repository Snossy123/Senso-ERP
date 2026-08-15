<?php

namespace App\Http\Controllers\Admin;

use App\Application\Sales\RecordCustomerPaymentService;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShippingIntegration;
use App\Services\Shipping\ShippingRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(
        private readonly RecordCustomerPaymentService $recordCustomerPaymentService,
        private readonly ShippingRateService $shippingRates,
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Order::with('customer')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($query) use ($q) {
                $query->where('order_number', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%");
            });
        }
        $orders = $query->paginate(20)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('items.product', 'customer', 'shipment');
        $shippingIntegration = ShippingIntegration::query()->first();
        $shippingRates = $this->shippingRates->activeRates();

        return view('admin.orders.show', compact('order', 'shippingIntegration', 'shippingRates'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);
        $order->update(['status' => $request->status]);

        return back()->with('success', 'Order status updated.');
    }

    public function markPaid(Request $request, Order $order)
    {
        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->hasPermission('accounting.collect')) {
            return back()->with('error', 'You do not have permission to record customer payments.');
        }

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,card,bank_transfer',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($order->payment_status === 'paid') {
            return back()->with('warning', 'Order is already marked as paid.');
        }

        try {
            $this->recordCustomerPaymentService->record($order, $validated, (int) $user->id);

            return back()->with('success', 'Customer payment recorded. Cash receipt posted to GL.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
