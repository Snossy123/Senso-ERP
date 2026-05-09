<?php

namespace App\Events\POS;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcasts post-sale stock levels to tenant inventory channel (multi-terminal sync).
 */
class InventoryBulkUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, array{product_id:int, stock_quantity:float|int}>  $updates
     */
    public function __construct(
        public int $tenantId,
        public array $updates,
        public ?string $reason = null,
    ) {}

    public function broadcastWhen(): bool
    {
        return config('broadcasting.default') !== 'null';
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.'.$this->tenantId.'.inventory'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'inventory.bulk.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'updates' => $this->updates,
            'reason' => $this->reason,
        ];
    }
}
