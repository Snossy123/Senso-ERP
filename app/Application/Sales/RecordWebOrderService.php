<?php

namespace App\Application\Sales;

use App\Application\Inventory\InventoryPostingService;
use App\Application\Inventory\StockPostingData;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Ecommerce checkout orchestration: order + items + inventory posting + low-stock detection.
 * HTTP/session/redirect remain in {@see \App\Http\Controllers\Store\CheckoutController}.
 */
class RecordWebOrderService
{
    public function __construct(
        private readonly InventoryPostingService $inventoryPostingService
    ) {}

    /**
     * @param  array<int, array{qty: int}>  $cart  Session cart shape: product id => ['qty' => ...]
     * @param  array{
     *   customer_name: string,
     *   customer_email: ?string,
     *   customer_phone: ?string,
     *   shipping_address: ?string,
     *   city: ?string,
     *   payment_method: string,
     *   notes: ?string
     * } $checkoutData Request-validated checkout fields
     */
    public function record(array $cart, array $checkoutData, ?Customer $customer = null): RecordWebOrderResult
    {
        if ($cart === []) {
            throw new InvalidArgumentException('Cart must not be empty.');
        }

        $order = null;
        $lowStockProducts = [];

        DB::transaction(function () use ($cart, $checkoutData, $customer, &$order, &$lowStockProducts) {
            $subtotal = 0;
            $lines = [];

            foreach ($cart as $id => $item) {
                $product = Product::lockForUpdate()->findOrFail($id);
                $lineTotal = $product->selling_price * $item['qty'];
                $subtotal += $lineTotal;
                $lines[] = ['product' => $product, 'qty' => $item['qty'], 'lineTotal' => $lineTotal];
            }

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'customer_id' => $customer?->id,
                'customer_name' => $checkoutData['customer_name'],
                'customer_email' => $checkoutData['customer_email'],
                'customer_phone' => $checkoutData['customer_phone'],
                'shipping_address' => $checkoutData['shipping_address'],
                'city' => $checkoutData['city'],
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'payment_method' => $checkoutData['payment_method'],
                'payment_status' => 'pending',
                'status' => 'pending',
                'notes' => $checkoutData['notes'],
            ]);

            foreach ($lines as $line) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'quantity' => $line['qty'],
                    'unit_price' => $line['product']->selling_price,
                    'total' => $line['lineTotal'],
                ]);

                $product = $line['product'];

                $newStock = $product->stock_quantity - $line['qty'];

                if ($newStock <= $product->min_stock_alert) {
                    $lowStockProducts[] = $product;
                }

                $this->inventoryPostingService->postOutbound(
                    StockPostingData::forEcommerceOrderLine(
                        tenantId: (int) $order->tenant_id,
                        productId: $product->id,
                        quantity: $line['qty'],
                        orderNumber: $order->order_number,
                    )
                );

                $product->refresh();
            }
        });

        $order = $order?->fresh(['items']);
        if (! $order) {
            throw new \RuntimeException('Order was not persisted.');
        }

        return new RecordWebOrderResult(
            order: $order,
            lowStockProducts: $lowStockProducts,
            warnings: [],
            inventoryPosted: true,
            paymentStatus: (string) $order->payment_status,
        );
    }
}
