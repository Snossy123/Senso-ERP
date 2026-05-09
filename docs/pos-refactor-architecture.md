# POS Refactor Architecture (Safe Upgrade Path)

## Principles
- Preserve all existing POS business logic in controllers/services (sales, accounting, stock, tenant, shift, refund, permissions).
- Move frontend to componentized Blade + Alpine modules with API-driven product loading where safe.
- Keep existing routes, route names, and JSON contracts intact.

## Module Structure — Terminal
- `resources/views/pos/terminal.blade.php`: shell + scoped POS CSS utilities.
- `resources/views/pos/partials/terminal/topbar.blade.php`
- `resources/views/pos/partials/terminal/sidebar.blade.php`
- `resources/views/pos/partials/terminal/catalog.blade.php`
- `resources/views/pos/partials/terminal/cart.blade.php`
- `resources/views/pos/partials/modals.blade.php` → proxies `@include('pos.partials.modals.index')`.
- Atomized modal parts under `resources/views/pos/partials/modals/`:
  - `held-orders`, `open-shift`, `close-shift`, `quick-customer`, `variants`, `checkout`, `success`.

## Frontend state (`public/js/pos-terminal.js`)
- **Merged Alpine store (`pos`)**: product feed (pagination/search/category/barcode), cart math, checkout, shifts, holds, shortcuts.
- **Keyboard layer (Phase 2)**:
  - Custom **grid navigator** tracked by `keyboardProductIndex`, column count inferred from `#pos-product-grid` CSS layout.
  - **Cart lane focus** tracked by `cartFocusedIndex`; `+`/`-`, `Del`, row tap for selection.
  - **Modals**: `Esc` closes Bootstrap top modal; barcode search flashes `pos-scan-flash`.
- **Realtime-ish UX cues**: shimmer skeletons (`loadingProducts`), `recentFlashProductId` halo, `_cartPulseIndex` bump animations, optimistic grid interactions.

## Product data
- Paginated endpoint `GET /pos/products` (`pos.products.feed`): `q`, `category_id`, `barcode`, `page`, `per_page`.

## Operational pages (Phase 2 visual refresh)
- `pos/sales/index|show.blade.php` — KPI scaffolding, sticky filters/mobile cards, cashier initials, guarded refund/void modals/scripts.
- `pos/shifts/index|show.blade.php` — recon cards, variance chips, timelines, sticky financial summary panes.

## Phase 3 roadmap (not implemented)
1. Offline buffer + IndexedDB reconcile.
2. WebSocket inventory + multi-terminal sync.
3. Native split tenders + denomination sheets.
4. Customer-facing receipt display + programmable receipt DSL.
5. Hardware abstraction layer (scales, MSR, STAR/Epson adapters).
