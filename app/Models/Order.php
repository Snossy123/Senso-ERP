<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\Loggable;

class Order extends Model
{
    use BelongsToTenant, Loggable;

    protected $fillable = [
        'order_number', 'customer_id', 'customer_name', 'customer_email', 'customer_phone',
        'shipping_address', 'city', 'status',
        'subtotal', 'shipping_cost', 'tax_amount', 'total',
        'payment_method', 'payment_status', 'notes',
    ];

    protected $casts = [
        'subtotal'      => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_amount'    => 'decimal:2',
        'total'         => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateOrderNumber(): string
    {
        $date  = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return 'ORD-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending'    => 'warning',
            'processing' => 'info',
            'shipped'    => 'primary',
            'delivered'  => 'success',
            'cancelled'  => 'danger',
            default      => 'secondary',
        };
    }
}
