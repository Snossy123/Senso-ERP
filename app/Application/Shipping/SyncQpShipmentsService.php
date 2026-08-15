<?php

namespace App\Application\Shipping;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingIntegration;
use App\Models\Tenant;
use App\Services\Shipping\QpExpressClient;
use App\Services\Shipping\QpExpressException;
use App\Services\TenantManager;

class SyncQpShipmentsService
{
    public function __construct(private readonly TenantManager $tenants) {}

    /**
     * @return array{tenants: int, updated: int, errors: int}
     */
    public function syncAll(): array
    {
        $stats = ['tenants' => 0, 'updated' => 0, 'errors' => 0];

        $integrations = ShippingIntegration::withoutGlobalScopes()
            ->where('is_active', true)
            ->whereNotNull('username')
            ->get();

        foreach ($integrations as $integration) {
            if (! filled($integration->password)) {
                continue;
            }

            $stats['tenants']++;

            try {
                $stats['updated'] += $this->syncIntegration($integration);
            } catch (QpExpressException) {
                $stats['errors']++;
            }
        }

        return $stats;
    }

    public function syncIntegration(ShippingIntegration $integration): int
    {
        $tenant = Tenant::query()->find($integration->tenant_id);
        if ($tenant) {
            $this->tenants->setCurrent($tenant);
        }

        $client = new QpExpressClient($integration);
        $from = $integration->last_history_synced_at
            ? $integration->last_history_synced_at->copy()->subHour()->toDateString()
            : now()->subDays(7)->toDateString();

        $history = $client->getUpdateHistory([
            'page' => 1,
            'page_size' => 200,
            'from_date' => $from,
        ]);

        $updated = 0;
        $serials = [];

        foreach ($history as $row) {
            $serial = (string) ($row['serial'] ?? '');
            if ($serial === '') {
                continue;
            }
            $serials[$serial] = true;

            if (($row['field'] ?? '') !== 'Order_Delivery_Status' && ($row['field'] ?? '') !== 'StatusNote') {
                continue;
            }

            $shipment = Shipment::withoutGlobalScopes()
                ->where('tenant_id', $integration->tenant_id)
                ->where('carrier', 'qp')
                ->where('carrier_serial', $serial)
                ->first();

            if (! $shipment) {
                continue;
            }

            if (($row['field'] ?? '') === 'Order_Delivery_Status' && filled($row['new_value'] ?? null)) {
                $this->applyStatus($shipment, (string) $row['new_value'], $row['notes'] ?? $shipment->status_note);
                $updated++;
            } elseif (($row['field'] ?? '') === 'StatusNote') {
                $shipment->update([
                    'status_note' => $row['new_value'] ?? $shipment->status_note,
                    'last_synced_at' => now(),
                ]);
                $updated++;
            }
        }

        foreach (array_keys($serials) as $serial) {
            $shipment = Shipment::withoutGlobalScopes()
                ->where('tenant_id', $integration->tenant_id)
                ->where('carrier', 'qp')
                ->where('carrier_serial', $serial)
                ->first();

            if (! $shipment) {
                continue;
            }

            try {
                $this->applyRemoteOrder($shipment, $client->getOrder($serial));
            } catch (QpExpressException) {
                // History already applied; retrieve is best-effort.
            }
        }

        $integration->update(['last_history_synced_at' => now()]);

        return $updated;
    }

    /**
     * @param  array<string, mixed>  $remote
     */
    public function applyRemoteOrder(Shipment $shipment, array $remote): void
    {
        $status = $remote['Order_Delivery_Status'] ?? $shipment->status;

        $shipment->fill([
            'status' => $status,
            'status_note' => $remote['StatusNote'] ?? $shipment->status_note,
            'total_fees' => $remote['total_fees'] ?? $shipment->total_fees,
            'carrier_serial' => (string) ($remote['serial'] ?? $shipment->carrier_serial),
            'last_synced_at' => now(),
            'raw_payload' => $remote,
        ]);
        $shipment->save();

        $this->applyStatus($shipment, (string) $status, $remote['StatusNote'] ?? $shipment->status_note);
    }

    public function applyStatus(Shipment $shipment, string $qpStatus, mixed $note = null): void
    {
        $shipment->fill([
            'status' => $qpStatus,
            'status_note' => $note ?? $shipment->status_note,
            'last_synced_at' => now(),
        ]);
        $shipment->save();

        $orderStatus = $this->mapToOrderStatus($qpStatus);
        if (! $orderStatus) {
            return;
        }

        $shippable = $shipment->shippable;
        if (! $shippable instanceof Order) {
            return;
        }

        if (in_array($shippable->status, ['cancelled', 'delivered'], true) && $orderStatus !== 'delivered') {
            return;
        }

        if ($shippable->status !== $orderStatus) {
            $shippable->update(['status' => $orderStatus]);
        }
    }

    public function mapToOrderStatus(string $qpStatus): ?string
    {
        $normalized = strtolower(str_replace(['_', '-'], ' ', trim($qpStatus)));

        return match (true) {
            str_contains($normalized, 'out for deliver') => 'shipped',
            $normalized === 'delivered' || str_ends_with($normalized, ' delivered') => 'delivered',
            $normalized === 'pending' => 'processing',
            default => null,
        };
    }
}
