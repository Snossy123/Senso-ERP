# Slice F — POS Refund & Void Inventory Consistency

## 1. Warehouse drift fixed for void

Previously, **`SaleController::void`** incremented **`products.stock_quantity`** and inserted a **`stock_movements`** row **without** **`warehouse_id`** or **`product_variant_id`**, and **did not** update **`product_warehouse_stocks`**. After a warehouse-scoped POS sale, voiding left the warehouse slice too low.

Void now routes each line through **`InventoryPostingService::postInbound`** with **`StockPostingData::forPosVoidLine`**, using the sale’s **`shift->warehouse_id`** and each line’s **`product_variant_id`**, matching outbound semantics.

## 2. Warehouse drift fixed for refund

Previously, refund restock incremented rolled-up product quantity and wrote **`stock_movements`** with **`product_variant_id`** but **not** a consistent warehouse slice when the sale used a warehouse shift.

Refund restock (same prorated quantity formula as before) now uses **`postInbound`** + **`StockPostingData::forPosRefundLine`** so **`product_warehouse_stocks`** and movement rows stay aligned with **`products.stock_quantity`**.

## 3. Behavior intentionally preserved

| Area | Unchanged |
|------|-----------|
| Request validation (void / refund) | Same rules |
| JSON responses | Same shape |
| Refund **amount** / **prorated** `restoreQty = round(line_qty * amount/total)` | Same |
| **`restock` flag** | Same |
| Sale status rules (`voided`, `refunded` including legacy partial sum quirk) | Same |
| Refund **journal** entry path | Still inline in **`SaleController::refund`** |
| Ecommerce / PO / POS sale posting | Not modified in this slice |

Movement **references** remain **`VOID-{sale_number}`** and **`REF-{refund_number}`**. Notes are standardized to **`Voided POS Sale`** and **`POS Refund`** for movements created via the service (replacing ad-hoc strings that omitted warehouse updates).

## 4. What remains risky in refund logic

- **Proration by sale total** is still a **money-weighted** approximation, not line-level SKU allocation. Partial refunds can misallocate quantity across lines when prices differ.
- **`totalRefunded`** calculation (`sum(refunds) + current amount`) still has the **double-count** characteristic documented in tests for partial flows — **not redesigned** in Slice F.
- **Reservation / serial / lot** awareness is **not** implemented; reversals mirror current rolled-up + warehouse slice only.

## 5. Why accounting was not changed

Slice F is strictly **inventory ledger consistency**. Refund journals remain in the controller transaction as before; moving refund accounting to an after-commit or event listener is a later slice.

## 6. Future work

| Topic | Direction |
|-------|-----------|
| Item-level refund allocation | Replace proration with explicit line amounts or returns lines |
| Reservation-aware reversal | Tie restock to reserved quantities when reservations exist |
| Serial/lot-aware refund | Track identifiers on sale lines and reverse accordingly |
| **`RefundRecorded` listener** | Optional event-driven accounting mirroring **`SaleRecorded`** |
| Failed inventory posting | Operational alerts if **`postInbound`** throws after partial writes (today failures bubble and roll back the void/refund transaction) |
