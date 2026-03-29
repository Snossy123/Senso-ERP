<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PosShift;
use App\Models\HeldOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class POSController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function terminal()
    {
        $categories = Category::where('is_active', true)->get();
        $products = Product::where('is_active', true)
            ->with(['category', 'variants' => fn($q) => $q->where('is_active', true)])
            ->get()
            ->map(fn($p) => [
                'id'           => $p->id,
                'name'         => $p->name,
                'sku'          => $p->sku,
                'barcode'      => $p->barcode,
                'price'        => (float) $p->selling_price,
                'stock'        => $p->stock_quantity,
                'min_stock'    => $p->min_stock_alert,
                'category'     => $p->category?->name,
                'category_id'  => $p->category_id,
                'image'        => $p->image_url,
                'has_variants' => $p->has_variants,
                'variants'     => $p->variants->map(fn($v) => [
                    'id'      => $v->id,
                    'name'    => $v->name,
                    'sku'     => $v->sku,
                    'barcode' => $v->barcode,
                    'price'   => (float) ($v->selling_price ?? $p->selling_price),
                ]),
                'low_stock'    => $p->stock_quantity <= $p->min_stock_alert,
                'out_of_stock' => $p->stock_quantity <= 0,
            ]);

        $customers = Customer::orderBy('name')->get(['id', 'name', 'email', 'phone']);

        // Active shift for this user
        $activeShift = PosShift::where('user_id', Auth::id())
            ->where('status', 'open')
            ->latest()
            ->first();

        // Held orders for this user
        $heldOrders = HeldOrder::where('user_id', Auth::id())
            ->latest()
            ->take(10)
            ->get();

        return view('pos.terminal', compact(
            'categories', 'products', 'customers', 'activeShift', 'heldOrders'
        ));
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

        return response()->json([
            'success'       => true,
            'variance'      => $shift->variance,
            'expected_cash' => $shift->expected_cash,
            'total_sales'   => $shift->totalSales(),
        ]);
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
