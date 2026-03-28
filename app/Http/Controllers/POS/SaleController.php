<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Activity;
use App\Notifications\LowStockAlertNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function index()
    {
        $sales = Sale::with('user', 'customer')->latest()->paginate(20);
        return view('pos.sales.index', compact('sales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'          => 'required|array|min:1',
            'items.*.id'     => 'required|exists:products,id',
            'items.*.qty'    => 'required|integer|min:1',
            'items.*.price'  => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,bank_transfer',
            'discount'       => 'nullable|numeric|min:0',
            'tax_rate'       => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($request) {
            $items         = $request->input('items');
            $discountAmt   = (float) $request->input('discount', 0);
            $taxRate       = (float) $request->input('tax_rate', 0);
            $subtotal      = 0;

            foreach ($items as $item) {
                $subtotal += $item['price'] * $item['qty'];
            }

            $taxAmount = round(($subtotal - $discountAmt) * $taxRate / 100, 2);
            $total     = $subtotal - $discountAmt + $taxAmount;

            $sale = Sale::create([
                'sale_number'     => Sale::generateSaleNumber(),
                'customer_id'     => $request->input('customer_id'),
                'user_id'         => Auth::id(),
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmt,
                'tax_amount'      => $taxAmount,
                'total'           => $total,
                'payment_method'  => $request->input('payment_method'),
                'payment_status'  => 'paid',
                'notes'           => $request->input('notes'),
            ]);

            $lowStockProducts = [];

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['id']);
                $lineTotal = $item['price'] * $item['qty'];

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $product->id,
                    'quantity'   => $item['qty'],
                    'unit_price' => $item['price'],
                    'discount'   => 0,
                    'total'      => $lineTotal,
                ]);

                $product->decrement('stock_quantity', $item['qty']);
                
                $product->refresh();
                if ($product->stock_quantity <= $product->min_stock_alert) {
                    $lowStockProducts[] = $product;
                }

                StockMovement::create([
                    'product_id' => $product->id,
                    'type'       => 'out',
                    'quantity'   => $item['qty'],
                    'reference'  => $sale->sale_number,
                    'notes'      => 'POS Sale',
                    'user_id'    => Auth::id(),
                ]);
            }

            session(['last_sale_id' => $sale->id]);
        });

        $sale = Sale::find(session('last_sale_id'));
        if ($sale) {
            Activity::logSale($sale);
        }

        foreach ($lowStockProducts as $product) {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new LowStockAlertNotification($product));
            }
        }

        $saleId = session('last_sale_id');
        return response()->json(['success' => true, 'sale_id' => $saleId]);
    }

    public function show(Sale $sale)
    {
        $sale->load('items.product', 'customer', 'user');
        return view('pos.sales.show', compact('sale'));
    }
}
