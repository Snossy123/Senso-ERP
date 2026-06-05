<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceInstallmentPlan extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'sales_invoice_id', 'down_payment', 'installment_count',
        'interval_days', 'first_due_date',
    ];

    protected $casts = [
        'down_payment' => 'decimal:2',
        'first_due_date' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(InvoiceInstallment::class);
    }
}
