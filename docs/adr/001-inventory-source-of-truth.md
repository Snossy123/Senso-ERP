# ADR-001: Inventory Source of Truth

## Status

Proposed

## Context

The system persists inventory in multiple places:

- `products.stock_quantity` — integer on the product row (legacy rolled-up field).
- `product_warehouse_stocks` — quantity per `(product_id, product_variant_id|null, warehouse_id)`.
- `stock_movements` — ledger of ins/outs with references.

POS sale flow (`SaleController@store`) validates availability using **`products.stock_quantity` only**, then decrements that column and optionally decrements `product_warehouse_stocks` when the cashier’s shift has `warehouse_id`. Ecommerce checkout and PO receiving also touch `products.stock_quantity` and movements.

Without a single declared source of truth, omnichannel sync, multi-terminal POS, manufacturing issues/receipts, and variant-accurate allocation will diverge.

## Decision

### Canonical model (authoritative quantities)

**Authoritative available quantity** for fulfillment is defined at the **warehouse stock slice**:

**Key:** `(tenant_id, product_id, product_variant_id|null, warehouse_id)` in **`product_warehouse_stocks.quantity`**.

The **ledger of record** for audit and costing remains **`stock_movements`** (append-only semantics for posted movements).

### Rolled-up field (`products.stock_quantity`)

**Decision (choose one tenant-level mode — default recommendation):**

- **`inventory.rollup_mode = sum_all_warehouses` (recommended default):**  
  `products.stock_quantity` is a **derived cache** equal to the sum of `product_warehouse_stocks.quantity` for that product (and variant=null rows aggregated per product policy), maintained **synchronously** inside the same transaction as warehouse updates **once** `InventoryPostingService` exists.

- **Alternative `single_default_warehouse`:**  
  Rolled-up column mirrors **one** warehouse only; multi-warehouse tenants rely entirely on `product_warehouse_stocks` for non-default locations.

Until extraction is implemented, document **current behavior as legacy**: rolled-up and warehouse rows are both updated ad hoc; **target state** is one writer (`InventoryPostingService`) enforcing invariants.

### Reservations (foundation)

- **Direction:** Introduce a **reservation layer** after posting service lands: either `stock_reservations` table or `reserved_quantity` on warehouse stock rows, scoped by `reference_type` / `reference_id` (cart id, order id, MO id).
- **Phase 1:** No requirement to ship reservations; **interface stub** `ReservationPort` with no-op is acceptable.

### Variant stock behavior

- **Stock-bearing identity** is `(product_id, product_variant_id)` at warehouse granularity.
- POS lines already send `variant_id`; warehouse decrement uses it. **Validation** must eventually check **warehouse row** for that variant when shift has `warehouse_id`, not only parent `products.stock_quantity`.

### Manufacturing compatibility

- Manufacturing **issues** and **receipts** are **inventory movements** with reason codes (`mo_issue`, `mo_receipt`) referencing work orders (future tables). They **must not** bypass `InventoryPostingService`.
- BOM explosion does not live in `product_warehouse_stocks`; only **resolved SKUs** post movements.

### Ecommerce compatibility

- Web orders must call the **same posting service** as POS for outbound stock, so **omnichannel** does not double-decrement or skip warehouse rows.

### POS validation behavior (target)

| Shift / context | Validate against |
|-----------------|------------------|
| `warehouse_id` set | `product_warehouse_stocks` for `(product, variant, warehouse)` |
| No warehouse / legacy | Policy from tenant settings: rolled-up sum or default warehouse row |

## Explicit answer: What is the REAL stock source of truth?

**Operational truth:** **`product_warehouse_stocks.quantity`** per `(product, variant?, warehouse)`.

**Audit trail:** **`stock_movements`**.

**`products.stock_quantity`:** **Not** ultimate truth long-term; **cache or single-warehouse mirror** per rollup policy until removed in a later major migration.

## Consequences

### Positive

- One posting pipeline can enforce invariants before omnichannel and manufacturing.
- Clear path to reservations and concurrent checkout.

### Negative / work

- Requires backfill or reconciliation job if historical rolled-up and warehouse rows diverge.
- POS and ecommerce must migrate validation logic — **behavior change** unless feature-flagged per tenant.

## Compliance

Review when: first `InventoryPostingService` ships; when reservations launch; when manufacturing MO posts movements.
