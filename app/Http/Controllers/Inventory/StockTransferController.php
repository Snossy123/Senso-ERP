<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\ProductWarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function index()
    {
        $transfers = StockTransfer::with('fromWarehouse', 'toWarehouse', 'creator')->latest()->get();
        return view('inventory.transfers.index', compact('transfers'));
    }

    public function create()
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        $products = Product::with('variants')->where('is_active', true)->get();
        return view('inventory.transfers.create', compact('warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'transfer_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            $transfer = StockTransfer::create([
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id' => $request->to_warehouse_id,
                'reference_no' => 'TR-' . strtoupper(uniqid()),
                'transfer_date' => $request->transfer_date,
                'status' => 'completed',
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                $variantId = $item['product_variant_id'] ?? null;
                
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $variantId,
                    'quantity' => $item['quantity'],
                ]);

                // 1. Decrement From Warehouse
                $fromStock = ProductWarehouseStock::where([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $variantId,
                    'warehouse_id' => $request->from_warehouse_id,
                ])->first();
                if ($fromStock) {
                    $fromStock->decrement('quantity', $item['quantity']);
                }

                // 2. Increment To Warehouse
                $toStock = ProductWarehouseStock::firstOrCreate([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $variantId,
                    'warehouse_id' => $request->to_warehouse_id,
                ], [
                    'tenant_id' => Auth::user()->tenant_id,
                    'quantity' => 0
                ]);
                $toStock->increment('quantity', $item['quantity']);

                // 3. Record Movements
                // OUT from origin
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $variantId,
                    'warehouse_id' => $request->from_warehouse_id,
                    'stock_transfer_id' => $transfer->id,
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'reference' => $transfer->reference_no,
                    'notes' => 'Stock Transfer (Out)',
                    'user_id' => Auth::id(),
                ]);

                // IN to destination
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $variantId,
                    'warehouse_id' => $request->to_warehouse_id,
                    'stock_transfer_id' => $transfer->id,
                    'type' => 'in',
                    'quantity' => $item['quantity'],
                    'reference' => $transfer->reference_no,
                    'notes' => 'Stock Transfer (In)',
                    'user_id' => Auth::id(),
                ]);
            }
        });

        return redirect()->route('inventory.transfers.index')->with('success', 'Stock transfer completed.');
    }

    public function show(StockTransfer $transfer)
    {
        $transfer->load('items.product', 'items.variant', 'fromWarehouse', 'toWarehouse');
        return view('inventory.transfers.show', compact('transfer'));
    }
}
