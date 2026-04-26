# Administration – Functional Requirements Document (FRD)

## 1. Purpose

This document specifies **functional behavior** of the Administration module: **User Management**, **Role Management**, **Tenant Management**, **Settings**, and **Activity Log**, as exposed in the ERP sidebar under **Administration**.

**Readers**: product, QA, engineering.

---

## 2. Definitions

| Term | Meaning |
| ---- | ------- |
| Tenant | An isolated organization context in the multi-tenant ERP |
| Role | Named bundle of permissions; may be tenant-scoped |
| Direct permission | Permission attached to a user outside the role (override / supplement) |
| Activity | A persisted audit row describing an action, actor, target model, and metadata |
| Platform admin | User with unrestricted admin capabilities (e.g. `isAdmin()`), including full Activity Log |

---

## 3. User roles (functional)

| Actor | Capabilities (high level) |
| ----- | ------------------------- |
| Platform admin | All administration areas; Activity Log; tenant lifecycle including impersonation |
| Delegated admin (`users.view`, `roles.view`, etc.) | Subset per permission checks on controllers |
| Staff (no admin permissions) | No access to Administration routes (403) |

Exact permission slugs are defined in seed data; controllers gate **User** and **Role** management with `users.*` / `roles.*` style checks where implemented.

---

## 4. User Management

**FR-ADM-UM-001** The system shall list users with pagination and support filters including **search** (name, email), **role**, **branch**, **active** flag, and **locked** state where implemented.

**FR-ADM-UM-002** The system shall allow authorized users to **create** a user with validated fields: name, email (unique), optional password (with confirmation rules), phone, role, branch, optional permission ids, active flag, and “must change password” flag.

**FR-ADM-UM-003** The system shall allow **view** of a user detail page including related role, branch, creator, effective permissions, and recent activity for that user where the service provides it.

**FR-ADM-UM-004** The system shall allow **edit** of user attributes consistent with validation rules (including reassignment of role, branch, and permission attachments).

**FR-ADM-UM-005** The system shall support **toggle active** status for a user via dedicated POST routes; callers must be authenticated (extend permission checks if product policy requires beyond list/view).

**FR-ADM-UM-006** The system shall support **lock** and **unlock** account operations reflecting failed-login / lockout policy in the user management service.

**FR-ADM-UM-007** The system shall support **admin-initiated password reset** and **force password change** flows for a selected user.

**FR-ADM-UM-008** New users shall be associated with the **current tenant** when tenant id is not explicitly supplied, preserving multi-tenant integrity.

---

## 5. Role Management

**FR-ADM-RM-001** The system shall list roles for the tenant context with indicators needed for administration (name, slug, active, permission coverage as presented in UI).

**FR-ADM-RM-002** The system shall allow **create role** with name, description, optional permission id list, and active flag; response shall confirm success and return the new role id (JSON API pattern used by controller).

**FR-ADM-RM-003** The system shall allow **edit role** with the same field shape as create; permissions shall replace or reconcile per service implementation.

**FR-ADM-RM-004** The system shall allow **delete role** when business rules permit; otherwise return an error message (e.g. role in use).

**FR-ADM-RM-005** The system shall expose a **read permissions for role** JSON endpoint for integrations or UI hydration.

---

## 6. Tenant Management

**FR-ADM-TM-001** The system shall list tenants with plan relationship and pagination.

**FR-ADM-TM-002** The system shall allow **create tenant** with name (slug derived), optional domain (unique), optional settings payload, optional plan, trial days, currency, language, timezone; initial status shall follow product rules (e.g. trial).

**FR-ADM-TM-003** The system shall present a **tenant detail** view including related users, products, sales, orders, usage tracking summaries, and computed limit checks where the tenant service provides them.

**FR-ADM-TM-004** The system shall allow **update tenant** including name/slug sync, domain uniqueness, plan changes via dedicated service when plan id changes, status enum, subscription/trial dates, tax settings, and notes.

**FR-ADM-TM-005** The system shall **prevent delete** when users still exist on the tenant, with user-visible error feedback.

**FR-ADM-TM-006** The system shall support **toggle** `is_active` for quick enable/disable.

**FR-ADM-TM-007** The system shall support **suspend** with optional reason and **activate** flows that delegate to `TenantService`.

**FR-ADM-TM-008** The system shall support **plan upgrade** with validated plan id.

**FR-ADM-TM-009** The system shall support **login as** tenant user: default first user or explicit `user_id` when provided; session shall record impersonation context for safe return flows (product-dependent).

**FR-ADM-TM-010** The system shall support **usage sync** action to refresh usage statistics from the service layer.

**FR-ADM-TM-011** The system shall support **PATCH-style tenant settings** update for currency, language, timezone, and optional tax settings from the tenant detail workflow.

---

## 7. Settings

**FR-ADM-ST-001** The system shall present settings grouped by **business**, **localization**, **sales**, **inventory**, **security**, and **notifications** (plus UI “general” group handling on save).

**FR-ADM-ST-002** The system shall persist each key under a tenant id resolved from `TenantManager` (current tenant), with cache invalidation on write.

**FR-ADM-ST-003** The system shall support types including string, boolean (checkbox normalization), integer, select, and **file** uploads stored on the public disk with path persisted as the value.

**FR-ADM-ST-004** On successful save, the user shall receive confirmation scoped to the group that was posted.

---

## 8. Activity Log

**FR-ADM-AL-001** The system shall list activities ordered by newest first, with pagination.

**FR-ADM-AL-002** The system shall support filters: user, type, severity, model type substring, date range.

**FR-ADM-AL-003** The system shall support **CSV export** when the export query parameter is present, streaming a file with core columns (id, date, user, type, action, severity, description, model, IP).

**FR-ADM-AL-004** The system shall provide a **detail** view for a single activity including loaded user relation.

**FR-ADM-AL-005** Access shall be restricted to **platform admin** in the current implementation (non-admin users receive 403).

---

## 9. Non-functional requirements (administration-specific)

- **Authorization**: Every administration route shall require authentication; sensitive mutations shall align with middleware and policy used in controllers.
- **Auditability**: Security-relevant user and tenant operations should emit or correlate with `activities` rows where the application logs them (exact coverage is implementation-dependent beyond this FRD).
- **Usability**: Sidebar entries shall deep-link to primary index routes: `admin.users.*`, `admin.roles.*`, `tenants.*`, `admin.settings`, `admin.activity.*`.

---

## 10. Related documents

- [BRD.md](./BRD.md)
- [TDD.md](./TDD.md)
- [UX-flow-diagram.md](./UX-flow-diagram.md)
