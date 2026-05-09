# Inventory write migration map (post Slice D)

Central seam: **`App\Application\Inventory\InventoryPostingService`** (`postInbound`, `postOutbound`, `postAdjustment`).

## Routed through InventoryPostingService

| Flow | Location | Method(s) |
|------|----------|-----------|
| PO goods receipt | `PurchaseOrderController::receive` | `postInbound` |
| Ecommerce checkout | `CheckoutController::placeOrder` | `postOutbound` via `StockPostingData::forEcommerceOrderLine` |
| POS sale checkout | `SaleController::store` (per line) | `postOutbound` via `StockPostingData::forPosSaleLine` |

## Still direct writes (future slices)

### `products.stock_quantity` + `StockMovement` outside the service

| Location | Pattern |
|----------|---------|
| `POS/SaleController.php` | Void (`increment` + movement), refund (`increment` + movement) — **sale lines now use service** |
| `Inventory/StockMovementController.php` | `increment` / `decrement` / `update(['stock_quantity'])` + `StockMovement::create` |
| `Inventory/StockTransferController.php` | `StockMovement::create` (two legs); warehouse/product mutations inline |

### Only rolled-up `stock_quantity` (no movement row)

| Location | Note |
|----------|------|
| Seeders / factories / exports | Non-runtime catalog defaults |
| Dashboard / reports | Read-only |

## `StockMovement::create` / `StockMovement::query()->create` occurrences

| File | Status |
|------|--------|
| `InventoryPostingService.php` | **Canonical** — movements for PO receive, ecommerce outbound, POS sale lines |
| `POS/SaleController.php` | **Void + refund only** (pending Slice F) |
| `Inventory/StockMovementController.php` | Manual adjustments UI — future alignment with `postAdjustment` |
| `Inventory/StockTransferController.php` | Transfers — future seam |

## `increment` / `decrement` / direct `stock_quantity` updates (app code)

| File | Status |
|------|--------|
| `InventoryPostingService.php` | Canonical for inbound/outbound/adjustment paths it implements |
| `POS/SaleController.php` | Void/refund paths still touch product stock directly |
| `Inventory/StockMovementController.php` | Pending consolidation |

---

Next: **Slice E** — accounting trigger cleanup (single JE per sale). **Slice F** — refund/void via `InventoryPostingService`.
