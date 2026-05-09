# ADR-005: Domain Event Strategy

## Status

Proposed

## Context

The application uses Laravel’s event system minimally for accounting (`AccountingObserver` effectively couples models to side effects). Broadcast events exist for POS (`InventoryBulkUpdated`, `PosSaleCompleted`). Foundation extraction requires **clear module communication** without controllers calling across bounded contexts.

## Decision

### Internal domain events (primary)

Use **Laravel events** (`App\Events\Domain\...`) for **first-party** reactions: accounting listeners, notifications, cache invalidation, audit projections.

- **Default:** **synchronous** dispatch during HTTP request for correctness and simpler debugging in foundation phase.
- **Queued listeners:** Introduce per-listener when throughput requires; must be **idempotent** (see below).

### Broadcast vs domain events

| Mechanism | Use |
|-----------|-----|
| **Domain events** (`SaleRecorded`, …) | **Inside** application; drive listeners |
| **Broadcast events** (`PosSaleCompleted`, …) | **Optional** UI/realtime; subscribe **from** domain listener or separate thin broadcaster that maps domain payload → websocket |

Do **not** use broadcast as the only record of business facts.

### Naming conventions

- **Past tense** for completed facts: `SaleRecorded`, `StockPosted`, `GoodsReceived`, `RefundRecorded`.
- Namespace: `App\Events\Domain\Sales\SaleRecorded`, etc.

### Payload contracts

- **Minimal IDs + immutable snapshot fragments** (totals, tenant_id, channel, correlation id).
- **Version field** optional: `payloadVersion: 1` for evolving shapes.
- **No** full Eloquent graphs in payloads — listeners reload from DB when needed.

### Listener ownership

| Listener area | Owns |
|---------------|------|
| `PostSaleJournalListener` | Accounting JE |
| `BroadcastPosSaleListener` | Maps to `PosSaleCompleted` if needed |
| `NotifyLowStockListener` | Notifications |

Listeners **must not** call back into arbitrary controllers.

### Replay / idempotency

- **Journal listeners:** Use **deterministic reference** (`reference` = `SALE-{sale_number}`) and **unique constraint** or **check-before-insert** on `journal_entries` source `(source_type, source_id)` if available.
- **Queued listeners:** Store **processed event id** or use **idempotency key** on journal header.
- **Replay:** Manual replay tool (future) re-dispatches event with admin guard — not automatic replay of full event log in v1.

## Explicit answer: How do modules communicate long-term?

**Through domain events + listener interfaces + explicit DTO contracts.** HTTP and broadcast are **adapters**, not the integration backbone.

## Consequences

### Positive

- Testable; replaceable listeners; clear seams for manufacturing/MO events later.

### Negative

- Requires discipline to avoid fat listeners; keep orchestration in application services.

## Compliance

Revisit when: outbox pattern, message broker, or cross-service boundaries are introduced.
