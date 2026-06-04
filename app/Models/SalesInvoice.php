<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesInvoice extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'invoice_number', 'customer_id', 'user_id', 'warehouse_id',
        'status', 'payment_term', 'subtotal', 'discount_amount', 'tax_amount', 'total',
        'paid_amount', 'balance_due', 'payment_status', 'invoice_date', 'due_date',
        'confirmed_at', 'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLine::class);
    }

    public function installmentPlan(): HasOne
    {
        return $this->hasOne(InvoiceInstallmentPlan::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(InvoiceInstallment::class)->orderBy('sequence');
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(InvoicePaymentAllocation::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function recalculatePaymentStatus(): void
    {
        $paid = (float) $this->paid_amount;
        $total = (float) $this->total;

        if ($paid <= 0) {
            $this->payment_status = 'unpaid';
        } elseif ($paid + 0.009 >= $total) {
            $this->payment_status = 'paid';
        } else {
            $this->payment_status = 'partial';
        }

        $this->balance_due = max(0, round($total - $paid, 2));
    }
}
