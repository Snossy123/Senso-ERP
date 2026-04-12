
---
# 🧪 POS Module – TDD Test Cases

## 1) Shift Management

### Open Shift

* `it_allows_cashier_to_open_shift_with_opening_cash`
* `it_prevents_opening_multiple_active_shifts_for_same_user`
* `it_prevents_sales_without_open_shift`

### Close Shift

* `it_calculates_expected_cash_correctly`
* `it_calculates_cash_variance_on_close`
* `it_creates_journal_entry_for_cash_variance`
* `it_marks_shift_as_closed`

---

## 2) Product & Cart

### Add to Cart

* `it_adds_product_to_cart`
* `it_merges_same_product_lines`
* `it_updates_quantity`
* `it_removes_item_from_cart`

### Validation

* `it_prevents_adding_out_of_stock_product`
* `it_respects_backorder_setting`

---

## 3) Discounts & Pricing

* `it_applies_item_discount_correctly`
* `it_applies_order_level_discount`
* `it_recalculates_tax_after_discount`
* `it_requires_manager_approval_above_discount_limit`

---

## 4) Checkout & Payments

### Single Payment

* `it_completes_cash_payment_successfully`
* `it_completes_card_payment_successfully`
* `it_records_payment_correctly`

### Split Payments

* `it_accepts_multiple_payment_methods`
* `it_validates_total_paid_equals_order_total`
* `it_rejects_payment_if_total_is_less_than_due`

### Change Handling

* `it_calculates_change_for_cash_payment`

---

## 5) Inventory Integration

* `it_deducts_stock_after_sale`
* `it_creates_stock_movement_record`
* `it_restores_stock_on_refund`

---

## 6) Accounting Integration

### Sales

* `it_creates_journal_entry_after_sale`
* `it_debits_cash_or_bank_correctly`
* `it_credits_sales_revenue`
* `it_credits_tax_payable`
* `it_ensures_journal_entry_is_balanced`

### Refunds

* `it_creates_reverse_journal_on_refund`
* `it_reverses_revenue_and_tax`
* `it_reverses_cash_or_bank_entry`

---

## 7) Refunds & Returns

* `it_allows_full_refund`
* `it_allows_partial_refund`
* `it_links_refund_to_original_sale`
* `it_prevents_refund_exceeding_original_amount`

---

## 8) Customer & Receivables

* `it_links_sale_to_customer`
* `it_updates_customer_balance_on_sale`
* `it_reduces_customer_balance_on_payment`

---

## 9) Permissions & Security

* `it_prevents_cashier_from_overriding_price_without_permission`
* `it_requires_manager_approval_for_refund`
* `it_prevents_unauthorized_shift_closure`

---

## 10) Error Handling

* `it_blocks_sale_in_closed_accounting_period`
* `it_fails_if_payment_method_not_mapped_to_account`
* `it_throws_error_for_unbalanced_journal_entry`

---

## 11) Reports

* `it_calculates_sales_summary_correctly`
* `it_groups_sales_by_payment_method`
* `it_generates_shift_report`

---

## 12) Edge Cases

* `it_handles_zero_amount_sale_gracefully`
* `it_prevents_negative_total`
* `it_handles_concurrent_sales_without_stock_conflict`

---