# Slice 0 — POS Foundation (Implemented)

## Files

| Path | Role |
|------|------|
| `resources/css/design-system.css` | Tokens, typography, `.pos-tabular`, `.pos-money*` scales |
| `resources/css/pos-shell.css` | Shell CSS variables + `.pos-app-main` refinements |
| `resources/css/pos-components.css` | Primitive classes: button, input, surface, badge, modal frame, table |
| `resources/css/pos/main.css` | Imports foundation → legacy POS sheets → `pos-components` late |
| `resources/views/components/ui/*.blade.php` | `<x-ui.button>`, `card`, `modal`, `input`, `badge`, `money` |

## Sync

POS layout loads `public/css/pos/main.css` (not Vite). After editing `resources/css/**`, copy into `public/css/**` or automate in CI.

## Usage

```blade
<x-ui.money :amount="$total" size="hero" />
<x-ui.button variant="primary" size="lg">Checkout</x-ui.button>
```

## Next slices

Replace inline markup in POS partials with these primitives; fix Alpine-driven totals separately (NaN) before visual polish.
