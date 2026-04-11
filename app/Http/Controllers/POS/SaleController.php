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
            'shift_id'                => 'required|exists:pos_shifts,id',
        ]);

        // Resolve Tenant
        $tenant = app(\App\Services\TenantManager::class)->getCurrent() ?? Auth::user()->tenant;
        
        if (!$tenant) {
            return response()->json(['success' => false, 'error' => 'Tenant context not found.'], 403);
        }

        if (!$tenant->hasFeature('pos')) {
            return response()->json(['success' => false, 'error' => 'POS feature is not enabled for your plan.'], 403);
        }
        $usage = $tenant->getOrdersUsage();
        if ($usage && $usage->isAtLimit()) {
            return response()->json(['success' => false, 'error' => 'Monthly order limit reached. Please upgrade your plan.'], 403);
        }

        $lowStockProducts = [];
        $saleId = null;

        DB::transaction(function () use ($request, &$lowStockProducts, &$saleId, $tenant) {
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
                $itemDiscountPct = (float) ($item['discount_pct'] ?? 0);
                
                // Item-level discount permission check
                if ($itemDiscountPct > 0 && !Auth::user()->hasPermission('pos.discount')) {
                    $maxItemDisc = (float) \App\Models\Setting::get('pos_max_discount_no_perm', 0, $tenant->id);
                    if ($itemDiscountPct > $maxItemDisc) {
                        throw new \Exception("You do not have permission to apply {$itemDiscountPct}% discount on {$product->name}. Max allowed: {$maxItemDisc}%");
                    }
                }

                $itemDiscount = ($item['price'] * $item['qty'] * $itemDiscountPct / 100);
                $subtotal += ($item['price'] * $item['qty']) - $itemDiscount;
            }

            $taxAmount      = round(($subtotal - $discountAmt) * $taxRate / 100, 2);
            $total          = round($subtotal - $discountAmt + $taxAmount, 2);

            // Order-level discount permission check
            if ($discountAmt > 0 && !Auth::user()->hasPermission('pos.discount')) {
                $maxOrderDiscPct = (float) \App\Models\Setting::get('pos_max_order_discount_pct_no_perm', 5, $tenant->id);
                $orderDiscPct = ($discountAmt / $subtotal) * 100;
                if ($orderDiscPct > $maxOrderDiscPct) {
                    throw new \Exception("Order discount exceeds your limit of {$maxOrderDiscPct}%");
                }
            }

            $amountTendered = (float) $request->input('amount_tendered', $total);
            $changeDue      = max(0, $amountTendered - $total);

            $sale = Sale::create([
                'tenant_id'       => $tenant->id,
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
                    'tenant_id'          => $tenant->id,
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
                    'tenant_id'          => $tenant->id,
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
                    'tenant_id'          => $tenant->id,
                ]);
            }

            $saleId = $sale->id;

            // ── Create Journal Entry ─────────────────────────────────────
            try {
                $generator = \App\Services\Accounting\JournalEntryFactory::getGenerator($sale);
                $jeData = $generator->generate($sale);
                
                app(\App\Services\AccountingService::class)->createJournalEntry(
                    $jeData['header'],
                    $jeData['lines']
                );
            } catch (\Exception $e) {
                // If accounting fails, we might still want to record the sale, 
                // but usually in ERPs this should be atomic. 
                // For now, we'll throw to rollback and ensure data integrity.
                throw new \Exception("Accounting integration failed: " . $e->getMessage());
            }
        });

        // Post-transaction: notifications
        foreach ($lowStockProducts as $product) {
            $admins = User::whereHas('role', fn($q) => $q->where('slug', 'admin'))->get();
            foreach ($admins as $admin) {
                try { $admin->notify(new LowStockAlertNotification($product)); } catch (\Throwable) {}
            }
        }

        $sale = Sale::find($saleId);
        \App\Models\Activity::logSale($sale);

        return response()->json([
            'success'    => true,
            'sale_id'    => $saleId,
            'change_due' => $sale?->change_due,
            'sale_number' => $sale?->sale_number,
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
                    'tenant_id'  => $sale->tenant_id,
                    'product_id' => $item->product_id,
                    'type'       => 'in',
                    'quantity'   => $item->quantity,
                    'reference'  => 'VOID-' . $sale->sale_number,
                    'notes'      => 'Sale Voided: ' . $request->reason,
                    'user_id'    => Auth::id(),
                ]);
            }

            $sale->void($request->reason, Auth::id());

            \App\Models\Activity::log(
                'pos',
                'void',
                "Voided Sale #{$sale->sale_number}. Reason: {$request->reason}",
                ['sale_id' => $sale->id],
                $sale,
                'danger'
            );
        });

        return response()->json(['success' => true]);
    }

    // ── Refund / Return ──────────────────────────────────────────────────────────

    public function refund(Request $request, Sale $sale)
    {
        $request->validate([
            'amount'   => 'required|numeric|min:0.01|max:' . $sale->total,
            'reason'   => 'required|string|max:255',
            'method'   => 'required|in:original,cash,credit',
            'restock'  => 'nullable|boolean',
        ]);

        if (!Auth::user()->hasPermission('pos.refund')) {
            return response()->json(['error' => 'You do not have permission to process refunds.'], 403);
        }

        if ($sale->isVoided()) {
            return response()->json(['error' => 'Cannot refund a voided sale.'], 422);
        }

        $tenant = app(\App\Services\TenantManager::class)->getCurrent() ?? Auth::user()->tenant;
        $restock = (bool) $request->input('restock', true);

        DB::transaction(function () use ($sale, $request, $tenant, $restock) {
            $refund = SaleRefund::create([
                'tenant_id'     => $tenant?->id ?? $sale->tenant_id,
                'sale_id'       => $sale->id,
                'user_id'       => Auth::id(),
                'refund_number' => SaleRefund::generateRefundNumber(),
                'amount'        => $request->amount,
                'reason'        => $request->reason,
                'method'        => $request->method,
            ]);

            // Restore stock pro-rated by refund amount ratio
            if ($restock && $sale->total > 0) {
                $ratio = $request->amount / $sale->total;
                foreach ($sale->items as $saleItem) {
                    $restoreQty = (int) round($saleItem->quantity * $ratio);
                    if ($restoreQty <= 0) continue;

                    $product = Product::find($saleItem->product_id);
                    if (!$product) continue;

                    $before = $product->stock_quantity;
                    $product->increment('stock_quantity', $restoreQty);

                    StockMovement::create([
                        'tenant_id'          => $tenant?->id ?? $sale->tenant_id,
                        'product_id'         => $saleItem->product_id,
                        'product_variant_id' => $saleItem->product_variant_id,
                        'type'               => 'in',
                        'quantity'           => $restoreQty,
                        'before_quantity'    => $before,
                        'after_quantity'     => $before + $restoreQty,
                        'reference'          => 'REF-' . $refund->refund_number,
                        'notes'              => 'Refund: ' . $request->reason,
                        'user_id'            => Auth::id(),
                    ]);
                }
            }

            // If full refund, mark the sale
            $totalRefunded = $sale->refunds()->sum('amount') + $request->amount;
            if ($totalRefunded >= $sale->total) {
                $sale->update(['status' => 'refunded']);
            }

            // ── Create Journal Entry ─────────────────────────────────────
            try {
                $generator = \App\Services\Accounting\JournalEntryFactory::getGenerator($refund);
                $jeData = $generator->generate($refund);
                
                app(\App\Services\AccountingService::class)->createJournalEntry(
                    $jeData['header'],
                    $jeData['lines']
                );
            } catch (\Exception $e) {
                throw new \Exception("Accounting integration failed for refund: " . $e->getMessage());
            }

            \App\Models\Activity::log(
                'pos',
                'refund',
                "Refund #{$refund->refund_number} of {$request->amount} for Sale #{$sale->sale_number}",
                ['refund_id' => $refund->id, 'amount' => $request->amount],
                $refund,
                'warning'
            );
        });

        return response()->json(['success' => true]);
    }
}
