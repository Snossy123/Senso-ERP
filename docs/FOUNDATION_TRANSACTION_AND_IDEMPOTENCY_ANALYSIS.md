# Transaction Boundaries, Concurrency, and Idempotency Analysis

**Purpose:** Technical analysis **before** implementing `InventoryPostingService`, domain events, and accounting extraction. Code references reflect current implementation.

---

## 1. Current transaction boundaries

| Flow | Outer transaction | Inner / nested |
|------|---------------------|----------------|
| **POS sale** [`SaleController@store`](app/Http/Controllers/POS/SaleController.php) | Single `DB::transaction(...)` wraps validation loop, `Sale::create`, items, stock decrements, movements, **manual** `JournalEntryFactory` call | `AccountingService::createJournalEntry` uses **`DB::beginTransaction`** inside → **nested transaction** (Laravel uses savepoints when supported) |
| **POS void** [`void`](app/Http/Controllers/POS/SaleController.php) | `DB::transaction` | None observed inside loop |
| **POS refund** [`refund`](app/Http/Controllers/POS/SaleController.php) | `DB::transaction` | Nested JE via `AccountingService` again |
| **Ecommerce checkout** [`CheckoutController::placeOrder`](app/Http/Controllers/Store/CheckoutController.php) | `DB::transaction` | No accounting JE in controller |
| **PO receive** [`PurchaseOrderController::receive`](app/Http/Controllers/Inventory/PurchaseOrderController.php) | `DB::transaction` | No nested accounting in snippet |

**Broadcast / Activity:** Run **after** POS sale transaction completes (`InventoryBulkUpdated`, `PosSaleCompleted`, `Activity::logSale`) — good for “external world” side effects.

---

## 2. DB transaction nesting (risks)

1. **AccountingService inner transaction** commits journal lines **independently** if outer layer mishandles exceptions — today exceptions **do** roll back outer sale transaction if thrown from inner path (same request). Risk: **partial commit** if nested behavior differs by driver.

2. **`Sale::create` fires `AccountingObserver`** → synchronous JE attempt **before** line items exist. Combined with **controller’s second JE**, yields **duplicate journals** or ordering bugs — **structural risk**, not nesting alone.

**Recommendation (ADR-002):** Post-accounting **afterCommit** only; collapse to **one** listener; simplify `AccountingService` to **not** nest transactions when called from an existing transaction (optional `withinExistingTransaction` parameter).

---

## 3. Where `afterCommit` should be introduced

| Concern | Why |
|---------|-----|
| **Domain events** (`SaleRecorded`, `StockPosted`) | Listeners must run only if sale/movements **committed** |
| **Journal posting** | Avoid journals for rolled-back sales |
| **Broadcast to other terminals** | Other POS instances must not act on uncommitted stock — today broadcast runs after transaction; **keep** or tie to `SaleRecorded` dispatched after commit |
| **Webhooks / queues (future)** | Always after commit |

**Concrete:** `DB::afterCommit(fn () => Event::dispatch(new SaleRecorded(...)))` at end of successful sale transaction, or Laravel 10+ `transaction()->afterCommit()`.

---

## 4. Unsafe under concurrency (lost updates / overselling)

| Flow | Issue |
|------|--------|
| **POS checkout** | Pre-validation reads `stock_quantity` without comparing to **cart reservations**; two terminals can pass validation if both read before either decrements — **classic oversell** unless row locks cover all lines for full checkout duration. Today **`lockForUpdate()` per product** in loop reduces risk **within one request**, not across concurrent requests. |
| **Ecommerce checkout** | `Product::lockForUpdate()` per product in loop — **same** cross-request race between two buyers. |
| **PO receive** | Increments warehouse + product; concurrent receives **same line** could double-count if not idempotent — usually prevented by PO status gate (`received`). |
| **Refund restock** | Uses **ratio** × line qty; concurrent refunds could **over-restore** if not gated by cumulative refunded amount (business rule). |
| **Void** | Full restore per line — concurrent void + refund paths need **business rules** (usually void blocks if refunds exist — **verify**). |

---

## 5. Idempotency analysis

| Flow | Idempotent? | Notes |
|------|-------------|-------|
| **POS sale** | **Partial** | `client_idempotency_key` on `sales` returns duplicate response — **good**. Requests **without** key can duplicate sales on retry. |
| **Ecommerce checkout** | **Weak** | No idempotency key; double-submit can duplicate **orders** and double decrement stock. |
| **PO receive** | **Weak** | Guard `status === 'received'` prevents double receive of **same** PO object; replay HTTP could still race — rely on status. |
| **Refund** | **Weak** | No idempotency key; duplicate POST could double refund amount — **dangerous** without unique client token. |
| **Void** | **Weak** | Second call returns error if already voided — acceptable. |
| **Stock adjustments** (if manual UI) | **Unknown** | Must use idempotent reference or movement uniqueness per tool. |

---

## 6. Multi-terminal POS breakage scenarios

- **Realtime catalog:** `InventoryBulkUpdated` broadcasts **after** sale; other terminals may show stale stock until refresh — tolerable; **optimistic** cart merge can sell last unit **twice** without reservations (see §4).
- **Held orders / offline queue:** Offline sale replay uses idempotency key — **strong** for sync path.

**Mitigation direction:** warehouse-level **available = qty − reserved** checks + optional short TTL reservation at checkout start (future).

---

## 7. Omnichannel stock sync breakage

- **POS** and **web** both decrement `products.stock_quantity` — **no unified reservation**, so total sold can exceed physical if races align poorly.
- **Warehouse rows** updated only on POS when shift has `warehouse_id`; ecommerce may **not** touch same rows — **channel skew** until ADR-001 posting service unifies writes.

---

## 8. Flow-specific notes

### POS checkout

- Transaction: **single outer**.
- **Risk:** duplicate JE (observer + controller); nested accounting transaction.
- **Idempotency:** key support exists.

### Ecommerce checkout

- Transaction: **single outer**; **no** JE in controller — accounting gap.
- **Risk:** double-submit orders.

### PO receiving

- Status transition to `received`; observer expects `completed` — **procurement accounting gap** (ADR-002).

### Refunds

- **Pro-rata** restock by amount/total ratio — **not** always equal to original line quantities; warehouse rows **not** restored in snippet — **inventory drift** vs original sale path.

### Void

- Restores **only** `products.stock_quantity` and movement; **does not** reverse `product_warehouse_stocks` — **warehouse drift** if POS sale decremented warehouse.

### Stock adjustments

- Any ad-hoc adjustment UI must eventually call **`InventoryPostingService`** with reason code — avoid parallel conventions.

---

## 9. Summary table (foundation priorities)

| Priority | Topic |
|----------|--------|
| P0 | Resolve duplicate sale JE path (ADR-002) |
| P1 | Introduce `afterCommit` for accounting/events |
| P1 | Unify inventory posting (ADR-001) |
| P2 | Idempotency tokens for refund + ecommerce submit |
| P2 | Align void/refund with warehouse rows |
| P3 | Reservations for high-concurrency retail |

---

*This document should be updated after each extraction wave when transaction boundaries change.*
