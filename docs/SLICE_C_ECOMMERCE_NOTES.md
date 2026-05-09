# Slice C — Ecommerce checkout → InventoryPostingService

## 1. Ecommerce vs POS inventory semantics (today)

| Aspect | Ecommerce (`CheckoutController`) | POS (`SaleController`) |
|--------|----------------------------------|---------------------------|
| **Tenant context** | `TenantMiddleware` + `X-Tenant-ID` (guest-friendly) | Authenticated staff user + tenant |
| **Concurrency** | `Product::lockForUpdate()` on cart lines **before** order insert | `lockForUpdate()` per line inside sale transaction |
| **Warehouse** | No `warehouse_id`; **no** `product_warehouse_stocks` mutation | Optional `PosShift.warehouse_id`; decrements **warehouse row + rolled-up** stock |
| **Variants** | Cart keyed by `product_id` only | Supports `variant_id` on lines |
| **Movement fields** | Was sparse (`product_id`, `type`, `qty`, `reference`, `notes`); now rows include `tenant_id`, `before_quantity`, `after_quantity`, zero costs — via **InventoryPostingService** | Full movement rows + warehouse linkage |
| **Actor** | No staff `user_id` on movements (`null`) | Cashier `user_id` on movements |
| **Idempotency** | None | `client_idempotency_key` on sales |

## 2. Missing warehouse semantics in ecommerce

- Checkout only reduces **`products.stock_quantity`** (rolled-up cache). It **does not** choose a fulfillment warehouse or decrement **`product_warehouse_stocks`**.
- Omnichannel risk: web availability mirrors rolled-up quantity while fulfillment might pick stock from a specific warehouse.

## 3. Should ecommerce eventually support warehouse allocation?

- **Yes**, once storefront/checkout knows **which warehouse** fulfills web orders (single default warehouse per tenant, geo routing, or allocate-from-available aggregate).
- Until then, warehouse truth stays aligned only if rolled-up stock is maintained consistently with warehouse totals (future invariant enforcement).

## 4. Should ecommerce reserve inventory before payment?

- **Not today** (`payment_status` remains `pending` on COD; no reservation layer).
- Future options: soft reservation at checkout, TTL release, payment capture hooks — **out of scope** until a reservation subsystem exists.

## 5. What should later move into RecordWebOrderService (or similar)?

- **Orchestration**: validate cart → lock catalog rows → create `Order` / `OrderItem` rows → **delegate inventory** → notifications / activity.
- **Non-inventory**: shipping fields, customer association, session (`last_order_number`), redirect targets.
- **Inventory**: already delegated to **InventoryPostingService** for writes; a future service would **call** it rather than embed SQL.

## Slice C scope

- **Only** inventory writes moved behind `InventoryPostingService::postOutbound` + `StockPostingData::forEcommerceOrderLine`.
- No events, reservations, order schema changes, or warehouse validation added.
