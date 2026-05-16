<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleRefundItem extends Model
{
    protected $fillable = [
        'sale_refund_id', 'sale_item_id', 'quantity', 'line_amount', 'restocked_qty',
    ];

    protected $casts = [
        'line_amount' => 'decimal:2',
    ];

    public function refund(): BelongsTo
    {
        return $this->belongsTo(SaleRefund::class, 'sale_refund_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }
}
