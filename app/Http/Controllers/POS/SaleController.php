<?php

namespace App\Http\Controllers\POS;

use App\Application\Inventory\InventoryPostingService;
use App\Application\Inventory\StockPostingData;
use App\Events\POS\InventoryBulkUpdated;
use App\Events\POS\PosSaleCompleted;
use App\Http\Controllers\Controller;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleRefund;
use App\Events\Domain\Sales\SaleRecorded;
use App\Models\User;
use App\Notifications\LowStockAlertNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SaleController extends Controller
{
    public function __construct(
        private readonly InventoryPostingService $inventoryPostingService
    ) {
        $this->middleware('auth');
    }

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
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount_pct' => 'nullable|numeric|min:0|max:100',
            'payment_method' => 'required|in:cash,card,bank_transfer,split',
            'discount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'amount_tendered' => 'nullable|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:120',
            'shift_id' => 'required|exists:pos_shifts,id',
            'client_idempotency_key' => 'nullable|string|max:191',
        ]);

        // Resolve Tenant
        $tenant = app(\App\Services\TenantManager::class)->getCurrent() ?? Auth::user()->tenant;

        if (! $tenant) {
            return response()->json(['success' => false, 'error' => 'Tenant context not found.'], 403);
        }

        if (! $tenant->hasFeature('pos')) {
            return response()->json(['success' => false, 'error' => 'POS feature is not enabled for your plan.'], 403);
        }
        $usage = $tenant->getOrdersUsage();
        if ($usage && $usage->isAtLimit()) {
            return response()->json(['success' => false, 'error' => 'Monthly order limit reached. Please upgrade your plan.'], 403);
        }

        $idempotencyKey = $request->input('client_idempotency_key');
        if (filled($idempotencyKey) && Schema::hasColumn('sales', 'client_idempotency_key')) {
            $existing = Sale::query()
                ->where('tenant_id', $tenant->id)
                ->where('client_idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'duplicate' => true,
                    'sale_id' => $existing->id,
                    'change_due' => $existing->change_due,
                    'sale_number' => $existing->sale_number,
                ]);
            }
        }

        $lowStockProducts = [];
        $saleId = null;

        DB::transaction(function () use ($request, &$lowStockProducts, &$saleId, $tenant, $idempotencyKey) {
            $items = $request->input('items');
            $discountAmt = (float) $request->input('discount', 0);
            $taxRate = (float) $request->input('tax_rate', 0);
            $subtotal = 0;

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
                if ($itemDiscountPct > 0 && ! Auth::user()->hasPermission('pos.discount')) {
                    $maxItemDisc = (float) \App\Models\Setting::get('pos_max_discount_no_perm', 0, $tenant->id);
                    if ($itemDiscountPct > $maxItemDisc) {
                        throw new \Exception("You do not have permission to apply {$itemDiscountPct}% discount on {$product->name}. Max allowed: {$maxItemDisc}%");
                    }
                }

                $itemDiscount = ($item['price'] * $item['qty'] * $itemDiscountPct / 100);
                $subtotal += ($item['price'] * $item['qty']) - $itemDiscount;
            }

            $taxAmount = round(($subtotal - $discountAmt) * $taxRate / 100, 2);
            $total = round($subtotal - $discountAmt + $taxAmount, 2);

            // Order-level discount permission check
            if ($discountAmt > 0 && ! Auth::user()->hasPermission('pos.discount')) {
                $maxOrderDiscPct = (float) \App\Models\Setting::get('pos_max_order_discount_pct_no_perm', 5, $tenant->id);
                $orderDiscPct = ($discountAmt / $subtotal) * 100;
                if ($orderDiscPct > $maxOrderDiscPct) {
                    throw new \Exception("Order discount exceeds your limit of {$maxOrderDiscPct}%");
                }
            }

            $amountTendered = (float) $request->input('amount_tendered', $total);
            $changeDue = max(0, $amountTendered - $total);

            $saleAttrs = [
                'tenant_id' => $tenant->id,
                'sale_number' => Sale::generateSaleNumber(),
                'customer_id' => $request->input('customer_id'),
                'customer_name' => $request->input('customer_name'),
                'user_id' => Auth::id(),
                'shift_id' => $request->input('shift_id'),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmt,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'payment_method' => $request->input('payment_method'),
                'payment_status' => 'paid',
                'amount_tendered' => $amountTendered,
                'change_due' => $changeDue,
                'status' => 'completed',
                'notes' => $request->input('notes'),
            ];

            if (Schema::hasColumn('sales', 'client_idempotency_key')) {
                $saleAttrs['client_idempotency_key'] = filled($idempotencyKey) ? $idempotencyKey : null;
            }

            $sale = Sale::create($saleAttrs);

            $shift = PosShift::find($request->input('shift_id'));

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['id']);
                $variantId = $item['variant_id'] ?? null;
                $discountPct = (float) ($item['discount_pct'] ?? 0);
                $lineTotal = $item['price'] * $item['qty'];
                $lineDisc = $lineTotal * $discountPct / 100;

                SaleItem::create([
                    'tenant_id' => $tenant->id,
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId,
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                    'discount_pct' => $discountPct,
                    'discount_amount' => $lineDisc,
                    'discount' => $lineDisc,
                    'total' => $lineTotal - $lineDisc,
                ]);

                $this->inventoryPostingService->postOutbound(
                    StockPostingData::forPosSaleLine(
                        tenantId: (int) $tenant->id,
                        productId: $product->id,
                        productVariantId: $variantId,
                        warehouseId: $shift?->warehouse_id,
                        quantity: $item['qty'],
                        unitCost: (float) $product->purchase_price,
                        totalValue: $item['qty'] * (float) $product->purchase_price,
                        saleNumber: $sale->sale_number,
                        userId: (int) Auth::id(),
                    )
                );

                $product->refresh();

                if ($product->stock_quantity <= $product->min_stock_alert) {
                    $lowStockProducts[] = $product;
                }
            }

            $saleId = $sale->id;

            DB::afterCommit(function () use ($sale, $tenant) {
                event(new SaleRecorded(
                    saleId: $sale->id,
                    tenantId: (int) $tenant->id,
                    channel: 'pos',
                    payloadVersion: 1,
                ));
            });
        });

        // Post-transaction: notifications
        foreach ($lowStockProducts as $product) {
            $admins = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->get();
            foreach ($admins as $admin) {
                try {
                    $admin->notify(new LowStockAlertNotification($product));
                } catch (\Throwable) {
                }
            }
        }

        $sale = Sale::with('items.product')->find($saleId);
        \App\Models\Activity::logSale($sale);

        if ($sale && config('broadcasting.default') !== 'null') {
            try {
                $updates = [];
                foreach ($sale->items as $item) {
                    if ($item->product) {
                        $updates[] = [
                            'product_id' => $item->product_id,
                            'stock_quantity' => $item->product->stock_quantity,
                        ];
                    }
                }
                if ($updates !== []) {
                    broadcast(new InventoryBulkUpdated($tenant->id, $updates, 'pos_sale'));
                }
                broadcast(new PosSaleCompleted(
                    $tenant->id,
                    $sale->id,
                    (string) $sale->sale_number,
                    $sale->shift_id,
                    $sale->user_id
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'success' => true,
            'sale_id' => $saleId,
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

        $sale->load(['items.product', 'shift']);
        $inventory = $this->inventoryPostingService;
        $warehouseId = $sale->shift?->warehouse_id;

        DB::transaction(function () use ($sale, $request, $inventory, $warehouseId) {
            foreach ($sale->items as $item) {
                $product = $item->product;
                if (! $product) {
                    continue;
                }

                $qty = (int) $item->quantity;
                if ($qty < 1) {
                    continue;
                }

                $unitCost = (float) $product->purchase_price;
                $totalValue = $qty * $unitCost;

                $inventory->postInbound(
                    StockPostingData::forPosVoidLine(
                        tenantId: (int) $sale->tenant_id,
                        productId: $item->product_id,
                        productVariantId: $item->product_variant_id,
                        warehouseId: $warehouseId,
                        quantity: $qty,
                        unitCost: $unitCost,
                        totalValue: $totalValue,
                        reference: 'VOID-'.$sale->sale_number,
                        userId: (int) Auth::id(),
                    )
                );
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
            'amount' => 'required|numeric|min:0.01|max:'.$sale->total,
            'reason' => 'required|string|max:255',
            'method' => 'required|in:original,cash,credit',
            'restock' => 'nullable|boolean',
        ]);

        if (! Auth::user()->hasPermission('pos.refund')) {
            return response()->json(['error' => 'You do not have permission to process refunds.'], 403);
        }

        if ($sale->isVoided()) {
            return response()->json(['error' => 'Cannot refund a voided sale.'], 422);
        }

        $tenant = app(\App\Services\TenantManager::class)->getCurrent() ?? Auth::user()->tenant;
        $restock = (bool) $request->input('restock', true);

        $sale->loadMissing(['items.product', 'shift']);
        $inventory = $this->inventoryPostingService;
        $warehouseId = $sale->shift?->warehouse_id;

        DB::transaction(function () use ($sale, $request, $tenant, $restock, $inventory, $warehouseId) {
            $refund = SaleRefund::create([
                'tenant_id' => $tenant?->id ?? $sale->tenant_id,
                'sale_id' => $sale->id,
                'user_id' => Auth::id(),
                'refund_number' => SaleRefund::generateRefundNumber(),
                'amount' => $request->amount,
                'reason' => $request->reason,
                'method' => $request->method,
            ]);

            // Restore stock pro-rated by refund amount ratio
            if ($restock && $sale->total > 0) {
                $ratio = $request->amount / $sale->total;
                foreach ($sale->items as $saleItem) {
                    $restoreQty = (int) round($saleItem->quantity * $ratio);
                    if ($restoreQty <= 0) {
                        continue;
                    }

                    $product = $saleItem->product;
                    if (! $product) {
                        continue;
                    }

                    $unitCost = (float) $product->purchase_price;
                    $totalValue = $restoreQty * $unitCost;

                    $inventory->postInbound(
                        StockPostingData::forPosRefundLine(
                            tenantId: (int) ($tenant?->id ?? $sale->tenant_id),
                            productId: $saleItem->product_id,
                            productVariantId: $saleItem->product_variant_id,
                            warehouseId: $warehouseId,
                            quantity: $restoreQty,
                            unitCost: $unitCost,
                            totalValue: $totalValue,
                            reference: 'REF-'.$refund->refund_number,
                            userId: (int) Auth::id(),
                        )
                    );
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
                throw new \Exception('Accounting integration failed for refund: '.$e->getMessage());
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
