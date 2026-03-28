<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;

class POSController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function terminal()
    {
        $categories = Category::where('is_active', true)->get();
        $products   = Product::where('is_active', true)
                        ->where('stock_quantity', '>', 0)
                        ->with('category')
                        ->get()
                        ->map(fn($p) => [
                            'id'       => $p->id,
                            'name'     => $p->name,
                            'sku'      => $p->sku,
                            'price'    => (float) $p->selling_price,
                            'stock'    => $p->stock_quantity,
                            'category' => $p->category?->name,
                            'image'    => $p->image_url,
                        ]);

        return view('pos.terminal', compact('categories', 'products'));
    }
}
