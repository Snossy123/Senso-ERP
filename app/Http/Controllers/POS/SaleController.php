<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleRefund;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Customer;
use App\Models\PosShift;
use App\Models\ProductWarehouseStock;
use App\Notifications\LowStockAlertNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function index(Request $request)
    {
        $query = Sale::with('user', 'customer')->orderBy('created_at', 'desc');

        if ($request->filled('cashier_id')) {
            $query->where('user_id', $request->cashier_id);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $sales = $query->paginate(20)->withQueryString();
        $cashiers = User::pluck('name', 'id');

        return view('pos.sales.index', compact('sales', 'cashiers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'                   => 'required|array|min:1',
            'items.*.id'              => 'required|exists:products,id',
            'items.*.qty'             => 'required|integer|min:1',
            'items.*.price'           => 'required|numeric|min:0',
            'items.*.discount_pct'    => 'nullable|numeric|min:0|max:100',
            'payment_method'          => 'required|in:cash,card,bank_transfer,split',
            'discount'                => 'nullable|numeric|min:0',
            'tax_rate'                => 'nullable|numeric|min:0|max:100',
            'amount_tendered'         => 'nullable|numeric|min:0',
            'customer_id'             => 'nullable|exists:customers,id',
            'customer_name'           => 'nullable|string|max:120',
        ]);

        // Tenant plan checks
        $tenant = app(\App\Services\TenantManager::class)->getCurrent();
        if ($tenant && !$tenant->hasFeature('pos')) {
            return response()->json(['success' => false, 'error' => 'POS feature is not enabled for your plan.'], 403);
        }
        $usage = $tenant?->getOrdersUsage();
        if ($tenant && $usage && $usage->isAtLimit()) {
            return response()->json(['success' => false, 'error' => 'Monthly order limit reached. Please upgrade your plan.'], 403);
        }

        $lowStockProducts = [];
        $saleId = null;

        DB::transaction(function () use ($request, &$lowStockProducts, &$saleId) {
            $items       = $request->input('items');
            $discountAmt = (float) $request->input('discount', 0);
            $taxRate     = (float) $request->input('tax_rate', 0);
            $subtotal    = 0;

            // Validate stock availabilty before any write
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['id']);
                if ($product->stock_quantity < $item['qty']) {
                    throw new \Exception("Insufficient stock for product: {$product->name}. Available: {$product->stock_quantity}");
                }
            }

            // Calculate totals
            foreach ($items as $item) {
                $itemDiscount = isset($item['discount_pct']) ? ($item['price'] * $item['qty'] * $item['discount_pct'] / 100) : 0;
                $subtotal += ($item['price'] * $item['qty']) - $itemDiscount;
            }

            $taxAmount      = round(($subtotal - $discountAmt) * $taxRate / 100, 2);
            $total          = round($subtotal - $discountAmt + $taxAmount, 2);
            $amountTendered = (float) $request->input('amount_tendered', $total);
            $changeDue      = max(0, $amountTendered - $total);

            $sale = Sale::create([
                'sale_number'     => Sale::generateSaleNumber(),
                'customer_id'     => $request->input('customer_id'),
                'customer_name'   => $request->input('customer_name'),
                'user_id'         => Auth::id(),
                'shift_id'        => $request->input('shift_id'),
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmt,
                'tax_amount'      => $taxAmount,
                'total'           => $total,
                'payment_method'  => $request->input('payment_method'),
                'payment_status'  => 'paid',
                'amount_tendered' => $amountTendered,
                'change_due'      => $changeDue,
                'status'          => 'completed',
                'notes'           => $request->input('notes'),
            ]);

            $shift = PosShift::find($request->input('shift_id'));

            foreach ($items as $item) {
                $product     = Product::lockForUpdate()->findOrFail($item['id']);
                $variantId   = $item['variant_id'] ?? null;
                $discountPct = (float) ($item['discount_pct'] ?? 0);
                $lineTotal   = $item['price'] * $item['qty'];
                $lineDisc    = $lineTotal * $discountPct / 100;

                SaleItem::create([
                    'sale_id'            => $sale->id,
                    'product_id'         => $product->id,
                    'product_variant_id' => $variantId,
                    'quantity'           => $item['qty'],
                    'unit_price'         => $item['price'],
                    'discount_pct'       => $discountPct,
                    'discount_amount'    => $lineDisc,
                    'discount'           => $lineDisc,
                    'total'              => $lineTotal - $lineDisc,
                ]);

                // 1. Decrement Global Stock
                $product->decrement('stock_quantity', $item['qty']);
                $product->refresh();

                // 2. Decrement Warehouse/Variant Stock
                if ($shift && $shift->warehouse_id) {
                    ProductWarehouseStock::updateOrCreate(
                        [
                            'product_id'         => $product->id,
                            'product_variant_id' => $variantId,
                            'warehouse_id'       => $shift->warehouse_id,
                        ],
                        ['tenant_id' => $product->tenant_id]
                    )->decrement('quantity', $item['qty']);
                }

                if ($product->stock_quantity <= $product->min_stock_alert) {
                    $lowStockProducts[] = $product;
                }

                $beforeQty = $product->stock_quantity + $item['qty'];

                StockMovement::create([
                    'product_id'         => $product->id,
                    'product_variant_id' => $variantId,
                    'warehouse_id'       => $shift?->warehouse_id,
                    'type'               => 'out',
                    'quantity'           => $item['qty'],
                    'before_quantity'    => $beforeQty,
                    'after_quantity'     => $product->stock_quantity,
                    'unit_cost'          => $product->purchase_price,
                    'total_value'        => $item['qty'] * $product->purchase_price,
                    'reference'          => $sale->sale_number,
                    'notes'              => 'POS Sale',
                    'user_id'            => Auth::id(),
                ]);
            }

            $saleId = $sale->id;
        });

        // Post-transaction: notifications
        foreach ($lowStockProducts as $product) {
            $admins = User::whereHas('role', fn($q) => $q->where('slug', 'admin'))->get();
            foreach ($admins as $admin) {
                try { $admin->notify(new LowStockAlertNotification($product)); } catch (\Throwable) {}
            }
        }

        \App\Models\Activity::logSale(Sale::find($saleId));

        return response()->json([
            'success'    => true,
            'sale_id'    => $saleId,
            'change_due' => Sale::find($saleId)?->change_due,
        ]);
    }

    public function show(Sale $sale)
    {
        $sale->load('items.product', 'customer', 'user', 'refunds');
        return view('pos.sales.show', compact('sale'));
    }

    // ── Void Sale ─────────────────────────────────────────────────────────────────

    public function void(Request $request, Sale $sale)
    {
        $request->validate(['reason' => 'required|string|max:255']);

        if ($sale->isVoided()) {
            return response()->json(['error' => 'Sale is already voided.'], 422);
        }

        DB::transaction(function () use ($sale, $request) {
            // Restore stock
            foreach ($sale->items as $item) {
                $item->product->increment('stock_quantity', $item->quantity);
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'type'       => 'in',
                    'quantity'   => $item->quantity,
                    'reference'  => 'VOID-' . $sale->sale_number,
                    'notes'      => 'Sale Voided: ' . $request->reason,
                    'user_id'    => Auth::id(),
                ]);
            }

            $sale->void($request->reason, Auth::id());
        });

        return response()->json(['success' => true]);
    }

    // ── Refund / Return ──────────────────────────────────────────────────────────

    public function refund(Request $request, Sale $sale)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $sale->total,
            'reason' => 'required|string|max:255',
            'method' => 'required|in:original,cash,credit',
        ]);

        if ($sale->isVoided()) {
            return response()->json(['error' => 'Cannot refund a voided sale.'], 422);
        }

        DB::transaction(function () use ($sale, $request) {
            SaleRefund::create([
                'sale_id'       => $sale->id,
                'user_id'       => Auth::id(),
                'refund_number' => SaleRefund::generateRefundNumber(),
                'amount'        => $request->amount,
                'reason'        => $request->reason,
                'method'        => $request->method,
            ]);

            // If full refund, mark the sale
            $totalRefunded = $sale->refunds()->sum('amount') + $request->amount;
            if ($totalRefunded >= $sale->total) {
                $sale->update(['status' => 'refunded']);
            }
        });

        return response()->json(['success' => true]);
    }
}
