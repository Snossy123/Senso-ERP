<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\POS\POSController;
use App\Http\Controllers\POS\SaleController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\CategoryController;
use App\Http\Controllers\Inventory\SupplierController;
use App\Http\Controllers\Inventory\WarehouseController;
use App\Http\Controllers\Inventory\StockMovementController;
use App\Http\Controllers\Inventory\PurchaseOrderController;
use App\Http\Controllers\Inventory\StockTransferController;
use App\Http\Controllers\Inventory\UnitController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;

use App\Http\Controllers\Store\AuthController as StoreAuthController;
use App\Http\Controllers\Store\ShopController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\AccountController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\RoleController;

// ── ADMIN ERP AUTH (staff) ──────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // POS
    Route::get('/pos', [POSController::class, 'terminal'])->name('pos.terminal');
    Route::post('/pos/sale', [SaleController::class, 'store'])->name('pos.sale.store');
    Route::get('/pos/sales', [SaleController::class, 'index'])->name('pos.sales.index');
    Route::get('/pos/sales/{sale}', [SaleController::class, 'show'])->name('pos.sales.show');
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
    Route::post('/pos/customer/quick-store', [POSController::class, 'quickStoreCustomer'])->name('pos.customer.quick-store');

    // Inventory
    Route::resource('inventory/products', ProductController::class)->names('inventory.products');
    Route::resource('inventory/categories', CategoryController::class)->names('inventory.categories');
    Route::resource('inventory/suppliers', SupplierController::class)->names('inventory.suppliers');
    Route::resource('inventory/warehouses', WarehouseController::class)->names('inventory.warehouses');
    Route::resource('inventory/stock-movements', StockMovementController::class)->names('inventory.movements');
    Route::resource('inventory/purchase-orders', PurchaseOrderController::class)->names('inventory.purchase-orders');
    Route::post('inventory/purchase-orders/{order}/receive', [PurchaseOrderController::class, 'receive'])->name('inventory.purchase-orders.receive');
    Route::resource('inventory/stock-transfers', StockTransferController::class)->names('inventory.transfers');
    Route::resource('inventory/units', UnitController::class)->names('inventory.units')->only(['index', 'store', 'destroy']);

    // Admin — Ecommerce order management
    Route::get('/admin/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/admin/orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::patch('/admin/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('admin.orders.status');

    // Admin — User Management
    Route::resource('admin/users', UserController::class)->names('admin.users');
    Route::post('admin/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('admin.users.toggle');
    Route::post('admin/users/{user}/lock', [UserController::class, 'lock'])->name('admin.users.lock');
    Route::post('admin/users/{user}/unlock', [UserController::class, 'unlock'])->name('admin.users.unlock');
    Route::post('admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset-password');
    Route::post('admin/users/{user}/force-change-password', [UserController::class, 'forceChangePassword'])->name('admin.users.force-change-password');

    // Admin — Role Management
    Route::resource('admin/roles', RoleController::class)->names('admin.roles');

    // Admin — Tenant Management
    Route::resource('tenants', TenantController::class);
    Route::post('tenants/{tenant}/toggle', [TenantController::class, 'toggleStatus'])->name('tenants.toggle');
    Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
    Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
    Route::post('tenants/{tenant}/upgrade-plan', [TenantController::class, 'upgradePlan'])->name('tenants.upgrade-plan');
    Route::post('tenants/{tenant}/login-as', [TenantController::class, 'loginAs'])->name('tenants.login-as');
    Route::post('tenants/{tenant}/sync-usage', [TenantController::class, 'syncUsage'])->name('tenants.sync-usage');
    Route::patch('tenants/{tenant}/settings', [TenantController::class, 'updateSettings'])->name('tenants.settings');

    // Admin — Settings
    Route::get('/admin/settings', [SettingsController::class, 'index'])->name('admin.settings');
    Route::post('/admin/settings', [SettingsController::class, 'store'])->name('admin.settings.store');

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
