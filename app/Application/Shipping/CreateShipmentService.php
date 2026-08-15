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
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateShipmentService
{
    public function __construct(private readonly TenantManager $tenants) {}

    /**
     * @param  array{
     *   full_name?: string,
     *   phone?: string,
     *   address?: string,
     *   city?: string,
     *   notes?: string|null,
     *   weight?: float|string|null
     * }  $overrides
     */
    public function create(Order|SalesInvoice $shippable, array $overrides = []): Shipment
    {
        $tenantId = (int) $shippable->tenant_id;
        $tenant = Tenant::query()->find($tenantId);
        if ($tenant) {
            $this->tenants->setCurrent($tenant);
        }

        $existing = Shipment::query()
            ->where('shippable_type', $shippable->getMorphClass())
            ->where('shippable_id', $shippable->id)
            ->first();

        if ($existing) {
            throw new InvalidArgumentException('A shipment already exists for this document.');
        }

        $integration = ShippingIntegration::query()->where('tenant_id', $tenantId)->first();
        if (! $integration || ! $integration->isConfigured()) {
            throw new InvalidArgumentException('QP Express shipping is not configured for this company.');
        }

        $payload = $this->buildPayload($shippable, $integration, $overrides);
        $client = new QpExpressClient($integration);

        try {
            $remote = $client->createOrder($payload);
        } catch (QpExpressException $e) {
            throw new InvalidArgumentException($e->getMessage(), 0, $e);
        }

        $shipment = Shipment::create([
            'tenant_id' => $tenantId,
            'shippable_type' => $shippable->getMorphClass(),
            'shippable_id' => $shippable->id,
            'carrier' => 'qp',
            'carrier_serial' => (string) ($remote['serial'] ?? ''),
            'reference_id' => (string) ($remote['referenceID'] ?? $payload['referenceID']),
            'status' => $remote['Order_Delivery_Status'] ?? Shipment::STATUS_PENDING,
            'status_note' => $remote['StatusNote'] ?? null,
            'total_fees' => $remote['total_fees'] ?? 0,
            'weight' => $remote['weight'] ?? $payload['weight'],
            'full_name' => $payload['full_name'],
            'phone' => $payload['phone'],
            'city' => $payload['city'],
            'address' => $payload['address'],
            'last_synced_at' => now(),
            'raw_payload' => $remote,
        ]);

        if ($shippable instanceof Order && $shippable->status === 'pending') {
            $shippable->update(['status' => 'processing']);
        }

        return $shipment;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function buildPayload(Order|SalesInvoice $shippable, ShippingIntegration $integration, array $overrides = []): array
    {
        $snapshot = $this->destinationSnapshot($shippable, $overrides);

        return [
            'full_name' => $snapshot['full_name'],
            'phone' => $snapshot['phone'],
            'address' => $snapshot['address'],
            'city' => $snapshot['city'],
            'total_amount' => $this->codAmount($shippable),
            'notes' => $overrides['notes'] ?? ($shippable instanceof Order ? (string) $shippable->notes : (string) $shippable->notes),
            'order_date' => now('Africa/Cairo')->toIso8601String(),
            'shipment_contents' => $this->contents($shippable),
            'weight' => number_format($this->weight($shippable, $integration, $overrides), 2, '.', ''),
            'referenceID' => $this->referenceId($shippable),
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{full_name: string, phone: string, address: string, city: string}
     */
    private function destinationSnapshot(Order|SalesInvoice $shippable, array $overrides): array
    {
        if ($shippable instanceof Order) {
            $fullName = $overrides['full_name'] ?? $shippable->customer_name;
            $phone = $overrides['phone'] ?? $shippable->customer_phone;
            $address = $overrides['address'] ?? $shippable->shipping_address;
            $city = $overrides['city'] ?? $shippable->city;
        } else {
            $customer = $shippable->relationLoaded('customer') ? $shippable->customer : $shippable->customer()->first();
            $fullName = $overrides['full_name'] ?? $customer?->name;
            $phone = $overrides['phone'] ?? $customer?->phone;
            $address = $overrides['address'] ?? $customer?->address;
            $city = $overrides['city'] ?? $customer?->city;
        }

        foreach (['full_name' => $fullName, 'phone' => $phone, 'address' => $address, 'city' => $city] as $field => $value) {
            if (! filled($value)) {
                throw new InvalidArgumentException('Missing required shipping field: '.$field);
            }
        }

        return [
            'full_name' => (string) $fullName,
            'phone' => (string) $phone,
            'address' => (string) $address,
            'city' => (string) $city,
        ];
    }

    private function contents(Order|SalesInvoice $shippable): string
    {
        if ($shippable instanceof Order) {
            $shippable->loadMissing('items');

            $names = $shippable->items->map(function ($item) {
                return $item->quantity.'x '.($item->product_name ?: 'Item');
            })->filter()->implode(', ');
        } else {
            $shippable->loadMissing('lines.product');

            $names = $shippable->lines->map(function ($line) {
                $name = $line->description ?? $line->product?->name ?? 'Item';

                return $line->quantity.'x '.$name;
            })->filter()->implode(', ');
        }

        return $names !== '' ? mb_substr($names, 0, 250) : 'Order '.$this->referenceId($shippable);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function weight(Order|SalesInvoice $shippable, ShippingIntegration $integration, array $overrides): float
    {
        if (isset($overrides['weight']) && is_numeric($overrides['weight'])) {
            return (float) $overrides['weight'];
        }

        $total = 0.0;
        $hasWeight = false;

        if ($shippable instanceof Order) {
            $shippable->loadMissing('items.product');
            foreach ($shippable->items as $item) {
                $weight = $item->product?->weight;
                if ($weight !== null && (float) $weight > 0) {
                    $total += (float) $weight * (int) $item->quantity;
                    $hasWeight = true;
                }
            }
        } else {
            $shippable->loadMissing('lines.product');
            foreach ($shippable->lines as $line) {
                $weight = $line->product?->weight;
                if ($weight !== null && (float) $weight > 0) {
                    $total += (float) $weight * (int) $line->quantity;
                    $hasWeight = true;
                }
            }
        }

        return $hasWeight ? $total : (float) $integration->default_weight;
    }

    private function codAmount(Order|SalesInvoice $shippable): float
    {
        if ($shippable instanceof Order) {
            return $shippable->payment_status === 'paid' ? 0.0 : (float) $shippable->total;
        }

        return (float) $shippable->balance_due;
    }

    private function referenceId(Model $shippable): string
    {
        $prefix = $shippable instanceof SalesInvoice ? 'INV' : 'ORD';

        return $prefix.'-'.$shippable->id;
    }
}
