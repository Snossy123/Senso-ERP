<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'sale_id', 'product_id', 'product_variant_id', 'quantity', 'qty_refunded',
        'unit_price', 'discount', 'discount_pct', 'discount_amount', 'total', 'tenant_id',
    ];

    public function refundableQty(): int
    {
        return max(0, (int) $this->quantity - (int) ($this->qty_refunded ?? 0));
    }

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'discount_pct' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
