# Ecommerce – Functional Requirements Document (FRD)

## 1. Purpose

This document specifies **functional behavior** of the ecommerce surface: Store Portal (`/store`), customer account area, cart/checkout, and ERP-side storefront configuration (builder, Visual Store Studio, publish/rollback).

**Readers**: product, QA, engineering.

---

## 2. Definitions

| Term | Meaning |
| ---- | ------- |
| Store Portal | Customer-facing site under route prefix `/store` |
| Guest | Shopper without a logged-in customer account |
| Customer | Authenticated store user (`auth:customer`) |
| Storefront snapshot | Published immutable configuration used for rendering |
| Draft | Unpublished storefront edits |

---

## 3. User roles (functional)

| Role | Capabilities (high level) |
| ---- | ------------------------- |
| Guest | Browse, cart, checkout (if allowed), register/login |
| Customer | Profile, order list, order detail |
| ERP staff | Products/categories for ecommerce, orders, storefront builder, publish/rollback |
| Tenant admin | Same as staff per tenant permission model |

---

## 4. Store Portal – public shop

### 4.1 Home & listing

**FR-SP-001** The system shall render a storefront **home** experience driven by storefront configuration (template, sections, layout slots) and published snapshot.

**FR-SP-002** The system shall provide navigation to **product detail** from listing or promotional sections.

### 4.2 Product detail page (PDP)

**FR-PDP-001** The system shall resolve products by **slug** for URLs under `/store/products/{slug}`.

**FR-PDP-002** The system shall display product information consistent with ERP data (name, description, price, imagery as available) for products that are **active** and **ecommerce-enabled**.

**FR-PDP-003** The system shall provide **add to cart** from PDP subject to availability rules implemented in application logic.

---

## 5. Cart

**FR-CART-001** The cart shall be **session-based** and require **no login** to add or view cart.

**FR-CART-002** Users shall update quantities or remove line items.

**FR-CART-003** Cart lines shall reference ERP products; invalid/inactive products shall not create silent corruption (handled per controller/service behavior).

**FR-CART-004** Cart totals shown shall align with line quantities and current selling price at time of display (subject to defined pricing policy).

---

## 6. Checkout & orders

**FR-CHK-001** The system shall support **guest checkout** (no forced registration before placing an order), consistent with current routes.

**FR-CHK-002** Checkout shall collect required **shipping/billing/contact** fields as defined by the checkout implementation.

**FR-CHK-003** On successful placement, the system shall create an **order** retrievable in ERP admin and show a **success** confirmation to the shopper.

**FR-CHK-004** Failure paths (validation, stock, payment if applicable) shall surface actionable feedback without losing cart unless explicitly cleared.

---

## 7. Customer account

**FR-ACC-001** Customers shall **register** and **log in** under `/store` using the dedicated customer guard.

**FR-ACC-002** Authenticated customers shall access **account dashboard**, **profile** (view/update), **orders list**, and **order detail**.

**FR-ACC-003** Guest-only flows shall not expose account-only URLs without authentication.

---

## 8. ERP – catalog eligibility

**FR-INV-001** Staff shall control which products appear on the web via **ecommerce** and **active** flags (or equivalent fields).

**FR-INV-002** Categories and merchandising shall respect tenant scope and existing inventory models.

---

## 9. ERP – orders

**FR-ORD-001** Staff shall list and inspect **customer orders** placed through the Store Portal.

**FR-ORD-002** Order status updates shall follow existing admin order workflows.

---

## 10. Storefront Builder & Visual Store Studio

**FR-SF-001** Staff shall configure storefront **settings**, **sections**, and **layout slots** per documented builder UX.

**FR-SF-002** Staff shall **publish** a storefront snapshot for live use and **rollback** to a prior version where implemented.

**FR-SF-003** **Visual Store Studio** shall allow editing **page schema (v2)** JSON and previewing the store; saving draft shall not automatically replace production until **publish**.

**FR-SF-004** Studio shall provide **read-only catalog samples** and binding-oriented metadata for commercial components (products, categories, cart summary) without granting inventory write access from the editor.

---

## 11. Non-functional (functional layer summary)

**FR-NF-001** **Tenant isolation**: catalog and orders for tenant A must not appear for tenant B.

**FR-NF-002** **Performance**: listing and PDP shall remain usable with large catalogs (pagination/virtualization as implemented).

**FR-NF-003** **Security**: draft preview and APIs shall not leak cross-tenant data (middleware and policies as implemented).

---

## 12. Acceptance themes (QA)

- Guest can complete **browse → cart → checkout → success** on a tenant with valid products.
- Registered customer can **log in** and see **their** orders only.
- Publish changes **customer-visible** pages after publish; draft does not change live until publish (per design).
- Builder/studio errors are handled without corrupting last published snapshot.

---

## 13. Related documents

- [BRD.md](./BRD.md)
- [TDD.md](./TDD.md)
- [UX-flow-diagram.md](./UX-flow-diagram.md)
