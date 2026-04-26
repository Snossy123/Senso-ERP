# Administration – Business Requirements Document (BRD)

## 1. Purpose

This document defines **business goals**, **scope**, and **success criteria** for the **Administration** area of Senso-ERP: centralized control over **who** can use the system (**users**), **what** they may do (**roles** and permissions), **which organizations** are onboarded (**tenants**), **how** the product behaves for each tenant (**settings**), and **evidence** of important changes (**activity log**).

The ERP remains the **system of record** for identity, authorization policy, tenant lifecycle, and operational configuration.

---

## 2. Vision

Give platform operators and tenant administrators a **coherent, auditable** place to:

- Onboard and offboard staff safely, with least-privilege access.
- Standardize permission bundles through roles while allowing justified exceptions.
- Govern SaaS tenants (plans, status, suspension) without losing traceability.
- Tune tenant-level business and operational defaults without developer intervention.
- Investigate security and support incidents using structured activity history.

---

## 3. Stakeholders

| Stakeholder | Interest |
| ----------- | -------- |
| Platform / super admin | Tenant lifecycle, impersonation for support, global compliance |
| Tenant admin / owner | Users, roles, branch assignment, local policy (settings) |
| Security / compliance | Audit trail, lockout policy, password hygiene |
| Support | Fast diagnosis via activity log and tenant context |
| End users (staff) | Stable login, clear permissions, no cross-tenant data exposure |

---

## 4. Scope

### 4.1 In scope

- **User Management**: create, view, edit, deactivate users; assign role, branch, and optional direct permissions; lock/unlock; password reset and forced password change; filters for discovery.
- **Role Management**: define roles per tenant model; attach permission sets; activate/deactivate roles; prevent deletion when roles are in use (business rule).
- **Tenant Management**: create tenants; assign plans; trial and subscription dates; suspend/activate; usage sync; “login as” for support; per-tenant regional/tax preferences where modeled.
- **Settings**: grouped tenant-scoped configuration (business, localization, sales, inventory, security, notifications) including file-backed assets such as logos where applicable.
- **Activity Log**: browse, filter, and export administrative and system-relevant events with severity and optional before/after snapshots.

### 4.2 Out of scope (unless explicitly added later)

- Customer storefront identity (separate guards/modules).
- Full SIEM replacement (retention, alerting pipelines are product decisions beyond this BRD).
- Automated provisioning from external IdP (SCIM) unless a dedicated integration module is added.
- Legal document management (DPA, contracts) as primary objects in this module.

---

## 5. Business objectives

1. **Least privilege**: Default access is minimal; elevation is explicit via roles or granted permissions.
2. **Tenant isolation**: Users, roles, settings, and most auditable actions are interpreted in **tenant context**; cross-tenant leakage is unacceptable.
3. **Operational resilience**: Account lockout and session-related controls reduce abuse while keeping recovery paths for admins.
4. **Commercial control**: Tenant status, plans, and suspension align billing and product access with business reality.
5. **Accountability**: Material changes are attributable to a user (or system) with enough context for audits.

---

## 6. Success metrics (examples)

| Metric | Intent |
| ------ | ------ |
| Time to grant a new hire correct access | Role + branch assignment efficiency |
| Permission-related support tickets | Clarity of roles vs ad-hoc grants |
| Failed login / lockout incidents handled without escalation | Self-service admin recovery |
| Mean time to identify “who changed what” | Activity log usefulness |
| Tenant churn / upgrade cycle time | Tenant management UX and data quality |

---

## 7. Assumptions and constraints

- Authentication is handled by the core Laravel stack; Administration configures **authorization** and **tenant-scoped policy**, not the primary IdP protocol itself.
- **Settings** values are stored per tenant in the application database; sensitive secrets should follow organizational secret-management policy (not all secret types belong in plain settings rows).
- **Activity Log** visibility may be restricted to highly trusted roles (e.g. platform admin) even when other admin screens are available to delegated admins.

---

## 8. Related documents

- [FRD.md](./FRD.md) — functional requirements
- [TDD.md](./TDD.md) — technical design (schema, routes, services)
- [UX-flow-diagram.md](./UX-flow-diagram.md) — navigation and primary journeys
