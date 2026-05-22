<?php

namespace App\Http\Controllers\Accounting\Web;

use App\Application\Sales\RecordCustomerPaymentService;
use App\Http\Controllers\Controller;
use App\Models\CustomerPayment;
use App\Models\Order;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerReceiptController extends Controller
{
    public function __construct(
        private readonly RecordCustomerPaymentService $recordCustomerPaymentService
    ) {}

    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        $collectibleOrders = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('payment_status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->with('customer')
            ->orderByDesc('created_at')
            ->get();

        $creditSales = Sale::query()
            ->where('tenant_id', $tenantId)
            ->where('payment_method', 'credit')
            ->where('payment_status', 'pending')
            ->where('status', 'completed')
            ->with('customer')
            ->orderByDesc('created_at')
            ->get();

        $recentReceipts = CustomerPayment::query()
            ->where('tenant_id', $tenantId)
            ->with(['order', 'sale', 'customer'])
            ->latest('payment_date')
            ->limit(20)
            ->get();

        return view('accounting.customer-receipts.index', compact(
            'collectibleOrders',
            'creditSales',
            'recentReceipts',
        ));
    }

    public function collect(Request $request, Order $order)
    {
        if ($order->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->hasPermission('accounting.collect')) {
            abort(403, 'You do not have permission to record customer payments.');
        }

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,card,bank_transfer',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->recordCustomerPaymentService->record($order, $validated, (int) $user->id);

            return redirect()
                ->route('accounting.customer-receipts')
                ->with('success', "Payment recorded for order {$order->order_number}.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function collectSale(Request $request, Sale $sale)
    {
        if ($sale->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->hasPermission('accounting.collect')) {
            abort(403, 'You do not have permission to record customer payments.');
        }

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,card,bank_transfer',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->recordCustomerPaymentService->recordForSale($sale, $validated, (int) $user->id);

            return redirect()
                ->route('accounting.customer-receipts')
                ->with('success', "Payment recorded for sale {$sale->sale_number}.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
