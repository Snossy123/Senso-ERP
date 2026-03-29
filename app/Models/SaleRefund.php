<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleRefund extends Model
{
    use BelongsToTenant;

    protected $table = 'sale_refunds';

    protected $fillable = [
        'tenant_id', 'sale_id', 'user_id',
        'refund_number', 'amount', 'reason', 'method',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateRefundNumber(): string
    {
        $date  = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return 'REF-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
