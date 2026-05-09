# Slice B — InventoryPostingService (notes)

## 1. Unexpected coupling discovered

- **`TenantManager` vs explicit `tenant_id`:** Inventory postings historically relied on `BelongsToTenant` hooks (`TenantScope`, creating callbacks). The service passes **`tenant_id` explicitly** on `StockMovement` rows and resolves **`Product` via `tenant_id` + `withoutGlobalScopes()`** so postings stay correct under tests and future CLI jobs without HTTP middleware.
- **`ProductWarehouseStock` scope:** Reads/writes use **`withoutGlobalScopes()`** on warehouse-stock queries so behavior does not depend on whether `TenantManager` was hydrated for the current request (matches deterministic posting).
- **Purchase order receive** still owns the **DB transaction** and **PO status / `received_at`** updates; the service handles **inventory ledger writes only** (no accounting).

## 2. Hidden inventory assumptions

- **Rolled-up vs warehouse:** `products.stock_quantity` is still treated as a **global mirror** updated on every inbound/outbound in this service; warehouse rows are optional when `warehouse_id` is null (not used on PO receive today).
- **Purchase receive path:** Always has a **warehouse**; inbound increments **both** `product_warehouse_stocks` and **rolled-up** product quantity in that order (legacy preserved).
- **Adjustments:** `postAdjustment()` follows **`StockMovementController`** semantics: **absolute** rolled-up stock target; **no** warehouse slice update (legacy gap documented here for future omnichannel alignment).

## 3. Duplicate inventory logic locations (still present until later slices)

- **POS checkout:** `SaleController` — product decrement, optional warehouse decrement, `StockMovement` out (not yet calling `InventoryPostingService`).
- **Ecommerce checkout:** `CheckoutController` — product decrement + `StockMovement` only (**no** warehouse slice today).
- **Manual movements UI:** `StockMovementController` — creates movement row then adjusts rolled-up quantity (adjustment absolute semantics); **does not** touch `product_warehouse_stocks`.
- **Stock transfers / other flows:** Not audited in Slice B; grep for `StockMovement::create` / `increment('stock_quantity'` for migration inventory.

## 4. Future migration pain points

- **Ordering:** Legacy controllers differ on whether **movement row is inserted before or after** quantity mutation; POS vs PO ordering must be preserved when each flow migrates to the service.
- **Concurrent safety:** PO receive does not **lock** products; POS sale uses `lockForUpdate()` in places. Unifying may require explicit locking policy per use case.
- **Void / refund:** Warehouse restoration is **inconsistent** with rolled-up restoration (documented in Slice A); moving POS void/refund into this service will force **explicit policies** for warehouse rows.
- **`StockPostingData`:** Mixing **delta** quantities (`in`/`out`) with **absolute** adjustment targets is awkward; a later refactor may split DTOs (`InboundPosting`, `OutboundPosting`, `AdjustmentPosting`) without changing DB tables.

## 5. Should `products.stock_quantity` stay inside the service long term?

- **Short term (Slices B–D):** Yes — keep rolled-up updates **inside `InventoryPostingService`** so all writers share one implementation and characterization tests stay stable.
- **Longer term (ADR direction):** Treat `product_warehouse_stocks` as **operational truth** and `products.stock_quantity` as a **derived cache/projection** maintained by the same service (or a narrow **projection updater** subscribed after posting). Moving projection to async listeners is **out of scope** until events and idempotency are defined.
