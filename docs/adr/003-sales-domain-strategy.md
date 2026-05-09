# ADR-003: Sales Domain Strategy

## Status

Proposed

## Context

The codebase has:

- **`sales` / `sale_items`** — POS channel, shift-linked, payment tendered, accounting integration.
- **`orders` / `order_items`** — Storefront ecommerce, session cart, different lifecycle fields.

Both reduce inventory and create movements but are **not** unified at the domain layer.

## Decision

### Short term (foundation phase — minimal breakage)

Treat **`Sale` and `Order` as separate aggregates** bound by **channel**:

| Aggregate | Channel | Typical use |
|-----------|---------|-------------|
| `Sale` | `pos` (and future in-person lanes) | Immediate payment capture, shift |
| `Order` | `ecommerce` | Session checkout; payment status may be pending |

**Unification happens at the application layer** via:

- **`SalesChannel` enum** (`pos`, `web`, `api`, …).
- **`RecordPosSaleService`** vs **`RecordWebOrderService`** implementing shared contracts (`RecordsCommerceSale` interface) with **different persistence tables** for now.

### Reporting strategy

- **Operational dashboards:** channel-specific queries initially.
- **Unified revenue view:** **read model** (database view, materialized report, or analytics export) projecting both into common columns — **not** a prerequisite for foundation extraction.

### Payment lifecycle

- **POS `Sale`:** `payment_status` / `payment_method` on sale; tendered/change.
- **Web `Order`:** `payment_method`, `payment_status` — policy per ADR-002 for when revenue posts.

### Fulfillment lifecycle

- **POS:** Implicitly fulfilled at sale completion (retail).
- **Ecommerce:** `orders.status` (pending → shipped …) — **fulfillment is separate** from payment in many setups; future **Fulfillment** subdomain can subscribe to `OrderPlaced` without merging tables.

### Omnichannel strategy (medium term)

- Add **`channel`** / **`source`** columns** to both tables if reporting requires — backward-compatible migration.
- Optional external **`order_number`/`external_ref`** for marketplace sync.

### Long term (optional, large migration)

- Single **`commerce_orders`** table with `channel` discriminator — **only** after stable services and data migration plan exist.

## Explicit answer: Are sales/orders separate aggregates or channel variants?

**Foundation phase decision:** **Separate aggregates** (`Sale` vs `Order`), distinguished by **channel semantics**, unified **behavior** via **application services** and **shared inventory posting**, not a forced table merge.

## Consequences

### Positive

- No big-bang migration; POS and web routes unchanged.

### Negative

- Duplicated reporting queries until projection layer exists.

## Compliance

Revisit when: B2B invoices, quotes, or unified order hub product requirements appear.
