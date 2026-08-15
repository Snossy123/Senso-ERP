<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Shipment extends Model
{
    use BelongsToTenant;

    public const STATUS_PENDING = 'Pending';

    public const STATUS_OUT_FOR_DELIVERY = 'Out For Delivery';

    public const STATUS_DELIVERED = 'Delivered';

    public const STATUS_HOLD = 'Hold';

    public const STATUS_UNDELIVERED = 'Undelivered';

    public const STATUS_REJECTED = 'Rejected';

    protected $fillable = [
        'tenant_id',
        'shippable_type',
        'shippable_id',
        'carrier',
        'carrier_serial',
        'reference_id',
        'status',
        'status_note',
        'total_fees',
        'weight',
        'full_name',
        'phone',
        'city',
        'address',
        'last_synced_at',
        'raw_payload',
    ];

    protected $casts = [
        'total_fees' => 'decimal:2',
        'weight' => 'decimal:3',
        'last_synced_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function shippable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPending(): bool
    {
        return $this->normalizedStatus() === 'pending';
    }

    public function normalizedStatus(): string
    {
        return strtolower(trim((string) $this->status));
    }

    public function statusBadge(): string
    {
        return match ($this->normalizedStatus()) {
            'pending' => 'warning',
            'out for delivery', 'out for deliver' => 'primary',
            'delivered' => 'success',
            'hold' => 'info',
            'undelivered' => 'secondary',
            'rejected' => 'danger',
            default => 'light',
        };
    }
}
