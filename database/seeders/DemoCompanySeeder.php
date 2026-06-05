<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\GoLiveChecklistService;
use App\Services\SettingService;
use App\Services\TenantManager;
use App\Services\TenantProvisioningService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoCompanySeeder extends Seeder
{
    public const DEMO_SLUG = 'tech-store';

    public function run(): void
    {
        $tenant = Tenant::where('slug', self::DEMO_SLUG)->firstOrFail();
        $tenantId = (int) $tenant->id;

        $this->configureTenant($tenant);
        $this->seedSettings($tenantId);
        $this->provisionTenant($tenant);
        $this->seedOpeningBalances($tenant);
        $this->linkProductsToInventory($tenantId);
        $this->localizeProductNames($tenantId);
        $this->seedPosShift($tenantId);
        $this->seedPurchaseOrders($tenantId);

        $checklist = app(GoLiveChecklistService::class);
        $pct = $checklist->completionPercentage($tenant);
        $ready = $checklist->isReadyForGoLive($tenant) ? 'yes' : 'no';

        $this->command?->info("Demo company [{$tenant->name}] seeded. Go-live: {$pct}% (ready: {$ready})");
    }

    private function configureTenant(Tenant $tenant): void
    {
        $tenant->update([
            'name' => 'متجر التقنية للإلكترونيات',
            'domain' => 'techstore.local',
            'is_active' => true,
            'status' => 'active',
            'language' => 'ar',
            'timezone' => 'Africa/Cairo',
            'currency' => 'EGP',
            'tax_settings' => [
                'tax_number' => '123-456-789',
                'tax_rate' => 14,
                'tax_included' => false,
            ],
        ]);
    }

    private function seedSettings(int $tenantId): void
    {
        $settings = app(SettingService::class);

        $groups = [
            'business' => [
                'company_name' => 'متجر التقنية للإلكترونيات',
                'vat_number' => '123-456-789',
                'invoice_prefix' => 'INV-',
                'invoice_format' => 'modern',
            ],
            'localization' => [
                'language' => 'ar',
                'timezone' => 'Africa/Cairo',
                'date_format' => 'd/m/Y',
            ],
            'sales' => [
                'default_payment_method' => 'cash',
                'pos_tax_enabled' => true,
                'default_discount_limit' => 20,
            ],
            'inventory' => [
                'low_stock_threshold' => 10,
                'allow_negative_stock' => false,
                'auto_stock_updates' => true,
            ],
            'security' => [
                'session_timeout' => 120,
                'max_login_attempts' => 5,
                'password_min_length' => 8,
            ],
            'notifications' => [
                'email_alerts_enabled' => true,
                'low_stock_notification' => true,
                'notification_email' => 'admin@techstore.local',
            ],
        ];

        foreach ($groups as $group => $items) {
            foreach ($items as $key => $value) {
                $settings->set($key, $value, $group, $tenantId);
            }
        }

        $admin = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('email', 'admin@techstore.local')
            ->first();

        if ($admin) {
            $admin->update(['language' => 'ar']);
        }
    }

    private function provisionTenant(Tenant $tenant): void
    {
        app(TenantProvisioningService::class)->provision($tenant, publishStorefront: true);
    }

    private function seedOpeningBalances(Tenant $tenant): void
    {
        $exists = JournalEntry::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('description', 'like', '%Opening balance%')
            ->exists();

        if ($exists) {
            return;
        }

        $cashAccount = Account::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('code', '1120')
            ->first();

        $inventoryAccount = Account::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('code', '1400')
            ->first();

        $equityAccount = Account::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('code', '3100')
            ->first();

        if (! $cashAccount || ! $inventoryAccount || ! $equityAccount) {
            return;
        }

        $tenantManager = app(TenantManager::class);
        $previous = $tenantManager->getCurrent();
        $tenantManager->setCurrent($tenant);

        try {
            app(AccountingService::class)->createOpeningBalanceEntry(
                [
                    'tenant_id' => $tenant->id,
                    'date' => now()->startOfYear()->toDateString(),
                    'description' => 'Opening balances',
                    'reference' => 'OPENING-DEMO-'.now()->format('Y'),
                ],
                [
                    ['account_id' => $cashAccount->id, 'description' => 'POS cash float', 'debit' => 50000, 'credit' => 0],
                    ['account_id' => $inventoryAccount->id, 'description' => 'Inventory on hand', 'debit' => 150000, 'credit' => 0],
                    ['account_id' => $equityAccount->id, 'description' => 'Owner equity', 'debit' => 0, 'credit' => 200000],
                ]
            );
        } finally {
            if ($previous) {
                $tenantManager->setCurrent($previous);
            } else {
                $tenantManager->clear();
            }
        }
    }

    private function linkProductsToInventory(int $tenantId): void
    {
        $supplier = Supplier::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->first();

        $warehouse = Warehouse::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (! $supplier || ! $warehouse) {
            return;
        }

        Product::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where(function ($query) {
                $query->whereNull('supplier_id')->orWhereNull('warehouse_id');
            })
            ->update([
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
            ]);
    }

    private function localizeProductNames(int $tenantId): void
    {
        $translations = [
            'Gaming Pro Laptop X15' => 'لابتوب ألعاب برو X15',
            'Business Elite Laptop' => 'لابتوب أعمال إيليت',
            'iPhone 15 Pro Max' => 'آيفون 15 برو ماكس',
            'Samsung Galaxy S24' => 'سامسونج جالاكسي S24',
            'iPad Pro 12.9"' => 'آيباد برو 12.9',
            'AirPods Pro 2' => 'إيربودز برو 2',
            'Sony WH-1000XM5' => 'سوني WH-1000XM5',
            'PlayStation 5' => 'بلايستيشن 5',
            'Xbox Series X' => 'إكس بوكس سيريس X',
            'UltraWide 34" Monitor' => 'شاشة عريضة 34 بوصة',
        ];

        foreach ($translations as $english => $arabic) {
            $product = Product::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('name', $english)
                ->first();

            if ($product) {
                $product->update([
                    'name' => $arabic,
                    'slug' => Str::slug($arabic),
                    'description' => 'منتج إلكتروني عالي الجودة للعرض التجريبي.',
                ]);
            }
        }
    }

    private function seedPosShift(int $tenantId): void
    {
        $cashier = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('email', 'staff@techstore.local')
            ->first()
            ?? User::withoutGlobalScopes()->where('tenant_id', $tenantId)->first();

        if (! $cashier) {
            return;
        }

        $hasOpenShift = PosShift::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->exists();

        if ($hasOpenShift) {
            return;
        }

        PosShift::create([
            'tenant_id' => $tenantId,
            'user_id' => $cashier->id,
            'terminal_id' => 'POS-01',
            'opening_float' => 500,
            'opened_at' => now(),
            'status' => 'open',
            'notes' => 'وردية ديمو مفتوحة للعرض',
        ]);
    }

    private function seedPurchaseOrders(int $tenantId): void
    {
        if (PurchaseOrder::withoutGlobalScopes()->where('tenant_id', $tenantId)->exists()) {
            return;
        }

        $supplier = Supplier::withoutGlobalScopes()->where('tenant_id', $tenantId)->first();
        $warehouse = Warehouse::withoutGlobalScopes()->where('tenant_id', $tenantId)->first();
        $admin = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('email', 'admin@techstore.local')
            ->first();
        $products = Product::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->limit(3)
            ->get();

        if (! $supplier || ! $warehouse || ! $admin || $products->isEmpty()) {
            return;
        }

        $orders = [
            ['reference' => 'PO-DEMO-001', 'status' => 'received', 'days_ago' => 14],
            ['reference' => 'PO-DEMO-002', 'status' => 'received', 'days_ago' => 7],
            ['reference' => 'PO-DEMO-003', 'status' => 'ordered', 'days_ago' => 2],
        ];

        foreach ($orders as $index => $orderData) {
            $product = $products[$index % $products->count()];
            $quantity = 10 + $index * 5;
            $unitCost = (float) $product->purchase_price;
            $total = $quantity * $unitCost;

            $po = PurchaseOrder::create([
                'tenant_id' => $tenantId,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'reference_no' => $orderData['reference'],
                'order_date' => now()->subDays($orderData['days_ago'])->toDateString(),
                'expected_date' => now()->subDays($orderData['days_ago'] - 2)->toDateString(),
                'status' => $orderData['status'],
                'payment_status' => $orderData['status'] === 'received' ? 'paid' : 'pending',
                'total_amount' => $total,
                'created_by' => $admin->id,
                'received_at' => $orderData['status'] === 'received' ? now()->subDays($orderData['days_ago'] - 1) : null,
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'received_quantity' => $orderData['status'] === 'received' ? $quantity : 0,
                'unit_cost' => $unitCost,
                'total' => $total,
            ]);
        }
    }
}
