<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'supplier_id',
        'payment_number',
        'amount',
        'payment_date',
        'payment_method',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'payment_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generatePaymentNumber(int $tenantId): string
    {
        $date = now()->format('Ymd');
        $count = self::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('created_at', today())
            ->count() + 1;

        return 'PAY-'.$date.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
