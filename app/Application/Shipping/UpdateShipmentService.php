<?php

namespace App\Application\Shipping;

use App\Models\Order;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\ShippingIntegration;
use App\Models\Tenant;
use App\Services\Shipping\QpExpressClient;
use App\Services\Shipping\QpExpressException;
use App\Services\TenantManager;
use InvalidArgumentException;

class UpdateShipmentService
{
    public function __construct(
        private readonly TenantManager $tenants,
        private readonly CreateShipmentService $createShipment,
    ) {}

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function update(Shipment $shipment, array $overrides = []): Shipment
    {
        if (! $shipment->isPending()) {
            throw new InvalidArgumentException('QP only allows edits while the shipment is Pending.');
        }

        if (! filled($shipment->carrier_serial)) {
            throw new InvalidArgumentException('Shipment has no QP serial to update.');
        }

        $tenant = Tenant::query()->find($shipment->tenant_id);
        if ($tenant) {
            $this->tenants->setCurrent($tenant);
        }

        $integration = ShippingIntegration::query()
            ->where('tenant_id', $shipment->tenant_id)
            ->first();

        if (! $integration || ! $integration->isConfigured()) {
            throw new InvalidArgumentException('QP Express shipping is not configured for this company.');
        }

        $shippable = $shipment->shippable;
        if (! $shippable instanceof Order && ! $shippable instanceof SalesInvoice) {
            throw new InvalidArgumentException('Shipment is not linked to an order or invoice.');
        }

        $payload = $this->createShipment->buildPayload($shippable, $integration, $overrides);
        $payload['serial'] = $shipment->carrier_serial;

        $client = new QpExpressClient($integration);

        try {
            $remote = $client->updateOrder($shipment->carrier_serial, $payload);
        } catch (QpExpressException $e) {
            throw new InvalidArgumentException($e->getMessage(), 0, $e);
        }

        $shipment->update([
            'status' => $remote['Order_Delivery_Status'] ?? $shipment->status,
            'status_note' => $remote['StatusNote'] ?? $shipment->status_note,
            'total_fees' => $remote['total_fees'] ?? $shipment->total_fees,
            'weight' => $remote['weight'] ?? $payload['weight'],
            'full_name' => $payload['full_name'],
            'phone' => $payload['phone'],
            'city' => $payload['city'],
            'address' => $payload['address'],
            'last_synced_at' => now(),
            'raw_payload' => $remote,
        ]);

        return $shipment->fresh();
    }
}
