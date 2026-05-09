# ADR-004: Catalog vs Inventory Ownership

## Status

Proposed

## Context

Today `Product`, `ProductVariant`, categories, and pricing largely live under **inventory** controllers and models. POS consumes **mapped** product DTOs from `productsFeed`. Future needs include configurable attributes, modifiers, bundles, BOM references, and channel-specific pricing — mixing these into inventory posting logic would tightly couple catalog complexity to stock mutations.

## Decision

### Bounded context split

| Concern | Owner |
|---------|--------|
| **Catalog** | Product definition: identity, descriptions, media, **features/options/modifiers (future)**, bundle composition **definitions**, BOM **structure** as design-time graph, list/catalog pricing metadata, SEO/slug for ecommerce |
| **Inventory** | **Stock-bearing** identities: what gets moved, reserved, costed — quantities per warehouse, movements, valuation inputs tied to physical flow |
| **Sales** | **Commercial snapshots** at checkout: line prices, discounts, frozen option/modifier selections on the **sales document** |

### Products and variants

- **Catalog** owns **what** a product/variant **is** (attributes, SKUs as identifiers).
- **Inventory** owns **how much** exists for each stockable SKU key at each warehouse.

### Modifiers and features (future)

- **Catalog** defines modifier groups/options and rules (required, min/max, price adjustments).
- **Sales** persists **which** options were chosen on `sale_items` (or child tables / JSON snapshot).
- **Inventory** optionally links a modifier option to a **component SKU** that decrements stock when the option is selected (policy in Catalog: `consumes_inventory = true`).

### BOM / recipes

- **Catalog (or future Manufacturing package)** owns BOM **structure**.
- **Manufacturing / Inventory** owns **issue/receipt** movements when work orders execute.

### Configurable product logic

**See “Explicit answer” below** — definitions in Catalog; execution in Inventory/Sales as described.

## Explicit answer: Where does configurable product logic live?

**Primarily in Catalog** (definitions, constraints, valid combinations, generator rules for SKUs). **Inventory** only receives **resolved stockable units**. **POS** is a **client** of Catalog read APIs + Sales write APIs — **not** the owner of configuration rules.

## Consequences

### Positive

- POS thin client; ecommerce and POS share same catalog surface.

### Negative / work

- Requires gradual move of “product editor” mental model under `Domain/Catalog` or `Modules/Catalog` without moving every file day one.

## Compliance

Revisit when: first modifier schema ships; when BOM module is introduced.
