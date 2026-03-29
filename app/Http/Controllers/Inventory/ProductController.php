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
use Illuminate\Support\Facades\DB;
use App\Models\Unit;

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
        $units      = Unit::all();
        return view('inventory.products.create', compact('categories', 'suppliers', 'warehouses', 'units'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sku'              => 'required|unique:products,sku',
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'category_id'      => 'nullable|exists:categories,id',
            'supplier_id'      => 'nullable|exists:suppliers,id',
            'warehouse_id'     => 'nullable|exists:warehouses,id',
            'unit_id'          => 'nullable|exists:units,id',
            'purchase_price'   => 'required|numeric|min:0',
            'selling_price'    => 'required|numeric|min:0',
            'min_stock_alert'  => 'integer|min:0',
            'weight'           => 'nullable|numeric',
            'barcode'          => 'nullable|string|max:100',
            'image'            => 'nullable|image|max:2048',
            'is_ecommerce'     => 'boolean',
            'has_variants'     => 'boolean',
            'valuation_method' => 'required|in:fifo,average',
            'variants'         => 'nullable|array',
            'variants.*.name'  => 'required_with:variants|string|max:100',
            'variants.*.sku'   => 'required_with:variants|unique:product_variants,sku',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['slug']         = Str::slug($data['name']);
        $data['is_active']    = $request->boolean('is_active', true);
        $data['is_ecommerce'] = $request->boolean('is_ecommerce');
        $data['has_variants'] = $request->boolean('has_variants');

        $tenant = app(\App\Services\TenantManager::class)->getCurrent();
        if ($tenant && !$tenant->canAddProduct()) {
            return redirect()->back()->with('error', 'You have reached your product limit.');
        }

        DB::transaction(function () use ($data, $request) {
            $product = Product::create($data);

            if ($product->has_variants && $request->has('variants')) {
                foreach ($request->variants as $vData) {
                    $product->variants()->create($vData);
                }
            }
        });

        return redirect()->route('inventory.products.index')->with('success', 'Product created.');
    }

    public function show(Product $product)
    {
        $movements = $product->stockMovements()->with(['user', 'warehouse', 'variant'])->latest()->get();
        return view('inventory.products.show', compact('product', 'movements'));
    }

    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->get();
        $suppliers  = Supplier::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $units      = Unit::all();
        return view('inventory.products.edit', compact('product', 'categories', 'suppliers', 'warehouses', 'units'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'sku'              => "required|unique:products,sku,{$product->id}",
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'category_id'      => 'nullable|exists:categories,id',
            'supplier_id'      => 'nullable|exists:suppliers,id',
            'warehouse_id'     => 'nullable|exists:warehouses,id',
            'unit_id'          => 'nullable|exists:units,id',
            'purchase_price'   => 'required|numeric|min:0',
            'selling_price'    => 'required|numeric|min:0',
            'min_stock_alert'  => 'integer|min:0',
            'weight'           => 'nullable|numeric',
            'barcode'          => 'nullable|string|max:100',
            'image'            => 'nullable|image|max:2048',
            'is_ecommerce'     => 'boolean',
            'has_variants'     => 'boolean',
            'valuation_method' => 'required|in:fifo,average',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) Storage::disk('public')->delete($product->image);
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['slug']         = Str::slug($data['name']);
        $data['is_active']    = $request->boolean('is_active');
        $data['is_ecommerce'] = $request->boolean('is_ecommerce');
        $data['has_variants'] = $request->boolean('has_variants');

        $product->update($data);
        return redirect()->route('inventory.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('inventory.products.index')->with('success', 'Product deleted.');
    }
}
