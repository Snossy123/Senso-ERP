# POS Module – Business Requirements Document (BRD)

## 1. Purpose

This document defines the business requirements for the Point of Sale (POS) module within the Senso-ERP system. The POS is responsible for retail sales transactions, payment processing, real-time inventory updates, and integration with accounting.

The POS must be fast, reliable, and tightly integrated with inventory, accounting, and user permissions.

---

## 2. Scope

### Included

* POS sales operations
* Shift management
* Product catalog & inventory sync
* Payments & split payments
* Refunds & returns
* Automatic accounting integration
* Receipt generation
* Role-based permissions
* Multi-tenant support

### Out of Scope

* Procurement
* Advanced reporting
* Payroll
* CRM automation

---

## 3. Users & Roles

| Role          | Description                                    |
| ------------- | ---------------------------------------------- |
| Cashier       | Performs sales, payments, and refunds          |
| Store Manager | Approves discounts, voids, overrides           |
| Finance       | Reviews accounting entries and reconciliations |
| Admin         | Configures accounts, taxes, and mappings       |

---

## 4. Functional Requirements

### 4.1 POS Terminal

The cashier must be able to:

* Search products by name, SKU, or barcode
* Browse categories
* Add/remove items to cart
* Change quantities
* Apply item-level discounts
* Apply order-level discounts
* Attach a customer
* Add notes
* Hold/resume orders
* See stock availability
* Complete checkout

---

### 4.2 Checkout & Payments

Supported payment methods:

* Cash
* Card
* Bank transfer
* Split payments

System must:

* Calculate totals, tax, and change
* Validate stock before checkout
* Support partial payments
* Generate receipt
* Store payment records per method

---

### 4.3 Shift Management

* Cashier must open a shift before sales
* Opening balance required
* Track:

  * Cash sales
  * Card sales
  * Refunds
  * Expected cash
* On close:

  * Calculate variance
  * Record cash movements
  * Create journal entries

---

### 4.4 Refunds & Returns

System must:

* Link refund to original sale
* Reverse revenue & tax
* Restore inventory
* Reverse payment (cash/card)
* Generate refund receipt

---

### 4.5 Inventory Integration

On sale:

* Deduct stock from selected warehouse
* Record stock movement

On refund:

* Restore stock

---

### 4.6 Accounting Integration (Automated)

For each sale:

* Debit POS Cash / Bank / Card clearing
* Credit Sales Revenue
* Credit Tax Payable

For refunds:

* Reverse original journal entries

Accounts must be dynamically mapped via Account Settings (no hardcoded accounts).

---

## 5. Non-Functional Requirements

* Response time < 1 second for common actions
* Offline mode (future)
* Responsive design (tablet + desktop)
* Role-based access control
* Audit logs for all financial actions

---

## 6. Business Rules

* Sales cannot proceed without an open shift
* Discounts above threshold require manager approval
* Refunds require permission
* Inventory cannot go negative (unless backorder enabled)
* Closed accounting periods cannot be modified
* All journal entries must balance (debit = credit)

---

## 7. Reports

Minimum reports:

* Sales summary
* Payment breakdown
* Shift summary
* Product sales
* Cash reconciliation

---

## 8. Integration Points

* Accounting (journal entries)
* Inventory (stock movements)
* Customer accounts
* Tax configuration
* Payment gateways (future)

---

## 9. Assumptions & Constraints

* System is multi-tenant
* Each tenant has separate COA
* Account mappings configured in settings
* Internet required for sync (offline later)

---

## 10. Acceptance Criteria

* Cashier can complete full sale flow end-to-end
* Inventory updates correctly
* Journal entries generated correctly
* Refund reverses correctly
* Shift reconciliation works
* Permissions enforced
