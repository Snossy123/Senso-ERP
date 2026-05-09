# Slice G — `RecordWebOrderService` (Ecommerce Orchestration)

## 1. Responsibilities moved from the controller

The following now live in **`app/Application/Sales/RecordWebOrderService`** inside a single **`DB::transaction`**:

- Building checkout lines with **`Product::lockForUpdate()`** (same order as before: lock all cart lines, then create order).
- Creating **`Order`** and **`OrderItem`** rows with unchanged totals and statuses (`pending`, `payment_status` = `pending`).
- Calling **`InventoryPostingService::postOutbound`** with **`StockPostingData::forEcommerceOrderLine`** per line.
- **Low-stock detection**: projected `stock_quantity - qty` vs **`min_stock_alert`** before posting each line, collecting **`Product`** models for admin alerts (same semantics as the previous controller loop).
- Returning **`RecordWebOrderResult`** with the persisted **`order`** (with **`items`** loaded), **`lowStockProducts`**, empty **`warnings`** for future use, **`inventoryPosted`**, and **`paymentStatus`**.

## 2. What remains in `CheckoutController`

- Reading the session **cart** and rejecting empty cart before ordering.
- **`$request->validate(...)`** for checkout form fields (unchanged rules).
- **Tenant order limit** check (`TenantManager` + `getOrdersUsage()->isAtLimit()`).
- Delegating orchestration to **`RecordWebOrderService::record($cart, $data, $customer)`**.
- **`Activity::logOrder`**, **customer `OrderPlacedNotification`**, **admin `LowStockAlertNotification`** loops (same as before, outside the DB transaction — notifications were already post-transaction).
- **Session**: `forget('cart')`, **`last_order_number`**.
- **Redirect** to **`store.checkout.success`** and the **`success`** action/view unchanged.

## 3. Future extraction opportunities

| Area | Idea |
|------|------|
| Notifications | Move behind domain events (`WebOrderRecorded`) after-commit if ecommerce accounting/events are unified. |
| Usage limits | Could move into service pre-check or policy object; left in controller to avoid behavior change. |
| Result DTO | **`warnings`** reserved for allocation conflicts, payment holds, or partial fulfillment later. |

## 4. Remaining ecommerce gaps

| Gap | Notes |
|-----|--------|
| **Reservations** | Not implemented; checkout still decrements rolled-up stock only (no warehouse allocation). |
| **Warehouse allocation** | Ecommerce outbound remains **`warehouse_id = null`** in **`StockPostingData::forEcommerceOrderLine`**. |
| **Idempotency** | No **`client_idempotency_key`** on orders yet (characterization test documents baseline). |
| **Async payments** | **`payment_status`** stays **`pending`** for COD/online as today; no payment gateway seam in this slice. |

## 5. Why ecommerce accounting is still deferred

Slice G is **orchestration extraction only**. Revenue/cost recognition for web orders still follows whatever exists outside this path (no **`SaleRecorded`**-style ecommerce listener in this slice). Accounting hooks belong in a later slice once posting and events are aligned with POS/refund patterns.
