<?php

namespace App\Events\POS;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * POS sale recorded — tenant-wide terminal awareness (held orders / dashboards).
 */
class PosSaleCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $saleId,
        public string $saleNumber,
        public ?int $shiftId,
        public ?int $userId,
    ) {}

    public function broadcastWhen(): bool
    {
        return config('broadcasting.default') !== 'null';
    }

    /**
     * Tenant POS channel — terminals subscribe here (avoid duplicate shift + tenant delivery).
     * Shift-specific events can use a dedicated event class later.
     *
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.'.$this->tenantId.'.pos'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sale.completed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'sale_id' => $this->saleId,
            'sale_number' => $this->saleNumber,
            'shift_id' => $this->shiftId,
            'user_id' => $this->userId,
        ];
    }
}
