# Slice 2 — Cart & Checkout UX (Plan → Implementation)

## 1. Exact files

| File | Role |
|------|------|
| `resources/views/pos/partials/terminal/cart.blade.php` | Cart markup |
| `resources/views/pos/partials/modals/checkout.blade.php` | Checkout modal markup |
| `resources/css/pos/cart-modern.css` | App-shell cart + Slice 2 cart rules |
| `resources/css/pos/checkout.css` | Checkout modal rules (extended Slice 2) |
| `resources/css/pos/cart.css` | Shared qty / row animations (touch bumps) |

## 2. Cart pain points (before)

- Triple stack of borders (card, row `border-b`, order-discount rounded box).
- Line layout mixes meta with controls; line total competes with qty cluster visually.
- Inline styles (gradient CTA, emerald) bypass tokens.
- Empty state flat; icon floats without surface hierarchy.
- Sticky footer uses generic `shadow-soft-up` (non-token).

## 3. Checkout pain points (before)

- Summary column `bg-light` / due block `bg-dark` hardcoded — not tokenized.
- Payment `.active` uses raw hex purple.
- Many nested borders (input group, change banner).
- Due amount competes with equally loud surrounding chrome.

## 4. Proposed DOM changes (minimal)

- **Cart:** Wrap header / empty / summary in semantic blocks (`pos-cart-header`, `pos-cart-empty`, `pos-cart-summary-panel`). Split each line into `pos-cart-line-top` (title + remove), `pos-cart-line-actions` (qty + line total), `pos-cart-line-discount`. No Alpine logic changes.
- **Checkout:** Add `pos-checkout-summary-pane`, `pos-checkout-due-hero`, `pos-checkout-pay-pane`, `pos-checkout-change-strip`. Same Bootstrap grid; classes only.

## 5. Spacing

- Use `var(--pos-space-*)` via CSS padding/gap on new classes; reduce redundant `p-4` where CSS controls rhythm.

## 6. Touch targets

- Qty cluster: keep ≥44px; Slice 2 targets 48px comfortable via `.qty-btn` update in `cart.css`.
- Payment tiles: `min-height: 52px` → comfortable band.
- Quick cash: unified `.pos-checkout-quick-cash` min-height 48px.

## 7. Sticky behavior

- Keep existing `#cart-summary-sticky` + `.pos-cart-pane--app` sticky rules; add **surface + negative shadow** on summary panel for separation without extra nested boxes.

## 8. Visual hierarchy

- **Product name:** larger + darker (`--pos-color-text`).
- **Meta / unit price:** step--1, `--pos-color-text-muted`.
- **Line total:** `pos-money--md` scale via CSS class on cart.
- **Due:** largest tabular row + success color token for amount only.
- **Checkout due:** hero panel uses `--pos-color-text` background + inverse text (single dominant block).

## 9. Risk mitigation

- CSS-only + class additions; no JS changes.
- Token fallbacks match prior colors where possible.
- Sync `public/css/pos/*.css` after edits.
