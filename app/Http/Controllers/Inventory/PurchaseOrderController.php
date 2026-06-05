<?php

namespace App\Http\Controllers\Inventory;

use App\Application\Inventory\InventoryPostingService;
use App\Application\Inventory\RecordSupplierPaymentService;
use App\Application\Inventory\StockPostingData;
use App\Events\Domain\Inventory\GoodsReceived;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly InventoryPostingService $inventoryPostingService,
        private readonly RecordSupplierPaymentService $recordSupplierPaymentService,
    ) {
        $this->middleware('auth');
    }

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
                'reference_no' => 'PO-'.strtoupper(uniqid()),
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
                $this->inventoryPostingService->postInbound(
                    StockPostingData::forGoodsReceipt(
                        tenantId: (int) $order->tenant_id,
                        productId: $item->product_id,
                        productVariantId: $item->product_variant_id,
                        warehouseId: (int) $order->warehouse_id,
                        quantity: $item->quantity,
                        unitCost: (float) $item->unit_cost,
                        totalValue: (float) $item->total,
                        reference: $order->reference_no,
                        notes: 'Received from PO',
                        userId: Auth::id(),
                        purchaseOrderId: $order->id,
                    )
                );
            }

            $order->update([
                'status' => 'received',
                'received_at' => now(),
            ]);

            $tenantId = (int) $order->tenant_id;
            $orderId = (int) $order->id;

            DB::afterCommit(function () use ($orderId, $tenantId) {
                event(new GoodsReceived(
                    purchaseOrderId: $orderId,
                    tenantId: $tenantId,
                ));
            });
        });

        return redirect()->route('inventory.purchase-orders.show', $order)->with('success', 'Order received and stock updated.');
    }

    public function pay(Request $request, PurchaseOrder $order)
    {
        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->hasPermission('accounting.disburse')) {
            return redirect()->back()->with('error', 'You do not have permission to record supplier payments.');
        }

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->recordSupplierPaymentService->record($order, $validated, (int) $user->id);

            return redirect()
                ->route('inventory.purchase-orders.show', $order)
                ->with('success', 'Supplier payment recorded and AP cleared.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
