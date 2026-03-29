<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function index()
    {
        $movements = StockMovement::with('product', 'variant', 'warehouse', 'user')->latest()->get();
        return view('inventory.stock-movements.index', compact('movements'));
    }

    public function create()
    {
        $products   = Product::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        return view('inventory.stock-movements.create', compact('products', 'warehouses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'   => 'required|exists:products,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'type'         => 'required|in:in,out,adjustment',
            'quantity'     => 'required|integer|min:1',
            'notes'        => 'nullable|string',
            'reference'    => 'nullable|string|max:100',
        ]);

        $data['user_id'] = Auth::id();

        DB::transaction(function () use ($data) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);

            StockMovement::create($data);

            if ($data['type'] === 'in') {
                $product->increment('stock_quantity', $data['quantity']);
            } elseif ($data['type'] === 'out') {
                $product->decrement('stock_quantity', $data['quantity']);
            } else {
                // adjustment: set absolute value
                $product->update(['stock_quantity' => $data['quantity']]);
            }
        });

        return redirect()->route('inventory.movements.index')->with('success', 'Stock movement recorded.');
    }
}
