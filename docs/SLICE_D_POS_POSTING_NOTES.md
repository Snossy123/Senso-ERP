# Slice D — POS sale checkout → InventoryPostingService

## 1. POS stock validation still uses `products.stock_quantity`

- First loop + per-line `Product::lockForUpdate()` continue to enforce availability against **rolled-up** `stock_quantity` only.
- **Warehouse slice availability is not validated** at checkout (same as pre–Slice D).

## 2. Warehouse posting now goes through `InventoryPostingService`

- When `PosShift.warehouse_id` is set, `postOutbound` applies **`product_warehouse_stocks`** via `updateOrCreate` + `decrement`, keyed by `product_id`, `product_variant_id`, `warehouse_id`.
- Outbound **ordering** matches legacy POS: **rolled-up decrement → warehouse decrement → `stock_movements` row** (Slice D aligned `postOutbound` implementation).

## 3. Variant warehouse / movement behavior

- `items.*.variant_id` maps to `product_variant_id` on **warehouse rows** and **stock_movements** (unchanged semantics).
- Rolled-up validation remains on the **parent product** until a future slice validates against warehouse truth.

## 4. Remaining duplicate accounting issue

- **Unchanged:** `AccountingObserver::created(Sale)` **and** `SaleController` journal creation still produce **two** journal entries per POS sale (characterized in Slice A). Slice E will consolidate.

## 5. Remaining warehouse drift on refund / void

- **Unchanged:** void/refund inventory paths still bypass unified warehouse restoration (Slice A characterization). Slice F targets consistency via `InventoryPostingService`.

## 6. Why domain events are still deferred

- Slice D only moved **inventory writes** behind the service; **no** `SaleRecorded` / after-commit hooks added so POS payload, idempotency, broadcasts, and duplicate accounting stay identical until Slice E.

## 7. Before switching validation to warehouse truth

- Need **default fulfillment warehouse** (or allocation rules), **aggregated availability** across warehouses, optional **reservations**, and tests proving **no oversell** when rolled-up and warehouse totals diverge.
