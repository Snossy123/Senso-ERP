<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    private const IMAGE_MAX_KB = 5120;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $products = Product::with('category', 'warehouse')->latest()->get();

        return view('inventory.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $units = Unit::all();

        return view('inventory.products.create', compact('categories', 'suppliers', 'warehouses', 'units'));
    }

    public function store(Request $request)
    {
        if (! $request->boolean('has_variants')) {
            $request->request->remove('variants');
        }

        $data = $request->validate([
            'sku' => 'required|unique:products,sku',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'unit_id' => 'nullable|exists:units,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'min_stock_alert' => 'integer|min:0',
            'weight' => 'nullable|numeric',
            'barcode' => 'nullable|string|max:100',
            'image' => $this->imageValidationRules(),
            'is_ecommerce' => 'boolean',
            'has_variants' => 'boolean',
            'valuation_method' => 'required|in:fifo,average',
            'variants' => 'nullable|array|required_if:has_variants,1',
            'variants.*.name' => 'required_if:has_variants,1|string|max:100',
            'variants.*.sku' => 'required_if:has_variants,1|distinct|unique:product_variants,sku',
        ]);

        $variants = $data['variants'] ?? null;
        unset($data['variants']);

        $data['image'] = $this->storeUploadedImage($request);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_ecommerce'] = $request->boolean('is_ecommerce');
        $data['has_variants'] = $request->boolean('has_variants');

        $tenant = app(\App\Services\TenantManager::class)->getCurrent();
        if ($tenant && ! $tenant->canAddProduct()) {
            return redirect()->back()->with('error', 'You have reached your product limit.');
        }

        DB::transaction(function () use ($data, $variants) {
            $product = Product::create($data);

            if ($product->has_variants && $variants) {
                foreach ($variants as $vData) {
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
        $suppliers = Supplier::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $units = Unit::all();

        $product->load('variants');

        return view('inventory.products.edit', compact('product', 'categories', 'suppliers', 'warehouses', 'units'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'sku' => "required|unique:products,sku,{$product->id}",
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'unit_id' => 'nullable|exists:units,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'min_stock_alert' => 'integer|min:0',
            'weight' => 'nullable|numeric',
            'barcode' => 'nullable|string|max:100',
            'image' => $this->imageValidationRules(),
            'remove_image' => 'nullable|boolean',
            'is_ecommerce' => 'boolean',
            'has_variants' => 'boolean',
            'valuation_method' => 'required|in:fifo,average',
        ]);

        if ($request->boolean('remove_image') || $request->hasFile('image')) {
            $data['image'] = $this->storeUploadedImage($request, $product);
        }

        unset($data['remove_image']);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
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

    /** @return array<int, string> */
    private function imageValidationRules(): array
    {
        return ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:'.self::IMAGE_MAX_KB];
    }

    private function storeUploadedImage(Request $request, ?Product $product = null): ?string
    {
        if ($request->boolean('remove_image')) {
            if ($product?->image) {
                Storage::disk('public')->delete($product->image);
            }

            return null;
        }

        if (! $request->hasFile('image')) {
            return $product?->image;
        }

        $file = $request->file('image');
        $this->assertUploadValid($file);

        Storage::disk('public')->makeDirectory('products');

        if ($product?->image) {
            Storage::disk('public')->delete($product->image);
        }

        return $file->store('products', 'public');
    }

    private function assertUploadValid(UploadedFile $file): void
    {
        if ($file->isValid()) {
            return;
        }

        $message = match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => __('inventory.image_too_large', [
                'max' => (int) (self::IMAGE_MAX_KB / 1024),
            ]),
            default => __('inventory.image_upload_failed'),
        };

        throw ValidationException::withMessages(['image' => [$message]]);
    }
}
