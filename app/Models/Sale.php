<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'sale_number', 'customer_id', 'customer_name', 'customer_email', 'user_id', 'shift_id',
        'subtotal', 'discount_amount', 'tax_amount', 'total',
        'payment_method', 'payment_status', 'amount_tendered', 'change_due',
        'status', 'notes', 'void_reason', 'voided_by', 'voided_at',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total'           => 'decimal:2',
        'amount_tendered' => 'decimal:2',
        'change_due'      => 'decimal:2',
        'voided_at'       => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class, 'shift_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(SaleRefund::class);
    }

    public function void(string $reason, int $userId): void
    {
        $this->update([
            'status'      => 'voided',
            'void_reason' => $reason,
            'voided_by'   => $userId,
            'voided_at'   => now(),
        ]);
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public static function generateSaleNumber(): string
    {
        $date  = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return 'SL-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
