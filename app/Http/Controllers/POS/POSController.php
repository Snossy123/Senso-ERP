<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PosShift;
use App\Models\HeldOrder;
use App\Models\User;
use App\Services\POS\ShiftSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class POSController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    /** Shared data for POS cashier UI (standalone app + legacy blade consumers). */
    protected function terminalPayload(): array
    {
        $categories = Category::where('is_active', true)->get();

        $customers = Customer::orderBy('name')->limit(50)->get(['id', 'name', 'email', 'phone']);

        $activeShift = PosShift::where('user_id', Auth::id())
            ->where('status', 'open')
            ->latest()
            ->first();

        $heldOrders = HeldOrder::where('user_id', Auth::id())
            ->latest()
            ->take(10)
            ->get();

        $tenantName = optional(Auth::user()->tenant)->name ?? config('app.name');
        $cashierName = Auth::user()->name ?? '';

        return compact(
            'categories', 'customers', 'activeShift', 'heldOrders', 'tenantName', 'cashierName'
        );
    }

    /** Dedicated cashier-first POS shell at `/pos/app` (no ERP dashboard chrome). */
    public function app()
    {
        return view('pos.app', array_merge($this->terminalPayload(), ['posAppShell' => true]));
    }

    /** Legacy ERP-embedded POS layout (management shell). Kept for fallback bookmarks. */
    public function terminalLegacy()
    {
        return view('pos.terminal', array_merge($this->terminalPayload(), ['posAppShell' => false]));
    }

    /** Secondary customer-facing display (BroadcastChannel + localStorage sync). */
    public function customerDisplay()
    {
        $tenantName = optional(Auth::user()->tenant)->name ?? config('app.name');

        return view('pos.customer-display', [
            'currencySymbol' => config('app.currency_symbol', '$'),
            'tenantName' => $tenantName,
        ]);
    }

    // ── Shift Management ────────────────────────────────────────────────────────

    public function openShift(Request $request)
    {
        $request->validate(['opening_float' => 'required|numeric|min:0']);

        $existing = PosShift::where('user_id', Auth::id())->where('status', 'open')->first();
        if ($existing) {
            return response()->json(['error' => 'You already have an open shift.'], 422);
        }

        $shift = PosShift::create([
            'tenant_id'     => Auth::user()->tenant_id,
            'user_id'       => Auth::id(),
            'opening_float' => $request->opening_float,
            'terminal_id'   => $request->terminal_id ?? 'POS-1',
            'opened_at'     => now(),
            'status'        => 'open',
        ]);

        return response()->json(['success' => true, 'shift' => $shift]);
    }

    public function closeShift(Request $request, PosShift $shift)
    {
        $request->validate(['closing_float' => 'required|numeric|min:0']);

        if ($shift->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $shift->close((float) $request->closing_float, $request->notes);
        $summary = app(ShiftSummaryService::class)->summarize($shift->fresh());

        return response()->json([
            'success'       => true,
            'variance'      => $shift->variance,
            'expected_cash' => $shift->expected_cash,
            'total_sales'   => $shift->totalSales(),
            'summary'       => $summary,
        ]);
    }

    public function shiftsIndex(Request $request)
    {
        $query = PosShift::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('opened_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('opened_at', '<=', $request->to_date);
        }

        $shifts = $query->paginate(20)->withQueryString();
        $cashiers = User::pluck('name', 'id');

        return view('pos.shifts.index', compact('shifts', 'cashiers'));
    }

    public function shiftShow(PosShift $shift)
    {
        $shift->load([
            'user',
            'sales' => fn ($q) => $q->with('customer')->orderBy('created_at'),
        ]);
        $shiftSummary = app(ShiftSummaryService::class)->summarize($shift);

        return view('pos.shifts.show', compact('shift', 'shiftSummary'));
    }

    public function searchCustomers(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $query = Customer::query()->orderBy('name');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('company', 'like', "%{$q}%");
            });
        }

        $customers = $query->limit(20)->get(['id', 'name', 'email', 'phone', 'company']);

        return response()->json(['data' => $customers]);
    }

    // ── Held Orders ─────────────────────────────────────────────────────────────

    public function holdOrder(Request $request)
    {
        $request->validate([
            'cart'  => 'required|array|min:1',
            'label' => 'nullable|string|max:60',
        ]);

        $subtotal = collect($request->cart)->sum(fn($i) => $i['price'] * $i['qty']);

        $held = HeldOrder::create([
            'user_id'   => Auth::id(),
            'label'     => $request->label ?? 'Order ' . now()->format('H:i'),
            'cart_data' => $request->cart,
            'subtotal'  => $subtotal,
        ]);

        return response()->json(['success' => true, 'held' => $held]);
    }

    public function getHeldOrders()
    {
        $held = HeldOrder::where('user_id', Auth::id())->latest()->get();
        return response()->json($held);
    }

    public function resumeHeldOrder(HeldOrder $held)
    {
        if ($held->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }
        $data = $held->cart_data;
        $held->delete();
        return response()->json(['success' => true, 'cart' => $data]);
    }

    // ── Product Search / Barcode ─────────────────────────────────────────────────

    public function searchProduct(Request $request)
    {
        $q = $request->input('q');
        $products = Product::where('is_active', true)
            ->with(['variants' => fn($q) => $q->where('is_active', true)])
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('barcode', $q);
            })
            ->select(['id', 'name', 'sku', 'barcode', 'selling_price', 'stock_quantity', 'min_stock_alert', 'image'])
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id'        => $p->id,
                'name'      => $p->name,
                'sku'       => $p->sku,
                'barcode'   => $p->barcode,
                'price'     => (float) $p->selling_price,
                'stock'     => $p->stock_quantity,
                'has_variants' => $p->has_variants,
                'variants'     => $p->variants->map(fn($v) => [
                    'id'      => $v->id,
                    'name'    => $v->name,
                    'sku'     => $v->sku,
                    'barcode' => $v->barcode,
                    'price'   => (float) ($v->selling_price ?? $p->selling_price),
                ]),
                'low_stock' => $p->stock_quantity <= $p->min_stock_alert,
            ]);

        return response()->json($products);
    }

    public function productsFeed(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:120',
            'category_id' => 'nullable|integer|exists:categories,id',
            'barcode' => 'nullable|string|max:120',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:8|max:80',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 24);
        $queryText = trim((string) ($validated['q'] ?? ''));
        $barcode = trim((string) ($validated['barcode'] ?? ''));

        $query = Product::query()
            ->where('is_active', true)
            ->with(['category:id,name', 'variants' => fn ($q) => $q
                ->where('is_active', true)
                ->select(['id', 'product_id', 'name', 'sku', 'barcode', 'selling_price']),
            ])
            ->select([
                'id',
                'name',
                'sku',
                'barcode',
                'selling_price',
                'stock_quantity',
                'min_stock_alert',
                'category_id',
                'image',
                'has_variants',
            ]);

        if (! empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        if ($barcode !== '') {
            $query->where(function ($sub) use ($barcode) {
                $sub->where('barcode', $barcode)->orWhere('sku', $barcode);
            });
        } elseif ($queryText !== '') {
            $query->where(function ($sub) use ($queryText) {
                $sub->where('name', 'like', "%{$queryText}%")
                    ->orWhere('sku', 'like', "%{$queryText}%")
                    ->orWhere('barcode', 'like', "%{$queryText}%");
            });
        }

        $products = $query
            ->orderBy('name')
            ->paginate($perPage)
            ->through(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'price' => (float) $p->selling_price,
                'stock' => (float) $p->stock_quantity,
                'min_stock' => (float) $p->min_stock_alert,
                'category' => $p->category?->name,
                'category_id' => $p->category_id,
                'image' => $p->image_display_url,
                'has_variants' => (bool) $p->has_variants,
                'variants' => $p->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'sku' => $v->sku,
                    'barcode' => $v->barcode,
                    'price' => (float) ($v->selling_price ?? $p->selling_price),
                ]),
                'low_stock' => $p->stock_quantity <= $p->min_stock_alert,
                'out_of_stock' => $p->stock_quantity <= 0,
                'badge' => $p->stock_quantity <= 0 ? 'out' : ($p->stock_quantity <= $p->min_stock_alert ? 'low' : 'ok'),
                'search_index' => Str::lower(trim($p->name.' '.$p->sku.' '.$p->barcode)),
            ]);

        return response()->json($products);
    }

    public function quickStoreCustomer(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:120',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
        ]);

        try {
            $customer = Customer::create([
                'name'  => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
            ]);

            return response()->json([
                'success'  => true,
                'customer' => ['id' => $customer->id, 'name' => $customer->name],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
