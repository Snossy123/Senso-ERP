<?php

namespace App\Application\Shipping;

use App\Models\Shipment;
use App\Models\ShippingIntegration;
use App\Services\Shipping\QpExpressClient;
use App\Services\Shipping\QpExpressException;
use App\Services\TenantManager;
use InvalidArgumentException;

class RefreshShipmentService
{
    public function __construct(
        private readonly TenantManager $tenants,
        private readonly SyncQpShipmentsService $sync,
    ) {}

    public function refresh(Shipment $shipment): Shipment
    {
        if ($shipment->tenant) {
            $this->tenants->setCurrent($shipment->tenant);
        }

        $integration = ShippingIntegration::query()
            ->where('tenant_id', $shipment->tenant_id)
            ->first();

        if (! $integration || ! $integration->isConfigured()) {
            throw new InvalidArgumentException('QP Express shipping is not configured for this company.');
        }

        if (! filled($shipment->carrier_serial)) {
            throw new InvalidArgumentException('Shipment has no QP serial to refresh.');
        }

        $client = new QpExpressClient($integration);

        try {
            $remote = $client->getOrder($shipment->carrier_serial);
        } catch (QpExpressException $e) {
            throw new InvalidArgumentException($e->getMessage(), 0, $e);
        }

        $this->sync->applyRemoteOrder($shipment, $remote);

        return $shipment->fresh();
    }
}
