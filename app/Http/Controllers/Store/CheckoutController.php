<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Activity;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\LowStockAlertNotification;
use App\Modules\StorefrontBuilder\Services\StorefrontRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct(private readonly StorefrontRenderer $storefrontRenderer)
    {
    }

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
        $items    = [];
        $subtotal = 0;

        foreach ($cart as $id => $item) {
            $product = Product::find($id);
            if ($product) {
                $lineTotal  = $product->selling_price * $item['qty'];
                $subtotal  += $lineTotal;
                $items[]    = ['product' => $product, 'qty' => $item['qty'], 'lineTotal' => $lineTotal];
            }
        }

        $storefrontRender = $this->storefrontRenderer->forPage('checkout');
        return view('store.checkout.index', compact('items', 'subtotal', 'customer', 'storefrontRender'));
    }

    public function placeOrder(Request $request)
    {
        $cart = $this->getCart();
        if (empty($cart)) {
            return redirect()->route('store.index');
        }

        $data = $request->validate([
            'customer_name'    => 'required|string|max:255',
            'customer_email'   => 'nullable|email|max:255',
            'customer_phone'   => 'nullable|string|max:50',
            'shipping_address' => 'nullable|string',
            'city'             => 'nullable|string|max:100',
            'payment_method'   => 'required|in:cash_on_delivery,online',
            'notes'            => 'nullable|string',
        ]);

        $customer    = Auth::guard('customer')->user();
        $orderNumber = null;

        $order = null;
        $lowStockProducts = [];

        $tenant = app(\App\Services\TenantManager::class)->getCurrent();
        $usage = $tenant?->getOrdersUsage();
        if ($tenant && $usage && $usage->isAtLimit()) {
            return redirect()->back()->with('error', 'Monthly order limit reached for this store. Please contact support.');
        }

        DB::transaction(function () use ($cart, $data, $customer, &$orderNumber, &$order, &$lowStockProducts) {
            $subtotal = 0;
            $lines    = [];

            foreach ($cart as $id => $item) {
                $product    = Product::lockForUpdate()->findOrFail($id);
                $lineTotal  = $product->selling_price * $item['qty'];
                $subtotal  += $lineTotal;
                $lines[]    = ['product' => $product, 'qty' => $item['qty'], 'lineTotal' => $lineTotal];
            }

            $order = Order::create([
                'order_number'     => Order::generateOrderNumber(),
                'customer_id'      => $customer?->id,
                'customer_name'    => $data['customer_name'],
                'customer_email'   => $data['customer_email'],
                'customer_phone'   => $data['customer_phone'],
                'shipping_address' => $data['shipping_address'],
                'city'             => $data['city'],
                'subtotal'         => $subtotal,
                'total'            => $subtotal,
                'payment_method'   => $data['payment_method'],
                'payment_status'   => 'pending',
                'status'           => 'pending',
                'notes'            => $data['notes'],
            ]);

            foreach ($lines as $line) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'quantity'     => $line['qty'],
                    'unit_price'   => $line['product']->selling_price,
                    'total'        => $line['lineTotal'],
                ]);

                $product = $line['product'];
                $newStock = $product->stock_quantity - $line['qty'];
                
                if ($newStock <= $product->min_stock_alert) {
                    $lowStockProducts[] = $product;
                }

                $product->decrement('stock_quantity', $line['qty']);
                StockMovement::create([
                    'product_id' => $product->id,
                    'type'       => 'out',
                    'quantity'   => $line['qty'],
                    'reference'  => $order->order_number,
                    'notes'      => 'Ecommerce Order',
                ]);
            }

            $orderNumber = $order->order_number;
        });

        if ($order) {
            $order->load('items');
            
            Activity::logOrder($order);
            
            if ($customer) {
                $customer->notify(new OrderPlacedNotification($order));
            }
        }

        foreach ($lowStockProducts as $product) {
            $admins = User::whereHas('role', fn($q) => $q->where('slug', 'admin'))->get();
            foreach ($admins as $admin) {
                $admin->notify(new LowStockAlertNotification($product));
            }
        }

        session()->forget('cart');
        session(['last_order_number' => $orderNumber]);

        return redirect()->route('store.checkout.success');
    }

    public function success()
    {
        $orderNumber = session('last_order_number');
        if (!$orderNumber) {
            return redirect()->route('store.index');
        }
        $storefrontRender = $this->storefrontRenderer->forPage('checkout');
        return view('store.checkout.success', compact('orderNumber', 'storefrontRender'));
    }
}
