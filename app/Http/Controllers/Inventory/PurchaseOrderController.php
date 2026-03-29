<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function index()
    {
        $orders = PurchaseOrder::with('supplier', 'warehouse', 'creator')->latest()->get();
        return view('inventory.purchase-orders.index', compact('orders'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $products = Product::with('variants')->where('is_active', true)->get();
        return view('inventory.purchase-orders.create', compact('suppliers', 'warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $order = PurchaseOrder::create([
                'supplier_id' => $request->supplier_id,
                'warehouse_id' => $request->warehouse_id,
                'reference_no' => 'PO-' . strtoupper(uniqid()),
                'order_date' => $request->order_date,
                'status' => 'draft',
                'created_by' => Auth::id(),
                'total_amount' => 0,
            ]);

            $total = 0;
            foreach ($request->items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_cost'];
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total' => $lineTotal,
                ]);
                $total += $lineTotal;
            }

            $order->update(['total_amount' => $total]);
        });

        return redirect()->route('inventory.purchase-orders.index')->with('success', 'Purchase order created.');
    }

    public function show(PurchaseOrder $order)
    {
        $order->load('items.product', 'items.variant', 'supplier', 'warehouse');
        return view('inventory.purchase-orders.show', compact('order'));
    }

    public function receive(PurchaseOrder $order)
    {
        if ($order->status === 'received') {
            return redirect()->back()->with('error', 'Order already received.');
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                // 1. Update Warehouse Stock
                $warehouseStock = \App\Models\ProductWarehouseStock::firstOrCreate([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'warehouse_id' => $order->warehouse_id,
                ], [
                    'tenant_id' => $order->tenant_id,
                    'quantity' => 0
                ]);
                $warehouseStock->increment('quantity', $item->quantity);

                // 2. Update Global Product Stock
                $product = $item->product;
                $beforeQty = $product->stock_quantity;
                $product->increment('stock_quantity', $item->quantity);

                // 3. Record Movement
                \App\Models\StockMovement::create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'warehouse_id' => $order->warehouse_id,
                    'purchase_order_id' => $order->id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'before_quantity' => $beforeQty,
                    'after_quantity' => $beforeQty + $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'total_value' => $item->total,
                    'reference' => $order->reference_no,
                    'notes' => 'Received from PO',
                    'user_id' => Auth::id(),
                ]);
            }

            $order->update([
                'status' => 'received',
                'received_at' => now(),
            ]);
        });

        return redirect()->route('inventory.purchase-orders.show', $order)->with('success', 'Order received and stock updated.');
    }
}
