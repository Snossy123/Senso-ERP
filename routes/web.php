<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\Inventory\CategoryController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\PurchaseOrderController;
use App\Http\Controllers\Inventory\StockMovementController;
use App\Http\Controllers\Inventory\StockTransferController;
use App\Http\Controllers\Inventory\SupplierController;
use App\Http\Controllers\Inventory\UnitController;
use App\Http\Controllers\Inventory\WarehouseController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\POS\POSController;
use App\Http\Controllers\POS\SaleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Store\AccountController;
use App\Http\Controllers\Store\AuthController as StoreAuthController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\ShopController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Platform\ImpersonationController;
use App\Http\Controllers\Platform\PlanController as PlatformPlanController;
use App\Http\Controllers\Platform\PlatformAddonController;
use App\Http\Controllers\Platform\PlatformGatewayController;
use App\Http\Controllers\Platform\PlatformInvoiceController;
use App\Http\Controllers\Platform\PlatformModuleController;
use App\Http\Controllers\Platform\PlatformSettingsController;
use App\Http\Controllers\Platform\PlatformSystemLogController;
use App\Http\Controllers\Platform\TenantController as PlatformTenantController;
use App\Http\Controllers\UomoAssetController;
use App\Http\Controllers\UserController;
use App\Modules\StorefrontBuilder\Http\Controllers\StorefrontBuilderController;
use App\Modules\StorefrontBuilder\Http\Controllers\StorefrontStudioController;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/welcome', [MarketingController::class, 'home'])->name('marketing.home');
Route::get('/product/pos', [MarketingController::class, 'pos'])->name('marketing.pos');

// ── ADMIN ERP AUTH (staff) ──────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Local Uomo static previews (served from /uomo on disk, not necessarily from /public)
Route::get('/__uomo/{path}', [UomoAssetController::class, 'show'])
    ->where('path', '.*')
    ->name('uomo.asset');

Route::middleware(['auth', 'password.must_change'])->group(function () {
    Route::withoutMiddleware(['password.must_change'])->group(function () {
        Route::get('/password/change', [PasswordChangeController::class, 'show'])->name('password.change');
        Route::post('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');

        Route::post('platform/impersonation/stop', [ImpersonationController::class, 'destroy'])
            ->middleware('impersonation.active')
            ->name('platform.impersonation.stop');
    });

    // ── Platform Console (SaaS operators: tenant_id null) ─────
    Route::middleware(['platform.no_impersonation', 'platform'])->prefix('platform')->name('platform.')->group(function () {
        Route::get('/', [PlatformDashboardController::class, 'index'])->name('dashboard');

        Route::resource('tenants', PlatformTenantController::class);
        Route::post('tenants/{tenant}/toggle', [PlatformTenantController::class, 'toggleStatus'])->name('tenants.toggle');
        Route::post('tenants/{tenant}/suspend', [PlatformTenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('tenants/{tenant}/activate', [PlatformTenantController::class, 'activate'])->name('tenants.activate');
        Route::post('tenants/{tenant}/upgrade-plan', [PlatformTenantController::class, 'upgradePlan'])->name('tenants.upgrade-plan');
        Route::post('tenants/{tenant}/login-as', [PlatformTenantController::class, 'loginAs'])->name('tenants.login-as');
        Route::post('tenants/{tenant}/sync-usage', [PlatformTenantController::class, 'syncUsage'])->name('tenants.sync-usage');
        Route::patch('tenants/{tenant}/settings', [PlatformTenantController::class, 'updateSettings'])->name('tenants.settings');

        Route::get('subscriptions', [PlatformPlanController::class, 'index'])->name('subscriptions.index');
        Route::resource('plans', PlatformPlanController::class)->except(['show']);

        Route::resource('invoices', PlatformInvoiceController::class)->only(['index', 'show']);
        Route::post('invoices/{invoice}/mark-paid', [PlatformInvoiceController::class, 'markPaid'])->name('invoices.mark-paid');

        Route::resource('modules', PlatformModuleController::class)->only(['index', 'update']);
        Route::resource('addons', PlatformAddonController::class)->except(['show']);
        Route::get('settings', [PlatformSettingsController::class, 'index'])->name('settings.index');
        Route::patch('settings', [PlatformSettingsController::class, 'update'])->name('settings.update');
        Route::resource('gateways', PlatformGatewayController::class)->except(['show']);
        Route::get('logs', [PlatformSystemLogController::class, 'index'])->name('logs.index');
    });

    // Legacy tenant URLs → platform console
    Route::middleware(['platform.no_impersonation', 'platform'])->group(function () {
        Route::redirect('/tenants', '/platform/tenants', 301);
        Route::redirect('/tenants/create', '/platform/tenants/create', 301);
        Route::get('/tenants/{tenant}', function (\App\Models\Tenant $tenant) {
            return redirect()->route('platform.tenants.show', $tenant);
        });
        Route::get('/tenants/{tenant}/edit', function (\App\Models\Tenant $tenant) {
            return redirect()->route('platform.tenants.edit', $tenant);
        });
    });

    // ── Tenant ERP (tenant staff only) ─────────────────────────
    Route::middleware('tenant.staff')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // POS — standalone cashier app (primary); `/pos` redirects for backward-compatible bookmarks
    Route::get('/pos', function () {
        return redirect()->route('pos.app');
    })->name('pos.terminal');
    Route::get('/pos/app', [POSController::class, 'app'])->name('pos.app');
    Route::get('/pos/legacy', [POSController::class, 'terminalLegacy'])->name('pos.terminal.legacy');
    Route::get('/pos/display', [POSController::class, 'customerDisplay'])->name('pos.display');
    Route::post('/pos/sale', [SaleController::class, 'store'])->name('pos.sale.store');
    Route::get('/pos/sales', [SaleController::class, 'index'])->name('pos.sales.index');
    Route::get('/pos/sales/{sale}', [SaleController::class, 'show'])->name('pos.sales.show');
    Route::get('/pos/sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('pos.sales.receipt');
    Route::post('/pos/sales/{sale}/void', [SaleController::class, 'void'])->name('pos.sales.void');
    Route::post('/pos/sales/{sale}/refund', [SaleController::class, 'refund'])->name('pos.sales.refund');
    // POS Shift Management
    Route::post('/pos/shift/open', [POSController::class, 'openShift'])->name('pos.shift.open');
    Route::post('/pos/shift/{shift}/close', [POSController::class, 'closeShift'])->name('pos.shift.close');
    // POS Held Orders
    Route::post('/pos/hold', [POSController::class, 'holdOrder'])->name('pos.hold');
    Route::get('/pos/held', [POSController::class, 'getHeldOrders'])->name('pos.held');
    Route::post('/pos/held/{held}/resume', [POSController::class, 'resumeHeldOrder'])->name('pos.held.resume');
    // POS Product Search / Barcode
    Route::get('/pos/search', [POSController::class, 'searchProduct'])->name('pos.search');
    Route::get('/pos/products', [POSController::class, 'productsFeed'])->name('pos.products.feed');
    Route::post('/pos/customer/quick-store', [POSController::class, 'quickStoreCustomer'])->name('pos.customer.quick-store');
    Route::get('/pos/customers/search', [POSController::class, 'searchCustomers'])->name('pos.customers.search');

    // CRM
    Route::prefix('crm')->name('crm.')->group(function () {
        Route::resource('customers', \App\Http\Controllers\CRM\CustomerController::class);
        Route::post('customers/{customer}/notes', [\App\Http\Controllers\CRM\CustomerNoteController::class, 'store'])->name('customers.notes.store');
        Route::delete('customers/{customer}/notes/{note}', [\App\Http\Controllers\CRM\CustomerNoteController::class, 'destroy'])->name('customers.notes.destroy');
        Route::resource('tags', \App\Http\Controllers\CRM\CustomerTagController::class)->except(['show']);
    });
    // POS Shift Reports
    Route::get('/pos/shifts', [POSController::class, 'shiftsIndex'])->name('pos.shifts.index');
    Route::get('/pos/shifts/{shift}', [POSController::class, 'shiftShow'])->name('pos.shifts.show');

    // Inventory
    Route::resource('inventory/products', ProductController::class)->names('inventory.products');
    Route::resource('inventory/categories', CategoryController::class)->names('inventory.categories');
    Route::resource('inventory/suppliers', SupplierController::class)->names('inventory.suppliers');
    Route::resource('inventory/warehouses', WarehouseController::class)->names('inventory.warehouses');
    Route::resource('inventory/stock-movements', StockMovementController::class)->names('inventory.movements');
    Route::resource('inventory/purchase-orders', PurchaseOrderController::class)->names('inventory.purchase-orders');
    Route::post('inventory/purchase-orders/{order}/receive', [PurchaseOrderController::class, 'receive'])->name('inventory.purchase-orders.receive');
    Route::post('inventory/purchase-orders/{order}/pay', [PurchaseOrderController::class, 'pay'])->name('inventory.purchase-orders.pay');
    Route::resource('inventory/stock-transfers', StockTransferController::class)->names('inventory.transfers');
    Route::resource('inventory/units', UnitController::class)->names('inventory.units')->only(['index', 'store', 'destroy']);

    // Admin — Ecommerce order management
    Route::get('/admin/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/admin/orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::patch('/admin/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('admin.orders.status');
    Route::post('/admin/orders/{order}/mark-paid', [AdminOrderController::class, 'markPaid'])->name('admin.orders.mark-paid');

    // Admin — User Management
    Route::resource('admin/users', UserController::class)->names('admin.users');
    Route::post('admin/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('admin.users.toggle');
    Route::post('admin/users/{user}/lock', [UserController::class, 'lock'])->name('admin.users.lock');
    Route::post('admin/users/{user}/unlock', [UserController::class, 'unlock'])->name('admin.users.unlock');
    Route::post('admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset-password');
    Route::post('admin/users/{user}/force-change-password', [UserController::class, 'forceChangePassword'])->name('admin.users.force-change-password');

    // Admin — Role Management
    Route::resource('admin/roles', RoleController::class)->names('admin.roles');

    // Admin — Settings
    Route::get('/admin/settings', [SettingsController::class, 'index'])->name('admin.settings');
    Route::post('/admin/settings', [SettingsController::class, 'store'])->name('admin.settings.store');

    // Admin — Ecommerce Builder
    Route::prefix('/admin/storefront-builder')->name('admin.storefront-builder.')->group(function () {
        Route::get('/', [StorefrontBuilderController::class, 'index'])->name('index');
        Route::patch('/settings', [StorefrontBuilderController::class, 'update'])->name('update');
        Route::patch('/sections', [StorefrontBuilderController::class, 'updateSections'])->name('sections.update');
        Route::post('/publish', [StorefrontBuilderController::class, 'publish'])->name('publish');
        Route::post('/rollback', [StorefrontBuilderController::class, 'rollback'])->name('rollback');
        Route::get('/preview', [StorefrontBuilderController::class, 'preview'])->name('preview');
    });

    Route::prefix('/admin/storefront-studio')->name('admin.storefront-studio.')->group(function () {
        Route::get('/', [StorefrontStudioController::class, 'index'])->name('index');
        Route::get('/pages/{pageType}/layout', [StorefrontStudioController::class, 'showPageLayout'])
            ->where('pageType', '[a-z0-9-]+')
            ->name('pages.layout.show');
        Route::put('/pages/{pageType}/layout', [StorefrontStudioController::class, 'updatePageLayout'])
            ->where('pageType', '[a-z0-9-]+')
            ->name('pages.layout.update');
        Route::get('/catalog/products', [StorefrontStudioController::class, 'catalogProducts'])->name('catalog.products');
        Route::get('/catalog/categories', [StorefrontStudioController::class, 'catalogCategories'])->name('catalog.categories');
        Route::get('/catalog/cart-summary', [StorefrontStudioController::class, 'catalogCartSummary'])->name('catalog.cart-summary');
        Route::get('/presets/uomo', [StorefrontStudioController::class, 'uomoPresets'])->name('presets.uomo');
        Route::get('/pages/{pageType}/layout/diff', [StorefrontStudioController::class, 'pageLayoutDiff'])
            ->where('pageType', '[a-z0-9-]+')
            ->name('pages.layout.diff');
        Route::post('/pages/{pageType}/layout/import', [StorefrontStudioController::class, 'importPageLayout'])
            ->where('pageType', '[a-z0-9-]+')
            ->name('pages.layout.import');
    });

    // Admin — Activity Log
    Route::get('/admin/activity', [ActivityLogController::class, 'index'])->name('admin.activity.index');
    Route::get('/admin/activity/{activity}', [ActivityLogController::class, 'show'])->name('admin.activity.show');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('/reports/profit', [ReportController::class, 'profit'])->name('reports.profit');
    Route::get('/reports/customers', [ReportController::class, 'customers'])->name('reports.customers');

    // Exports
    Route::get('/exports/sales/pdf', [ExportController::class, 'salesPdf'])->name('exports.sales.pdf');
    Route::get('/exports/sales/excel', [ExportController::class, 'salesExcel'])->name('exports.sales.excel');
    Route::get('/exports/inventory/pdf', [ExportController::class, 'inventoryPdf'])->name('exports.inventory.pdf');
    Route::get('/exports/inventory/excel', [ExportController::class, 'inventoryExcel'])->name('exports.inventory.excel');
    Route::get('/exports/orders/pdf', [ExportController::class, 'ordersPdf'])->name('exports.orders.pdf');
    Route::get('/exports/customers/excel', [ExportController::class, 'customersExcel'])->name('exports.customers.excel');
    Route::get('/exports/receipt/{sale}/pdf', [ExportController::class, 'receiptPdf'])->name('exports.receipt.pdf');
    Route::get('/exports/invoice/{order}/pdf', [ExportController::class, 'invoicePdf'])->name('exports.invoice.pdf');
    // Accounting Web
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'dashboard'])->name('dashboard');

        Route::get('/accounts', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'accounts'])->name('accounts');
        Route::post('/accounts', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'storeAccount'])->name('accounts.store');

        Route::get('/journal-entries', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'journalEntries'])->name('journal-entries');
        Route::get('/journal-entries/create', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'createJournalEntry'])->name('journal-entries.create');
        Route::post('/journal-entries', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'storeJournalEntry'])->name('journal-entries.store');
        Route::post('/journal-entries/{entry}/approve', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'approveJournalEntry'])->name('journal-entries.approve');
        Route::post('/journal-entries/{entry}/post', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'postJournalEntry'])->name('journal-entries.post');

        Route::get('/reports', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'reports'])->name('reports');
        Route::get('/reports/trial-balance', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'reportTrialBalance'])->name('reports.trial-balance');
        Route::get('/reports/income-statement', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'reportIncomeStatement'])->name('reports.income-statement');
        Route::get('/reports/balance-sheet', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'reportBalanceSheet'])->name('reports.balance-sheet');
        Route::get('/reports/general-ledger', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'reportGeneralLedger'])->name('reports.general-ledger');

        Route::get('/reconciliation', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'reconciliation'])->name('reconciliation');
        Route::get('/subsidiary-ledgers', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'subsidiaryLedgers'])->name('subsidiary-ledgers');
        Route::get('/disbursements', [\App\Http\Controllers\Accounting\Web\SupplierDisbursementController::class, 'index'])->name('disbursements');
        Route::post('/disbursements/{order}/pay', [\App\Http\Controllers\Accounting\Web\SupplierDisbursementController::class, 'pay'])->name('disbursements.pay');
        Route::get('/customer-receipts', [\App\Http\Controllers\Accounting\Web\CustomerReceiptController::class, 'index'])->name('customer-receipts');
        Route::post('/customer-receipts/{order}/collect', [\App\Http\Controllers\Accounting\Web\CustomerReceiptController::class, 'collect'])->name('customer-receipts.collect');
        Route::post('/customer-receipts/sales/{sale}/collect', [\App\Http\Controllers\Accounting\Web\CustomerReceiptController::class, 'collectSale'])->name('customer-receipts.collect-sale');
        Route::get('/bank-reconciliation', [\App\Http\Controllers\Accounting\Web\BankReconciliationController::class, 'index'])->name('bank-reconciliation');
        Route::post('/bank-reconciliation/import', [\App\Http\Controllers\Accounting\Web\BankReconciliationController::class, 'importLine'])->name('bank-reconciliation.import');
        Route::post('/bank-reconciliation/match', [\App\Http\Controllers\Accounting\Web\BankReconciliationController::class, 'match'])->name('bank-reconciliation.match');
        Route::get('/audit-trail', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'auditTrail'])->name('audit-trail');

        Route::get('/periods', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'periods'])->name('periods');
        Route::post('/periods', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'storePeriod'])->name('periods.store');
        Route::post('/periods/{period}/close', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'closePeriod'])->name('periods.close');

        Route::get('/opening-balance', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'openingBalance'])->name('opening-balance');
        Route::post('/opening-balance', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'storeOpeningBalance'])->name('opening-balance.store');

        Route::get('/settings', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'settings'])->name('settings');
        Route::post('/settings', [\App\Http\Controllers\Accounting\Web\AccountingController::class, 'updateSettings'])->name('settings.update');
    });
    }); // tenant.staff
});

// ── USER PORTAL — Store (prefix: /store) ────────────────────
Route::prefix('store')->name('store.')->group(function () {

    // Customer auth
    Route::get('/login', [StoreAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [StoreAuthController::class, 'login']);
    Route::get('/register', [StoreAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [StoreAuthController::class, 'register']);
    Route::post('/logout', [StoreAuthController::class, 'logout'])->name('logout');

    // Public shop
    Route::get('/', [ShopController::class, 'index'])->name('index');
    Route::get('/products/{product:slug}', [ShopController::class, 'show'])->name('products.show');

    // Cart (no auth needed)
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/update/{product}', [CartController::class, 'update'])->name('cart.update');
    Route::get('/cart/remove/{product}', [CartController::class, 'remove'])->name('cart.remove');

    // Checkout (no auth forced — guest checkout allowed)
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'placeOrder'])->name('checkout.place');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');

    // My Account (requires customer auth)
    Route::middleware('auth:customer')->prefix('account')->name('account.')->group(function () {
        Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
        Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
        Route::get('/orders/{order}', [AccountController::class, 'orderDetail'])->name('orders.show');
    });
});
