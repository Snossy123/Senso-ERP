# ADR-002: Accounting Trigger Ownership

## Status

Proposed

## Context

Journal entries are created through multiple mechanisms:

1. **`AccountingObserver`** — on `Sale::created` and on `PurchaseOrder::updated` when `status === 'completed'`.
2. **`SaleController@store`** — after creating sale lines, calls `JournalEntryFactory` + `AccountingService::createJournalEntry` **inside** the same `DB::transaction` as the sale.
3. **`SaleController@refund`** — creates refund JE inside transaction after `SaleRefund` create.
4. **`PurchaseOrderController::receive`** — sets PO status to **`received`** (not `completed`), so observer condition may never match.
5. **`AccountingService::createJournalEntry`** — opens its **own** `DB::beginTransaction`/`commit` nested inside outer transactions.

Ecommerce `CheckoutController` creates **orders** without the same journal pipeline as POS sales (channel gap).

## Decision

### Single orchestration path

**Accounting posting for automated ERP documents is owned by application-layer reactions to domain outcomes**, not by HTTP controllers and not by duplicate Eloquent observers for the same aggregate.

**Target pattern:**

1. **Persist business transaction** (sale, refund, goods received) in **one outer `DB::transaction`**.
2. **`DB::afterCommit`** (or equivalent): dispatch **domain events** (`SaleRecorded`, `RefundRecorded`, `GoodsReceived`).
3. **Listeners** invoke existing **`JournalEntryFactory` generators** + **`AccountingService::createJournalEntry`** once per document.

### Observer vs event-driven

- **Remove** accounting responsibility from **`AccountingObserver`** for `Sale` (and align `PurchaseOrder` with `GoodsReceived` event instead of fragile status string match).
- **Prefer** explicit **`SaleRecorded` listener** over model `created` hooks for journals — easier to test and to run **after** line items exist and **after** commit.

### Transaction timing

- **Journal posting must not run** if the business transaction rolls back.
- **Recommendation:** dispatch events **`afterCommit`** so listeners never see phantom sales.
- **Nested `AccountingService` transaction:** Refactor to either:
  - participate in outer transaction (remove inner begin/commit when called from within an active transaction), **or**
  - always invoke accounting listeners **after** outer commit only (then inner transaction in `AccountingService` is acceptable as isolated).

**Explicit rule:** No journal for a sale that did not commit.

### afterCommit behavior

- **`SaleRecorded`**, **`RefundRecorded`**, **`GoodsReceived`** listeners: register as **`ShouldQueue` false** initially for simplicity; queue later for scale with idempotent consumer keys.

### Ecommerce accounting timing

**Decision (to be confirmed per business — default recommendation):**

- **Option A (operations-first):** Journal on **order placement** when stock is decremented and obligation is fixed (cash-on-delivery / pending payment still posts revenue policy — **requires policy**).
- **Option B (cash accounting):** Journal on **payment captured** only.

Document chosen policy in tenant settings (`commerce.revenue_recognition = on_place | on_paid`).

Until decided, **document current state:** ecommerce orders may have **no** automatic JE — gap to close.

### Refund journal strategy

- **Ownership:** `RefundRecorded` event → **`RefundJournalEntryGenerator`** via listener (same as today’s manual path in `refund()`).
- **Timing:** After refund row and optional stock restore commit.

### Procurement accounting timing

- **Ownership:** On **goods received** (final inventory increase committed), emit **`GoodsReceived`** with PO id and lines → **`PurchaseJournalEntryGenerator`** listener.
- **Align PO status:** Either rename observer expectation to **`received`** or emit event only from `receive()` path.

## Explicit answer: Who owns accounting posting?

**The Accounting bounded context**, invoked **only** through **listeners** subscribed to **domain events** emitted **after commit** (once duplicate observer/controller paths are removed). Controllers **do not** call `AccountingService` directly long-term.

## Consequences

### Positive

- Single JE per sale; audit clarity; testable subscribers.

### Negative / work

- Must eliminate duplicate `Sale` observer + controller JE (verify production today).
- Refactor `AccountingService` nesting to avoid deadlocks or partial commits.
- Ecommerce policy decision required.

## Compliance

Revisit when: new document types (MO, payroll); multi-currency; period close locks.
