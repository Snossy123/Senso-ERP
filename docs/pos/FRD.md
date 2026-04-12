# POS Module – Functional Requirement Document (FRD)

## 1. Overview

This document defines the functional requirements for the POS module of Senso-ERP. It details user interactions, workflows, system behavior, validations, and integration points with inventory and accounting.

---

## 2. Actors

* **Cashier:** Operates POS terminal, processes sales, refunds.
* **Store Manager:** Approves discounts, overrides, and refunds.
* **Accountant:** Reviews journals, reconciliations, and reports.
* **Admin:** Configures accounts, taxes, POS settings.

---

## 3. Modules & Functional Components

### 3.1 POS Terminal

**Functions**

* Product search by name, SKU, barcode
* Product category browsing
* Product grid view with image, price, stock indicator
* Add/remove items to cart
* Modify quantities
* Apply item-level discount
* Apply order-level discount
* Select customer
* Add notes to order
* Hold/resume orders
* Real-time stock validation

**Validations**

* Product must exist
* Quantity cannot exceed available stock (unless backorder enabled)
* Discount rules validated against user permission

---

### 3.2 Cart & Pricing Engine

**Calculations**

* Subtotal
* Item discounts
* Order discounts
* Tax
* Grand total
* Change amount (for cash)

**Rules**

* Taxes applied based on product/category tax rule
* Discount caps enforced
* Negative totals not allowed

---

### 3.3 Checkout & Payment Processing

**Supported Payment Methods**

* Cash
* Card
* Bank transfer
* Split payments

**Workflow**

1. Validate cart
2. Select payment method(s)
3. Confirm payment
4. Generate receipt
5. Update inventory
6. Trigger accounting event

**Validations**

* Total paid must be ≥ total due
* For split payments, sum of payments = total
* Payment method must be mapped to account

---

### 3.4 Shift Management

**Open Shift**

* Cashier selects register
* Inputs opening cash
* System creates POS session

**During Shift**

* Track cash sales
* Track refunds
* Track cash movements

**Close Shift**

* Calculate expected cash
* Enter actual cash
* System calculates variance
* Post reconciliation entry

---

### 3.5 Refunds & Returns

**Capabilities**

* Refund full or partial sale
* Restore inventory
* Reverse journal entries
* Refund via original payment method

**Validations**

* Refund cannot exceed original sale
* Permission required for refunds

---

### 3.6 Inventory Integration

**On Sale**

* Deduct stock
* Record stock movement

**On Refund**

* Restore stock
* Record reverse stock movement

**Rules**

* No negative stock unless allowed
* Multi-warehouse aware

---

### 3.7 Accounting Integration

**Automated Posting**

* Triggered by events (SaleCompleted, RefundCompleted)

**Journal Entries**

* Debit Cash / Bank / Card Clearing
* Credit Revenue
* Credit Tax Payable

**Rules**

* All entries must balance
* Use account mapping from Account Settings
* No hardcoded accounts

---

### 3.8 Customer & Receipts

**Customer**

* Optional selection
* Track sales history

**Receipts**

* Printable receipt
* Includes:

  * Items
  * Tax
  * Discounts
  * Payment method
  * Change
  * QR code (optional)

---

### 3.9 Permissions & Security

**Permissions**

* POS access
* Discount override
* Price override
* Refund permission
* Shift management

**Security**

* Role-based access
* Audit log for critical actions

---

### 3.10 Reporting

**Reports**

* Sales by day
* Sales by product
* Sales by cashier
* Payment method breakdown
* Shift summary

---

## 4. System Integrations

* Accounting module (journal entries)
* Inventory module (stock movement)
* Customer module
* Tax module
* Payment gateways (future)

---

## 5. Error Handling

* Out-of-stock warning
* Invalid payment method
* Unbalanced journal entry
* Closed accounting period
* Permission denied

---

## 6. Acceptance Criteria

* Sale can be completed end-to-end
* Inventory updates correctly
* Journal entry created correctly
* Refund reverses correctly
* Shift close reconciles
* Permissions enforced
