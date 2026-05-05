<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Modules\StorefrontBuilder\Services\StorefrontRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function __construct(private readonly StorefrontRenderer $storefrontRenderer)
    {
        $this->middleware('auth:customer');
    }

    public function dashboard()
    {
        $customer = Auth::guard('customer')->user();
        $recentOrders = Order::where('customer_id', $customer->id)->latest()->limit(5)->get();
        $totalOrders = Order::where('customer_id', $customer->id)->count();
        $storefrontRender = $this->storefrontRenderer->forPage('account');

        return view('store.account.dashboard', compact('customer', 'recentOrders', 'totalOrders', 'storefrontRender'));
    }

    public function orders()
    {
        $customer = Auth::guard('customer')->user();
        $orders = Order::where('customer_id', $customer->id)->latest()->paginate(10);
        $storefrontRender = $this->storefrontRenderer->forPage('account');

        return view('store.account.orders.index', compact('orders', 'storefrontRender'));
    }

    public function orderDetail(Order $order)
    {
        $customer = Auth::guard('customer')->user();
        abort_if($order->customer_id !== $customer->id, 403);
        $order->load('items.product');
        $storefrontRender = $this->storefrontRenderer->forPage('account');

        return view('store.account.orders.show', compact('order', 'storefrontRender'));
    }

    public function profile()
    {
        $customer = Auth::guard('customer')->user();
        $storefrontRender = $this->storefrontRenderer->forPage('account');

        return view('store.account.profile', compact('customer', 'storefrontRender'));
    }

    public function updateProfile(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'password' => 'nullable|min:8|confirmed',
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $customer->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }
}
