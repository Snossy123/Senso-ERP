<?php

namespace Tests\Unit\Application\Inventory;

use App\Application\Inventory\StockAvailabilityService;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

class StockAvailabilityServiceTest extends TestCase
{
    use FoundationBaselineFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundationTenantAndStaff();
    }

    public function test_uses_warehouse_quantity_when_row_exists(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 100,
        ]);

        $warehouse = Warehouse::withoutGlobalScopes()->create([
            'tenant_id' => $this->foundationTenantId,
            'name' => 'Test WH',
            'location' => 'A',
            'is_active' => true,
        ]);

        ProductWarehouseStock::withoutGlobalScopes()->create([
            'tenant_id' => $this->foundationTenantId,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 3,
        ]);

        $service = app(StockAvailabilityService::class);

        $this->assertSame(3, $service->availableQuantity($product, $warehouse->id));

        $this->expectException(InvalidArgumentException::class);
        $service->assertAvailable($product, 5, $warehouse->id);
    }

    public function test_falls_back_to_rolled_up_stock_without_warehouse_row(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 7,
        ]);

        $service = app(StockAvailabilityService::class);
        $service->assertAvailable($product, 5);

        $this->assertSame(7, $service->availableQuantity($product));
    }
}
