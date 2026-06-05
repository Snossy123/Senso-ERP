<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Modules\StorefrontBuilder\Services\StorefrontRenderer;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly StorefrontRenderer $storefrontRenderer) {}

    private function getCart(): array
    {
        return session('cart', []);
    }

    private function saveCart(array $cart): void
    {
        session(['cart' => $cart]);
    }

    public function index()
    {
        $cart = $this->getCart();
        $products = [];
        $subtotal = 0;

        foreach ($cart as $id => $item) {
            $product = Product::find($id);
            if ($product) {
                $lineTotal = $product->selling_price * $item['qty'];
                $subtotal += $lineTotal;
                $products[] = [
                    'product' => $product,
                    'qty' => $item['qty'],
                    'lineTotal' => $lineTotal,
                ];
            }
        }

        $storefrontRender = $this->storefrontRenderer->forPage('cart');

        return view('store.cart.index', compact('products', 'subtotal', 'storefrontRender'));
    }

    public function add(Request $request, Product $product)
    {
        abort_if(! $product->is_ecommerce || ! $product->is_active, 404);
        $qty = max(1, (int) $request->input('qty', 1));
        $cart = $this->getCart();
        $available = max(0, (int) $product->stock_quantity);
        if ($available < 1) {
            return redirect()->back()->with('error', "'{$product->name}' is out of stock.");
        }

        $requested = ($cart[$product->id]['qty'] ?? 0) + $qty;
        $cart[$product->id] = ['qty' => min($requested, $available)];
        $this->saveCart($cart);

        $message = $requested > $available
            ? "'{$product->name}' added to cart (limited to available stock: {$available})."
            : "'{$product->name}' added to cart.";

        return redirect()->back()->with('success', $message);
    }

    public function update(Request $request, Product $product)
    {
        $qty = max(0, (int) $request->input('qty', 1));
        $cart = $this->getCart();
        $available = max(0, (int) $product->stock_quantity);

        if ($qty === 0) {
            unset($cart[$product->id]);
        } else {
            $cart[$product->id] = ['qty' => min($qty, $available)];
        }
        $this->saveCart($cart);

        $message = $qty > 0 && $qty > $available
            ? "Cart updated (quantity capped at available stock: {$available})."
            : 'Cart updated.';

        return redirect()->route('store.cart.index')->with('success', $message);
    }

    public function remove(Product $product)
    {
        $cart = $this->getCart();
        unset($cart[$product->id]);
        $this->saveCart($cart);

        return redirect()->route('store.cart.index')->with('success', 'Item removed from cart.');
    }
}
