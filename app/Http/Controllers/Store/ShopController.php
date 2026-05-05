<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Modules\StorefrontBuilder\Services\StorefrontRenderer;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __construct(private readonly StorefrontRenderer $storefrontRenderer) {}

    public function index(Request $request)
    {
        $query = Product::where('is_ecommerce', true)->where('is_active', true)->with('category');

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $products = $query->latest()->paginate(12)->withQueryString();
        $categories = Category::whereHas('products', fn ($q) => $q->where('is_ecommerce', true))->get();

        $storefrontRender = $this->storefrontRenderer->forPage('shop');

        return view('store.shop.index', compact('products', 'categories', 'storefrontRender'));
    }

    public function show(Product $product)
    {
        abort_if(! $product->is_ecommerce || ! $product->is_active, 404);
        $related = Product::where('is_ecommerce', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();
        $storefrontRender = $this->storefrontRenderer->forPage('product');

        return view('store.shop.show', compact('product', 'related', 'storefrontRender'));
    }
}
