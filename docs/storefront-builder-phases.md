# Storefront Builder Progress

## Phase Checklist
- [x] Phase 0 - Foundation and registry
- [x] Phase 1 - Core ecommerce pages mapping
- [x] Phase 2 - Admin UX, preview, publish, rollback, validation
- [x] Phase 3 - Expanded page mappings + render cache hardening
- [x] Phase 4 - API resources/contracts for split readiness

## Delivered Files By Phase

### Phase 0
- `database/migrations/2026_04_13_100000_create_storefront_builder_tables.php`
- `app/Modules/StorefrontBuilder/Models/Storefront.php`
- `app/Modules/StorefrontBuilder/Models/StorefrontPage.php`
- `app/Modules/StorefrontBuilder/Models/StorefrontSection.php`
- `app/Modules/StorefrontBuilder/Models/StorefrontTemplateBinding.php`
- `app/Modules/StorefrontBuilder/Models/StorefrontPublishVersion.php`
- `app/Modules/StorefrontBuilder/Services/TemplateRegistryService.php`
- `app/Modules/StorefrontBuilder/Services/StorefrontBuilderService.php`

### Phase 1
- `app/Modules/StorefrontBuilder/Services/StorefrontRenderer.php`
- `app/Http/Controllers/Store/ShopController.php`
- `app/Http/Controllers/Store/CartController.php`
- `app/Http/Controllers/Store/CheckoutController.php`
- `app/Http/Controllers/Store/AccountController.php`
- `resources/views/store/layouts/portal.blade.php`

### Phase 2
- `app/Modules/StorefrontBuilder/Http/Requests/UpdateStorefrontRequest.php`
- `app/Modules/StorefrontBuilder/Http/Requests/UpdatePageSectionsRequest.php`
- `app/Modules/StorefrontBuilder/Http/Requests/RollbackStorefrontRequest.php`
- `app/Modules/StorefrontBuilder/Http/Controllers/StorefrontBuilderController.php`
- `resources/views/erp/storefront-builder/index.blade.php`
- `resources/views/erp/storefront-builder/preview.blade.php`
- `routes/web.php`
- `resources/views/layouts/main-sidebar.blade.php` (ERP navigation entry)

### Phase 3
- `app/Modules/StorefrontBuilder/Services/StorefrontBuilderService.php` (expanded page mappings and publish checks)
- `app/Modules/StorefrontBuilder/Services/StorefrontRenderer.php` (cached tenant-scoped render payload; published snapshot rendering)

### Phase 4
- `app/Modules/StorefrontBuilder/Http/Controllers/StorefrontBuilderApiController.php`
- `app/Modules/StorefrontBuilder/Http/Resources/StorefrontResource.php`
- `app/Modules/StorefrontBuilder/Http/Resources/StorefrontPageResource.php`
- `app/Modules/StorefrontBuilder/Http/Resources/StorefrontSectionResource.php`
- `routes/api.php`
- `app/Http/Middleware/VerifyStorefrontBootToken.php`
- `app/Http/Controllers/UomoAssetController.php`
- `app/Console/Commands/ImportUomoStorefrontTemplates.php`

## Blockers and Decisions Log
- Decision: keep beta renderer on Blade for low cost, with contract-oriented services for future decoupled frontend.
- Decision: use a single default storefront per tenant for beta speed; versioning supports later multi-store expansion.
- Decision: Uomo home templates are registered in code-based registry to avoid initial migration overhead.
- Decision: live storefront reads **published snapshot** when `published_version_id` is set; draft edits stay in tables until publish.

## What's Next (Top 3)
1. Richer section editors (grid density, banners, collection picks) + optional WYSIWYG blocks.
2. Extract real Uomo section slices automatically (DOM-based importer beyond metadata sync).
3. Harden `/__uomo` static hosting for production (auth, caching headers, CDN offload).
