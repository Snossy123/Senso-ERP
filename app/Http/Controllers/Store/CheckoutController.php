<?php

namespace App\Http\Controllers\Store;

use App\Application\Sales\RecordWebOrderService;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Product;
use App\Models\User;
use App\Modules\StorefrontBuilder\Services\StorefrontRenderer;
use App\Notifications\LowStockAlertNotification;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly StorefrontRenderer $storefrontRenderer,
        private readonly RecordWebOrderService $recordWebOrderService,
    ) {}

    private function getCart(): array
    {
        return session('cart', []);
    }

    public function index()
    {
        $cart = $this->getCart();
        if (empty($cart)) {
            return redirect()->route('store.cart.index')->with('warning', 'Your cart is empty.');
        }

        $customer = Auth::guard('customer')->user();
        $items = [];
        $subtotal = 0;

        foreach ($cart as $id => $item) {
            $product = Product::find($id);
            if ($product) {
                $lineTotal = $product->selling_price * $item['qty'];
                $subtotal += $lineTotal;
                $items[] = ['product' => $product, 'qty' => $item['qty'], 'lineTotal' => $lineTotal];
            }
        }

        $storefrontRender = $this->storefrontRenderer->forPage('checkout');

        if (! session()->has('checkout_idempotency_key')) {
            session(['checkout_idempotency_key' => (string) Str::uuid()]);
        }

        $checkoutIdempotencyKey = session('checkout_idempotency_key');

        return view('store.checkout.index', compact(
            'items',
            'subtotal',
            'customer',
            'storefrontRender',
            'checkoutIdempotencyKey'
        ));
    }

    public function placeOrder(Request $request)
    {
        $cart = $this->getCart();
        if (empty($cart)) {
            return redirect()->route('store.index');
        }

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'shipping_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'payment_method' => 'required|in:cash_on_delivery,online',
            'notes' => 'nullable|string',
            'client_idempotency_key' => 'nullable|string|max:191',
        ]);

        $idempotencyKey = $data['client_idempotency_key']
            ?? session('checkout_idempotency_key');

        $customer = Auth::guard('customer')->user();

        $tenant = app(\App\Services\TenantManager::class)->getCurrent();
        $usage = $tenant?->getOrdersUsage();
        if ($tenant && $usage && $usage->isAtLimit()) {
            return redirect()->back()->with('error', 'Monthly order limit reached for this store. Please contact support.');
        }

        try {
            $result = $this->recordWebOrderService->record($cart, $data, $customer, $idempotencyKey);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        if ($result->duplicate) {
            session(['last_order_number' => $result->order->order_number]);

            return redirect()->route('store.checkout.success')
                ->with('info', 'This order was already placed.');
        }

        $order = $result->order;
        $orderNumber = $order->order_number;

        Activity::logOrder($order);

        if ($customer) {
            $customer->notify(new OrderPlacedNotification($order));
        }

        foreach ($result->lowStockProducts as $product) {
            $admins = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->get();
            foreach ($admins as $admin) {
                $admin->notify(new LowStockAlertNotification($product));
            }
        }

        session()->forget(['cart', 'checkout_idempotency_key']);
        session(['last_order_number' => $orderNumber]);

        return redirect()->route('store.checkout.success');
    }

    public function success()
    {
        $orderNumber = session('last_order_number');
        if (! $orderNumber) {
            return redirect()->route('store.index');
        }
        $storefrontRender = $this->storefrontRenderer->forPage('checkout');

        return view('store.checkout.success', compact('orderNumber', 'storefrontRender'));
    }
}
