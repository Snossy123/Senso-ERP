# Slice E — Single Accounting Path (POS Sale)

## 1. Duplicate accounting issue fixed

Previously, POS sale revenue journals could be created twice for one sale:

- **`AccountingObserver`** reacted to **`Sale::created`** and posted a journal entry.
- **`SaleController::store`** also called **`JournalEntryFactory`** + **`AccountingService`** inline after persisting the sale.

Slice E removes that duplication by posting **only one** journal entry per POS sale, triggered **after** the sale transaction **commits**.

## 2. `SaleRecorded` domain event

**File:** `app/Events/Domain/Sales/SaleRecorded.php`

Payload (versioned for future channels):

| Field            | Purpose                                      |
|-----------------|-----------------------------------------------|
| `saleId`        | Primary key of the persisted `Sale`           |
| `tenantId`      | Tenant scope for downstream logic             |
| `channel`       | e.g. `pos` (default); reserved for ecommerce  |
| `payloadVersion`| Bump when payload shape or semantics change   |

The event is **internal application plumbing**: it signals “this sale is durable and ready for side effects,” not a public API contract.

## 3. Listener behavior

**File:** `app/Listeners/Accounting/PostSaleJournalListener.php`

- Handles **`channel === 'pos'`** only (other channels are future slices).
- Loads **`Sale` with `items`**.
- **Idempotency:** if a **`journal_entries`** row already exists with `source_type = Sale::class` and `source_id = sale id`, the listener **returns without creating** another entry.
- Otherwise uses existing **`JournalEntryFactory::getGenerator($sale)`** and **`AccountingService::createJournalEntry`** (same generator/service as before).
- Errors are **`report($e)`** only: accounting failure does **not** roll back an already-committed sale (operational choice for post-commit side effects).

## 4. After-commit behavior

**File:** `app/Http/Controllers/POS/SaleController.php`

Inside the sale **`DB::transaction`**, the controller registers:

```php
DB::afterCommit(function () use ($sale, $tenant) {
    event(new SaleRecorded(...));
});
```

That ensures:

- **`sale_items`** and inventory postings inside the same transaction are committed **before** accounting runs.
- If the transaction **rolls back**, **`afterCommit`** callbacks for that transaction **do not run**, so **no journal** is created for a failed sale.

## 5. Idempotency protection

The listener checks for an existing journal by **`Sale`** as source **before** calling the factory. This guards:

- Accidental **double dispatch** of `SaleRecorded`
- **Replay** or duplicate listener execution (e.g. if listeners become queued later)
- Tests can assert “dispatch twice → still one JE”

## 6. Remaining accounting gaps (not Slice E)

| Area              | Status                                      |
|-------------------|---------------------------------------------|
| Ecommerce sale JE | Not moved to `SaleRecorded`/listener yet    |
| PO receive / GRN  | Still via **`AccountingObserver`** on PO    |
| Refund JE         | Still inline in **`SaleController::refund`**|

These are intentional **out of scope** for Slice E to keep the change minimal and POS-focused.

## 7. Why events are internal only for now

`SaleRecorded` is dispatched **synchronously** after commit from the controller; it is **not** exposed as a webhooks or multi-tenant event bus API. That keeps the seam small: one listener, one code path, easy to test. Queueing or external publication can be layered on once channel-specific payloads and failure handling are defined.

## 8. Operational implications (after-commit accounting)

Slice E intentionally moved POS revenue journaling from **inside** the sale DB transaction to **after commit**:

| Scenario | Behavior |
|----------|----------|
| Sale commits successfully | `SaleRecorded` fires; listener creates the journal entry (or skips if idempotent duplicate). |
| Accounting fails after commit | The sale stays **`completed`** and inventory remains posted. The listener catches exceptions and **`report($e)`** only — it does **not** roll back the sale (post-commit side effects cannot undo the transaction anyway). |
| Monitoring | A future **operations dashboard** should surface failed accounting postings (e.g. log channel, dead-letter queue, or reconciliation job comparing sales without matching `journal_entries`). **Not built in Slice E.** |

This matches common ERP patterns: financial posting failures are **operational** problems (retry, manual JE, alerts), not automatic reversal of fulfilled sales.
