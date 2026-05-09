# Slice 1 — Money + Cart Correctness Wiring

## Summary

- **`public/js/pos-terminal.js`**: `posFinite` / `posFiniteOrNaN`; hardened cart totals (`subtotal`, `tax`, `total`, discounts, `changeDue`, split math); `moneyLabel`, `discountLabel`, `changeDueDisplay`, `taxRateDisplay`, `lineDiscountGross`, `itemUnitMoneyLabel`; safe receipt preview capture; `addTendered` implemented; `taxRate` normalized on init.
- **`public/js/pos/pos-contracts.js`**: `sanitizeMoneyString` / line sanitization so receipt previews never show literal `NaN`.
- **Blade**: Cart, checkout modal, catalog price, variant modal, success change block, customer display — use `moneyLabel` / safe helpers and `.pos-tabular` where amounts appear.

## Display rule

- **Finite number** → `currencySymbol` + 2 dp.
- **Invalid / non-finite** → em dash `—` (U+2014), never `NaN` or `$NaN`.
- **Customer display idle** (no lines): Amount due / subtotal / tax show `—`, not `$0.00`.

## Out of scope

Sales/shifts Blade `number_format` pages unchanged this slice (server-rendered DB money).
