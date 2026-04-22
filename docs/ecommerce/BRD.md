# Ecommerce – Business Requirements Document (BRD)

## 1. Purpose

This document defines **business goals**, **scope**, and **success criteria** for the ecommerce capability within the multi-tenant ERP: a **customer-facing Store Portal** that sells catalog items managed in inventory, with **staff-controlled branding and layout** via the storefront builder, without duplicating product or stock as a separate silo.

The ERP remains the **system of record** for products, pricing, stock, orders, and tenants.

---

## 2. Vision

Enable each tenant to operate a **professional online storefront** that:

- Reflects approved products and real availability (or policy-defined sell rules).
- Supports **guest and registered** shopping journeys through cart and checkout.
- Keeps **order and revenue data** in the same operational stack as POS and back-office.
- Allows **controlled customization** of pages and themes without breaking commerce flows.

---

## 3. Stakeholders

| Stakeholder | Interest |
| ----------- | -------- |
| Tenant / merchant | Revenue, brand, catalog control, order fulfillment |
| End customer | Discovery, trust, fast checkout, order visibility |
| Operations / warehouse | Accurate orders, picking, stock alignment |
| Finance | Revenue recognition, reconciliation with accounting |
| Platform admin | Tenant isolation, fair use, supportability |

---

## 4. Scope

### 4.1 In scope

- Public **shop** (home, listing, product detail paths as implemented).
- **Shopping cart** (session-based) and **checkout** with order creation.
- **Customer accounts** (registration, login, profile, order history) where enabled.
- **ERP admin**: product/category flags for ecommerce, order management, storefront configuration (builder / studio).
- **Multi-tenant** data isolation for catalog and orders.
- **Published storefront** snapshots (draft vs published) for stable customer experience.

### 4.2 Out of scope (unless explicitly added later)

- Marketplace across tenants.
- Native mobile apps (responsive web is in scope).
- Full marketing automation (email campaigns, loyalty as separate modules).
- Global tax engine beyond current implementation.
- Third-party marketplace sync (Amazon, etc.) as part of this BRD.

---

## 5. Business objectives

1. **Single source of truth**: Product master, price, and stock live in ERP; the store **displays** and **sells**—no parallel catalog DB for merchants to reconcile manually.
2. **Conversion**: Minimize friction from browse → cart → checkout; support guest checkout where policy allows.
3. **Operational efficiency**: Orders appear in the same pipeline as other channels (admin orders, fulfillment).
4. **Brand flexibility**: Merchants can adjust layout/slots and templates within guardrails so upgrades remain safe.
5. **Trust & compliance**: Tenant isolation; no cross-tenant data leakage; auditability of publish actions.

---

## 6. Success metrics (examples)

| Metric | Intent |
| ------ | ------ |
| Checkout completion rate | Reduce abandoned carts vs baseline |
| Order error rate | Wrong SKU / price / stock conflicts |
| Time to publish storefront changes | Operational agility |
| Support tickets (storefront) | Builder usability |
| Page load / API health | Availability SLAs |

---

## 7. Assumptions & constraints

- Products intended for the web are flagged and maintained in inventory (e.g. ecommerce-enabled, active).
- Payment and fulfillment rules follow tenant configuration and regional constraints already modeled in the application.
- Heavy visual editing (Visual Store Studio) remains **compatible** with the existing render pipeline (`/store` + published snapshot), not a parallel storefront runtime.

---

## 8. Related documents

- [FRD.md](./FRD.md) — functional requirements.
- [TDD.md](./TDD.md) — technical design.
- [UX-flow-diagram.md](./UX-flow-diagram.md) — customer and admin journeys.
