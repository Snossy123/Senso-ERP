# Foundation Implementation Plan — Events, Services, Tests, First Slice

**Prerequisites:** ADRs [001](./adr/001-inventory-source-of-truth.md)–[005](./adr/005-domain-event-strategy.md) reviewed and **Accepted** (or Accepted with amendments).

**Companion:** [FOUNDATION_TRANSACTION_AND_IDEMPOTENCY_ANALYSIS.md](./FOUNDATION_TRANSACTION_AND_IDEMPOTENCY_ANALYSIS.md), [FOUNDATION_EXTRACTION_PHASE.md](./FOUNDATION_EXTRACTION_PHASE.md).

---

## 1. Event flow definitions (implementation target)

### Events (internal Laravel events)

| Event | Dispatched when | Payload (minimal) |
|-------|-----------------|-------------------|
| `Domain\Sales\SaleRecorded` | POS sale DB transaction **commits** | `saleId`, `tenantId`, `channel: pos` |
| `Domain\Sales\WebOrderRecorded` | Ecommerce order **commits** | `orderId`, `tenantId`, `channel: web` |
| `Domain\Inventory\StockPosted` | Movements + warehouse updates **committed** (optional aggregate or per-line) | `tenantId`, `movementIds[]`, `reason` |
| `Domain\Inventory\GoodsReceived` | PO receive **commits** | `purchaseOrderId`, `warehouseId` |
| `Domain\Sales\RefundRecorded` | Refund row + restock **commits** | `refundId`, `saleId` |

Dispatch via **`DB::afterCommit`** from orchestration layer once extracted.

### Listeners (initial)

| Listener | Subscribes to | Action |
|----------|---------------|--------|
| `PostSaleJournalListener` | `SaleRecorded` | `SaleJournalEntryGenerator` + `AccountingService` |
| `PostRefundJournalListener` | `RefundRecorded` | `RefundJournalEntryGenerator` |
| `PostGoodsReceivedJournalListener` | `GoodsReceived` | `PurchaseJournalEntryGenerator` |
| `BroadcastInventoryUpdatedListener` | `SaleRecorded` or `StockPosted` | Optional: existing `InventoryBulkUpdated` |

**Remove** duplicate triggers: `AccountingObserver` for `Sale` once listeners verified.

---

## 2. Extraction implementation plan (ordered)

| Step | Deliverable | Acceptance |
|------|-------------|------------|
| S0 | Characterization tests (see §4) | CI green |
| S1 | `InventoryPostingService` with **same** DB effects as PO `receive` inner loop | Tests match row counts |
| S2 | Wire ecommerce checkout to posting service | Tests |
| S3 | Wire POS sale stock loop to posting service | Tests |
| S4 | Introduce `SaleRecorded` + listener; remove duplicate JE path | One JE per sale; tests |
| S5 | `GoodsReceived` + procurement listener; fix PO status vs observer | One JE per receive policy |
| S6 | `RefundRecorded`; move JE from controller | Tests |
| S7 | Thin controllers | Same HTTP responses |

---

## 3. First characterization test plan

**Goal:** Lock behavior **before** refactor.

### POS sale (`POST /pos/sale`)

- **Setup:** tenant, user, shift, products with known `stock_quantity`, optional warehouse stock.
- **Assert:** HTTP 200, `sales` + `sale_items` rows, stock decremented, `stock_movements` rows, journal entry count **document baseline** (fix duplicate issue separately).
- **Idempotency:** same `client_idempotency_key` → duplicate JSON, **no** second sale.

### Ecommerce (`POST` checkout)

- **Assert:** `orders`, `order_items`, stock decrement, movements — **baseline counts**.

### PO receive

- **Assert:** warehouse quantity increase, product rolled-up increase, movement rows, PO status.

### Refund

- **Assert:** `sale_refunds` row, stock increment ratio, journal baseline.

### Void

- **Assert:** stock restore, movement row, sale status.

**Tooling:** PHPUnit feature tests or Laravel HTTP tests with `RefreshDatabase` where applicable.

---

## 4. First safe implementation slice (recommended)

**Slice A — Read-only + tests only**

- Add characterization tests **without** production code change (or minimal fixtures).

**Slice B — `InventoryPostingService` + PO receive only**

- Lowest user-visible blast radius.
- Delegate [`PurchaseOrderController::receive`](app/Http/Controllers/Inventory/PurchaseOrderController.php) inner loop to service.
- **No** route/API change.

**Slice C — Ecommerce**

- Swap checkout stock loop to service; preserve session + redirect behavior.

**Slice D — POS**

- Highest scrutiny; last to switch posting path.

**Slice E — Events / accounting**

- After posting stable, add `afterCommit` + listeners; remove observer/controller duplication.

---

## 5. Preservation checklist (every slice)

- [ ] Existing routes and JSON shapes unchanged unless documented.
- [ ] POS offline queue + `client_idempotency_key` behavior preserved.
- [ ] Frontend contracts unchanged in Slice B–D (same payloads).
- [ ] Rollback plan: single PR per slice or feature branch behind flag.

---

## 6. Files likely to change (by slice)

| Slice | Primary files |
|-------|----------------|
| B | `PurchaseOrderController.php`, new `Application/Inventory/InventoryPostingService.php` |
| C | `CheckoutController.php` |
| D | `SaleController.php` |
| E | `EventServiceProvider.php`, `AccountingObserver.php`, new `Listeners/*`, `Events/Domain/*` |

---

*Update this plan when ADRs are accepted or amended.*
