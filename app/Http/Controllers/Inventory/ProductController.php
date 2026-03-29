<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function index()
    {
        $products = Product::with('category', 'warehouse')->latest()->get();
        return view('inventory.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $suppliers  = Supplier::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        return view('inventory.products.create', compact('categories', 'suppliers', 'warehouses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sku'             => 'required|unique:products,sku',
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string',
            'category_id'     => 'nullable|exists:categories,id',
            'supplier_id'     => 'nullable|exists:suppliers,id',
            'warehouse_id'    => 'nullable|exists:warehouses,id',
            'purchase_price'  => 'required|numeric|min:0',
            'selling_price'   => 'required|numeric|min:0',
            'min_stock_alert' => 'integer|min:0',
            'weight'          => 'nullable|numeric',
            'unit'            => 'required|string|max:50',
            'barcode'         => 'nullable|string|max:100',
            'image'           => 'nullable|image|max:2048',
            'is_ecommerce'    => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['slug']         = Str::slug($data['name']);
        $data['is_active']    = $request->boolean('is_active', true);
        $data['is_ecommerce'] = $request->boolean('is_ecommerce');

        $tenant = app(\App\Services\TenantManager::class)->getCurrent();
        if ($tenant && !$tenant->canAddProduct()) {
            return redirect()->back()->with('error', 'You have reached your product limit of ' . $tenant->getProductsUsage()?->capacity_limit . '. Please upgrade your plan.');
        }

        Product::create($data);
        return redirect()->route('inventory.products.index')->with('success', 'Product created.');
    }

    public function show(Product $product)
    {
        $movements = $product->stockMovements()->with('user', 'warehouse')->latest()->get();
        return view('inventory.products.show', compact('product', 'movements'));
    }

    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->get();
        $suppliers  = Supplier::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        return view('inventory.products.edit', compact('product', 'categories', 'suppliers', 'warehouses'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'sku'             => "required|unique:products,sku,{$product->id}",
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string',
            'category_id'     => 'nullable|exists:categories,id',
            'supplier_id'     => 'nullable|exists:suppliers,id',
            'warehouse_id'    => 'nullable|exists:warehouses,id',
            'purchase_price'  => 'required|numeric|min:0',
            'selling_price'   => 'required|numeric|min:0',
            'min_stock_alert' => 'integer|min:0',
            'weight'          => 'nullable|numeric',
            'unit'            => 'required|string|max:50',
            'barcode'         => 'nullable|string|max:100',
            'image'           => 'nullable|image|max:2048',
            'is_ecommerce'    => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) Storage::disk('public')->delete($product->image);
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['slug']         = Str::slug($data['name']);
        $data['is_active']    = $request->boolean('is_active');
        $data['is_ecommerce'] = $request->boolean('is_ecommerce');

        $product->update($data);
        return redirect()->route('inventory.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('inventory.products.index')->with('success', 'Product deleted.');
    }
}
