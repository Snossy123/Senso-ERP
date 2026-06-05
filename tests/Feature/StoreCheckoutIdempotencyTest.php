<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

class StoreCheckoutIdempotencyTest extends TestCase
{
    use FoundationBaselineFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundationTenantAndStaff();
    }

    public function test_duplicate_checkout_with_same_idempotency_key_creates_one_order(): void
    {
        if (! Schema::hasColumn('orders', 'client_idempotency_key')) {
            $this->markTestSkipped('orders.client_idempotency_key column not migrated.');
        }

        $product = Product::factory()->create([
            'tenant_id' => $this->foundationTenantId,
            'stock_quantity' => 20,
            'selling_price' => 10,
            'is_ecommerce' => true,
        ]);

        $payload = [
            'customer_name' => 'Idem Guest',
            'customer_email' => 'idem@example.org',
            'customer_phone' => null,
            'shipping_address' => '1 Lane',
            'city' => 'City',
            'payment_method' => 'cash_on_delivery',
            'notes' => null,
            'client_idempotency_key' => 'checkout-idem-'.uniqid('', true),
        ];

        $session = ['cart' => [$product->id => ['qty' => 2]]];

        $this->withHeaders($this->tenantHeader())
            ->withSession($session)
            ->post(route('store.checkout.place'), $payload)
            ->assertRedirect(route('store.checkout.success'));

        $this->withHeaders($this->tenantHeader())
            ->withSession($session)
            ->post(route('store.checkout.place'), $payload)
            ->assertRedirect(route('store.checkout.success'));

        $this->assertSame(
            1,
            Order::withoutGlobalScopes()->where('tenant_id', $this->foundationTenantId)->count()
        );
        $this->assertSame(18, $product->fresh()->stock_quantity);
    }
}
