<?php

namespace Tests\Unit\Services\Shipping;

use App\Models\ShippingIntegration;
use App\Services\Shipping\QpExpressClient;
use App\Services\Shipping\QpExpressException;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\FoundationBaselineFixtures;
use Tests\TestCase;

class QpExpressClientTest extends TestCase
{
    use FoundationBaselineFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundationTenantAndStaff();
        app(TenantManager::class)->setCurrent($this->foundationTenant);
    }

    public function test_authenticate_caches_token(): void
    {
        Http::fake([
            '*/integration/token' => Http::response(['token' => 'jwt-test', 'company_name' => 'QP'], 200),
        ]);

        $client = new QpExpressClient($this->integration());

        $this->assertSame('jwt-test', $client->authenticate());
        $this->assertSame('jwt-test', $client->authenticate());

        Http::assertSentCount(1);
    }

    public function test_create_order_sends_payload_and_returns_body(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/integration/token')) {
                return Http::response(['token' => 'jwt-test'], 200);
            }

            return Http::response([
                'serial' => 920178,
                'Order_Delivery_Status' => 'Pending',
                'total_fees' => '35.00',
                'referenceID' => 'ORD-1',
            ], 200);
        });

        $remote = (new QpExpressClient($this->integration()))->createOrder([
            'full_name' => 'Ahmed',
            'phone' => '01234567890',
            'city' => 'القاهرة',
            'address' => 'Nasr City',
            'total_amount' => 150,
            'weight' => '1.00',
            'shipment_contents' => '1x Watch',
            'order_date' => now()->toIso8601String(),
            'referenceID' => 'ORD-1',
        ]);

        $this->assertSame(920178, $remote['serial']);
        $this->assertSame('35.00', $remote['total_fees']);
    }

    public function test_failed_auth_throws(): void
    {
        Http::fake([
            '*/integration/token' => Http::response(['detail' => 'No active account found with the given credentials'], 401),
        ]);

        $this->expectException(QpExpressException::class);

        (new QpExpressClient($this->integration()))->authenticate();
    }

    private function integration(): ShippingIntegration
    {
        return ShippingIntegration::create([
            'tenant_id' => $this->foundationTenantId,
            'driver' => 'qp',
            'username' => 'qp-user',
            'password' => 'qp-pass',
            'is_active' => true,
        ]);
    }
}
